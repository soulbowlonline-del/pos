<?php
namespace app\controllers;

use Yii;
use app\models\Order;
use app\models\PaymentMode;
use app\models\PurchaseBill;
use app\models\PurchaseBillDetail;
use yii\web\Controller;
use yii\web\Response;

/**
 * Partial Yii 2 port of protected/modules/api/controllers/OrderController.php.
 *
 * The Yii 1 controller has eighteen actions across 1,117 lines, backed by an
 * Order model of 1,246 lines and an OrderItem model of 2,809. Three read
 * actions are ported here, the ones that need only Order::toArray1():
 *
 *   modes, list, getLastOrder, get, getDescriptionByBillId,
 *   getDescriptionByGrn
 *
 * Not ported yet:
 *   search          needs the search/filter query builder
 *   customer/getOrderHold  reads an OrderHold and then deletes it, so it cannot
 *                   be compared without rebuilding the fixture between calls;
 *                   it also needs OrderHold::toArray()
 *   toArray() is 248 lines with its own dependency tree (refunds, loyalty
 *   transactions, outlets, payment and delivery modes). It is the next
 *   increment, and it also unblocks customer/orderList, customer/getOrder and
 *   customer/getOrderHold.
 *
 * Not ported - order write paths:
 *   cancelOrder, shipOrder, completeOrder, assignOrder, orderUpdate, refund,
 *   reprint, online, getOnlineOrder, getAssignList, discount,
 *   getDescriptionByBillId, getDescriptionByGrn
 *   These are the critical POS flows: they move stock, take payment and issue
 *   refunds. They deserve their own increment with the differential harness
 *   run against a fixture order, not a port bundled in with read endpoints.
 *
 * Yii 1 continues to serve every /api/order/* route meanwhile.
 */
class OrderController extends Controller
{
    public $enableCsrfValidation = false;

    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    private function envelope($action)
    {
        return [
            'controller' => 'order',
            'action' => $action,
            'status' => 'NOK',
        ];
    }

    /**
     * POST /v2/api/order/modes
     *
     * The Yii 1 version concatenates $type into the condition. Bound here.
     */
    public function actionModes($type = 0)
    {
        $out = $this->envelope('modes');

        // id desc matches what the Yii 1 endpoint has been returning; see the
        // note on the Yii 1 side.
        $modes = PaymentMode::find()
            ->where(['type_id' => $type])
            ->orderBy(['id' => SORT_DESC])
            ->all();

        if (empty($modes)) {
            $out['message'] = 'Order not available';
            return $out;
        }

        $list = [];
        foreach ($modes as $mode) {
            $list[] = [
                'id' => (string)$mode->id,
                'title' => $mode->title,
            ];
        }
        $out['status'] = 'OK';
        $out['modes'] = $list;
        return $out;
    }

    /** POST /v2/api/order/list - orders with bill_date between start_date and end_date. */
    public function actionList()
    {
        $out = $this->envelope('list');
        $req = Yii::$app->request;

        $start = $req->post('start_date');
        $end = $req->post('end_date');
        if ($start === null || $end === null) {
            $out['message'] = 'No data posted';
            return $out;
        }

        $orders = Order::find()
            ->where(['between', 'bill_date', $start, $end])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        if (empty($orders)) {
            $out['message'] = 'No data to display';
            return $out;
        }

        $list = [];
        foreach ($orders as $order) {
            $list[] = $order->toApiArray1();
        }
        $out['status'] = 'OK';
        $out['orders'] = $list;
        return $out;
    }

    /** POST /v2/api/order/get-last-order */
    public function actionGetLastOrder()
    {
        $out = $this->envelope('getLastOrder');

        $order = Order::find()->orderBy(['id' => SORT_DESC])->limit(1)->one();
        if (!$order) {
            $out['message'] = 'No order found';
            return $out;
        }

        $out['status'] = 'OK';
        // Yii 1 wrapped the single order in a list; preserved.
        $out['orders'][] = $order->toApiArray1();
        return $out;
    }

    /**
     * POST /v2/api/order/get?id=N
     *
     * The full order with its line items, quantities and totals reported net of
     * any refund against the same item.
     */
    public function actionGet($id)
    {
        $out = $this->envelope('get');

        $order = Order::findOne($id);
        if (empty($order)) {
            $out['message'] = 'Order not available';
            return $out;
        }

        $out['status'] = 'OK';
        $out['order'] = $order->toApiArray();
        return $out;
    }

    /**
     * POST /v2/api/order/get-description-by-bill-id  (bill_id)
     *
     * Resolves a bill to a one-line description. A non-numeric bill_id has its
     * digits extracted first, so "B-40840" finds bill 40840.
     */
    public function actionGetDescriptionByBillId()
    {
        $out = $this->envelope('getDescriptionByBillId');
        $req = Yii::$app->request;

        $billId = $req->post('bill_id', $req->get('bill_id'));
        if (empty($billId)) {
            $out['message'] = 'Bill ID is required';
            return $out;
        }

        $billNo = is_numeric($billId) ? $billId : preg_replace('/[^0-9]/', '', $billId);

        $order = Order::find()
            ->where(['bill_no' => $billNo])
            ->orderBy(['id' => SORT_DESC])   // latest, if several share a number
            ->one();

        if (!$order) {
            $out['message'] = 'Bill not found with ID: ' . $billId;
            return $out;
        }

        $billDate = date('d-m-Y', strtotime((string)$order->bill_date));
        $out['status'] = 'OK';
        $out['description'] = "Bill No: {$order->bill_no} - Date: {$billDate}";
        $out['bill_number'] = $order->bill_no === null ? null : (string)$order->bill_no;
        $out['bill_date'] = $billDate;
        return $out;
    }

    /**
     * POST /v2/api/order/get-description-by-grn  (grn_number)
     *
     * Renders a purchase bill and its lines as a plain-text block.
     *
     * The Yii 1 version reads $detail->unit_price, $detail->qty and
     * $detail->product_id behind isset() guards, but none of those columns
     * exists on tbl_purchase_bill_detail - the table has approved_qty, mrp,
     * price and amount. Those branches are therefore unreachable, and reaching
     * them would raise an unknown-property error in either framework. Only the
     * live branches are ported.
     *
     * The line query has no ORDER BY in Yii 1, so the item order within a GRN
     * was the database's choice; ordered by id on both sides.
     */
    public function actionGetDescriptionByGrn()
    {
        $out = $this->envelope('getDescriptionByGrn');
        $req = Yii::$app->request;

        $grnNumber = $req->post('grn_number', $req->get('grn_number'));
        if (empty($grnNumber)) {
            $out['message'] = 'GRN Number is required';
            return $out;
        }

        $purchaseBill = PurchaseBill::findOne(['id' => $grnNumber]);
        if (!$purchaseBill) {
            $out['message'] = 'GRN not found: ' . $grnNumber;
            return $out;
        }

        $details = PurchaseBillDetail::find()
            ->with('item')
            ->where(['purchase_bill_id' => $purchaseBill->id])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        if (empty($details)) {
            $out['message'] = 'No items found for GRN: ' . $grnNumber;
            return $out;
        }

        $billDate = date('d-m-Y', strtotime((string)$purchaseBill->create_time));

        $description = "GRN: {$grnNumber}\n";
        $description .= "Date: " . $billDate . "\n\n";
        $description .= "Items:\n";

        foreach ($details as $detail) {
            $productName = isset($detail->item) ? $detail->item->short_name : 'Product ID: ' . $detail->item_id;
            $approvedQty = isset($detail->approved_qty) ? $detail->approved_qty : null;
            $mrp = isset($detail->mrp) ? number_format($detail->mrp, 2) : '0.00';
            $price = isset($detail->price) ? number_format($detail->price, 2) : '0.00';
            $amount = isset($detail->amount) ? number_format($detail->amount, 2) : ($approvedQty * $price);

            $description .= "- {$productName}, Qty: {$approvedQty}, MRP: \u{20B9}{$mrp}, Price: \u{20B9}{$price}, Amount: \u{20B9}{$amount}\n";
        }

        $out['status'] = 'OK';
        $out['description'] = $description;
        $out['grn_number'] = $grnNumber;
        $out['bill_date'] = $billDate;
        $out['item_count'] = count($details);
        return $out;
    }
}
