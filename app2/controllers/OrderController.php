<?php
namespace app\controllers;

use Yii;
use app\models\Order;
use app\models\PaymentMode;
use app\models\PurchaseBill;
use app\models\Discount;
use app\models\OnlineOrder;
use app\models\OrderItem;
use app\models\Outlet;
use app\models\User;
use PosOutbound;
use app\models\PurchaseBillDetail;
use yii\web\Controller;
use yii\web\Response;

/**
 * Yii 2 port of protected/modules/api/controllers/OrderController.php.
 *
 * Sixteen of the eighteen actions are ported and covered by differential
 * tests. Yii 1 still serves every /api/order/* route; nothing is cut over by
 * this file existing.
 *
 * Not ported:
 *
 *   refund   The refund path writes OrderRefund, OrderRefundItem, ItemStock
 *            and StockLog, and deletes MrsDetail and Mrs rows, inside one
 *            transaction. It needs four models that do not exist on this side
 *            yet and it moves both money and stock, so it wants its own
 *            increment with a fixture order to refund against - not a port
 *            bundled in with the endpoints around it.
 *
 *   search   Queries tbl_order_item for bill_date, bill_no and customer_id,
 *            three columns that only exist on tbl_order, so it throws for
 *            every request that reaches it. Twelve users can reach it. Fixing
 *            it means choosing what the endpoint returns, which is a decision
 *            rather than a port: see docs/live-bugs-found.md.
 *
 * Yii 1 action ids are camelCase; the Yii 2 routes are hyphenated, so
 * /api/order/getOnlineOrder is /v2/api/order/get-online-order. Both read their
 * parameters from the query string, except the date windows, which are POSTed.
 *
 * Reproduced rather than corrected, each commented at its call site:
 * online() and getOnlineOrder() overwrite the caller id with the literal '1',
 * so neither is authenticated; cancelOrder() lets any logged-in caller cancel
 * any order; shipOrder() sends its dispatch notification before saving and
 * never checks the reply; reprint() leaves `taxes` null when an order has no
 * lines; and orderUpdate() sets the text `status` column while completeOrder()
 * sets the numeric `order_status` - different fields with similar names.
 *
 * shipOrder and completeOrder reach the webshop's dispatch endpoint and
 * Firebase. Both go through lib/PosOutbound.php when POS_STUB_OUTBOUND=1, and
 * the differential suite compares the recorded calls as well as the response
 * and the row.
 *
 * Response envelopes are reproduced exactly, as with the other ports.
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
     * price and amount.
     *
     * isset() on a missing property is merely false, so unit_price harmlessly
     * falls through to price. The other two are read directly, and a direct
     * read of a property Yii 1 does not know raises "Property ... is not
     * defined" - so a line with a NULL approved_qty, or an item_id with no
     * matching item, took this endpoint down. No such line exists in the
     * current data, which is why it has never been seen to fail; the GRN
     * fixture creates both, which is how it was found. The Yii 1 action now
     * uses item_id and falls back to null, and this port matches it.
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
    /**
     * POST /v2/api/order/discount
     *
     * The order-level discounts that are live today. A discount with both
     * times at 00:00:00 counts as all-day; otherwise the current time has to
     * fall inside the window.
     *
     * Yii 1 sets status to 'OK' as soon as any discount matches the *date*
     * filter, before the time filter runs - so a discount that exists but is
     * outside its hours returns status OK, a "Discount not available" message
     * and no discountList at all. Reproduced: the client reads status.
     *
     * The Yii 1 query had no ORDER BY. Ordered by id on both sides.
     */
    public function actionDiscount()
    {
        $out = $this->envelope('discount');

        $today = date('Y-m-d');
        $now = date('H:i:s');

        $discounts = Discount::find()
            ->where(['<=', 'start_date', $today])
            ->andWhere(['>=', 'end_date', $today])
            ->andWhere(['discount_type' => Discount::DISCOUNT_ORDER])
            ->andWhere(['status' => Discount::STATUS_ACTIVE])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        if (empty($discounts)) {
            $out['message'] = 'Discount not available';
            return $out;
        }

        // set before the time filter, as in Yii 1
        $out['status'] = 'OK';

        $list = [];
        foreach ($discounts as $discount) {
            $allDay = ($discount->start_time == '00:00:00') && ($discount->end_time == '00:00:00');
            if ($allDay || ($discount->start_time <= $now && $discount->end_time >= $now)) {
                $list[] = $discount->toApiArray();
            }
        }

        if (!empty($list)) {
            $out['discountList'] = $list;
        } else {
            $out['message'] = 'Discount not available';
        }

        return $out;
    }
    /** The unverified caller id. Yii 1 reads 'userlogin', then 'login_id'. */
    private function headerUserId()
    {
        $headers = Yii::$app->request->getHeaders();
        $v = $headers->get('userlogin');
        if ($v === null || $v === '') {
            $v = $headers->get('login_id');
        }
        return ($v === null || $v === '') ? null : $v;
    }

    /**
     * The date window these three actions share. Yii 1 runs both POSTed dates
     * through strtotime, so anything strtotime understands is accepted and
     * anything it does not becomes 1970-01-01. Reproduced.
     */
    private function dateWindow($defaultStart)
    {
        $post = Yii::$app->request->post();
        if (isset($post['start_date']) && $post['start_date'] != ''
            && isset($post['end_date']) && $post['end_date'] != '') {
            return [
                date('Y-m-d', strtotime($post['start_date'])),
                date('Y-m-d', strtotime($post['end_date'])),
            ];
        }
        return [$defaultStart, date('Y-m-d')];
    }

    /**
     * POST /v2/api/order/get-assign-list
     *
     * The online orders assigned to the calling delivery rider. status=3 asks
     * for the completed ones; anything else means shipped-but-not-completed.
     *
     * The Yii 1 query concatenated both the status and the caller id - which
     * comes from a request header - straight into the SQL. Both are bound
     * parameters now, on both stacks. It also had no ORDER BY; ordered by id.
     */
    public function actionGetAssignList($status = null)
    {
        $out = $this->envelope('getAssignList');

        $loginId = $this->headerUserId();
        if ($loginId === null) {
            $out['message'] = 'Please login';
            return $out;
        }

        list($startDate, $endDate) = $this->dateWindow('2020-05-21');

        $query = OnlineOrder::find()
            ->where(['between', 'date(order_date)', $startDate, $endDate]);

        if ($status == 3) {
            $query->andWhere('order_status = :status', [':status' => $status]);
        } else {
            $query->andWhere(['is_shipped' => OnlineOrder::ORDER_SHIPPED])
                  ->andWhere(['!=', 'order_status', OnlineOrder::ORDERSTATUS_COMPLETED]);
        }

        $orders = $query->andWhere('delivery_boy_id = :dbid', [':dbid' => $loginId])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        if (empty($orders)) {
            $out['message'] = 'Online Order not available';
            return $out;
        }

        $list = [];
        foreach ($orders as $order) {
            $list[] = $order->toApiArray();
        }
        $out['status'] = 'OK';
        $out['orders'] = $list;
        return $out;
    }

    /**
     * POST /v2/api/order/online
     *
     * The online orders in a status. Note that Yii 1 overwrites the caller id
     * with the literal '1' immediately after reading it, so the login check
     * below can never fail and the endpoint is effectively unauthenticated.
     * Reproduced, and recorded in docs/live-bugs-found.md.
     *
     * status=2 means "packed or shipped", not "status 2".
     */
    public function actionOnline($status = null)
    {
        $out = $this->envelope('online');

        $this->headerUserId();
        $loginId = '1';   // as in Yii 1 - see above

        if (!$loginId) {
            $out['message'] = 'Please login';
            return $out;
        }

        list($startDate, $endDate) = $this->dateWindow('2021-05-14');

        $query = OnlineOrder::find()
            ->where(['between', 'date(order_date)', $startDate, $endDate]);

        if ($status != null && $status != 0 && $status != '') {
            if ($status == 2) {
                $query->andWhere(['order_status' => ['1', '2']]);
            } else {
                $query->andWhere('order_status = :status', [':status' => $status]);
            }
        } else {
            $query->andWhere(['order_status' => OnlineOrder::ORDERSTATUS_PENDING]);
        }

        $orders = $query->orderBy(['id' => SORT_ASC])->all();

        if (empty($orders)) {
            $out['message'] = 'Online Order not available';
            return $out;
        }

        $list = [];
        foreach ($orders as $order) {
            $list[] = $order->toApiArray();
        }
        $out['status'] = 'OK';
        $out['orders'] = $list;
        return $out;
    }

    /**
     * POST /v2/api/order/get-online-order
     *
     * One online order, with its line items. Same hardcoded caller id as
     * online(). With no id at all Yii 1 falls through every branch and returns
     * the bare envelope - no message key - which is reproduced here.
     */
    public function actionGetOnlineOrder($id = null)
    {
        $out = $this->envelope('getOnlineOrder');

        $this->headerUserId();
        $loginId = '1';   // as in Yii 1

        if (!$loginId) {
            $out['message'] = 'Please login';
            return $out;
        }

        if ($id === null) {
            return $out;   // no message key, as in Yii 1
        }

        $order = OnlineOrder::findOne($id);
        if (empty($order)) {
            $out['message'] = 'Order not available';
            return $out;
        }

        $out['status'] = 'OK';
        $out['order'] = $order->toApiArray(true);
        return $out;
    }
    /**
     * POST /v2/api/order/cancel-order?id=N
     *
     * Any logged-in caller can cancel any online order - the id is not checked
     * against the caller. Reproduced; recorded in docs/live-bugs-found.md.
     *
     * When save() fails the Yii 1 version falls through with status NOK and no
     * message at all, which is also reproduced.
     */
    public function actionCancelOrder($id = null)
    {
        $out = $this->envelope('cancelOrder');

        if ($this->headerUserId() === null) {
            $out['message'] = 'Please login';
            return $out;
        }

        $onlineOrder = OnlineOrder::findOne($id);
        if (!$onlineOrder) {
            $out['message'] = 'Online Order not available';
            return $out;
        }

        $onlineOrder->order_status = OnlineOrder::ORDERSTATUS_CANCELLED;
        if ($onlineOrder->save()) {
            $out['status'] = 'OK';
            $out['message'] = 'Order is cancelled successfully';
            $out['assigned'] = $onlineOrder->toApiArray();
        }

        return $out;
    }

    /**
     * POST /v2/api/order/assign-order?id=N
     *
     * Sets the picker and/or the delivery rider. Either may be omitted; an
     * empty value leaves the existing one alone.
     */
    public function actionAssignOrder($id = null)
    {
        $out = $this->envelope('assignOrder');

        if ($this->headerUserId() === null) {
            $out['message'] = 'Please login';
            return $out;
        }

        $onlineOrder = OnlineOrder::findOne($id);
        if (!$onlineOrder) {
            $out['message'] = 'Online Order not available';
            return $out;
        }

        $post = Yii::$app->request->post();
        if (isset($post['picker_id']) && $post['picker_id'] != '') {
            $onlineOrder->picker_id = $post['picker_id'];
        }
        if (isset($post['delivery_boy_id']) && $post['delivery_boy_id'] != '') {
            $onlineOrder->delivery_boy_id = $post['delivery_boy_id'];
        }

        if ($onlineOrder->save()) {
            $out['status'] = 'OK';
            $out['assigned'] = $onlineOrder->toApiArray();
        }

        return $out;
    }

    /**
     * POST /v2/api/order/order-update?id=N
     *
     * Sets the online order's *text* status to Completed. Note this is the
     * `status` column, not `order_status` - completeOrder() sets the other one.
     * No login check on this one at all.
     *
     * With no id the Yii 1 version returns the bare envelope, no message.
     */
    public function actionOrderUpdate($id = null)
    {
        $out = $this->envelope('orderUpdate');

        if ($id === null) {
            return $out;
        }

        $order = OnlineOrder::findOne($id);
        if (empty($order)) {
            $out['message'] = 'Order not available';
            return $out;
        }

        $order->status = OnlineOrder::STATUS_COMPLETED;
        if ($order->save()) {
            $out['status'] = 'OK';
            $out['message'] = 'Order is completed successfully';
        }

        return $out;
    }

    /**
     * POST /v2/api/order/reprint?id=N
     *
     * The bill header and its tax summary, grouped by tax, item and HSN code.
     *
     * The id has no default, as in Yii 1, so a request without one is an error
     * on both stacks - each rendered by its own framework, so the suite
     * compares those two by status rather than by body.
     *
     * Two Yii 1 behaviours reproduced: an unknown id returns the bare envelope
     * with no taxes key, and an order whose lines produce no rows leaves
     * $taxarr undefined, so `taxes` comes back null and PHP 8 warns. The
     * grouped query had no ORDER BY until the MySQL 8 work; it is ordered by
     * the grouped columns on both stacks.
     */
    public function actionReprint($id)
    {
        $out = $this->envelope('reprint');

        $order = Order::findOne($id);
        if (!$order) {
            return $out;
        }

        $out['status'] = 'OK';

        $billPrefix = 'B';
        $outlet = Outlet::findOne($order->outlet_id);
        if ($outlet && $outlet->bill_prefix != '') {
            $billPrefix = $outlet->bill_prefix;
        }

        $out['bill_no'] = $billPrefix . '-' . $order->bill_no;
        $out['bill_date'] = date('d-m-Y', strtotime($order->bill_date));
        // read off Order, which typecasts; Yii 1 emits the stringified column
        $out['is_mobile'] = $order->is_mobile === null ? null : (string)$order->is_mobile;

        $items = OrderItem::find()
            ->select('SUM(qty) AS qty, SUM(tax_amount) AS tax_amount, SUM(cgst_amt) AS cgst_amt,'
                   . ' SUM(sgst_amt) AS sgst_amt, SUM(cess_amt) AS cess_amt, SUM(igst_amt) AS igst_amt, t.*')
            ->alias('t')
            ->joinWith('item item')
            ->where(['t.order_id' => $order->id])
            ->groupBy(['t.tax_id', 't.item_id', 'item.hsn_code'])
            ->orderBy('t.tax_id, t.item_id, item.hsn_code')
            ->all();

        // left null when there are no rows, as in Yii 1
        $taxes = null;
        foreach ($items as $item) {
            $taxes[] = $item->getTaxApiArray();
        }
        $out['taxes'] = $taxes;

        return $out;
    }
    /**
     * Notifies a set of devices, through the same stubbed transport the Yii 1
     * side uses. Returns nothing: neither caller looks at the result.
     */
    private function notifyDevices($sender, array $tokens, array $message)
    {
        if ($sender === null || empty($tokens)) {
            return;
        }
        $sender->sendGCM($tokens, $message);
    }

    /**
     * POST /v2/api/order/ship-order?id=N
     *
     * Marks an online order shipped, tells the webshop's dispatch endpoint, and
     * pushes a notification to the assigned rider's device.
     *
     * Order of operations is load-bearing and reproduced exactly: the dispatch
     * call goes out *before* the row is saved, and its response is neither
     * checked nor returned - the Yii 1 code that would have checked it is
     * commented out, so a failed dispatch still reports success. The push is
     * sent only after a successful save.
     *
     * Both outbound calls run through PosOutbound when stubbed.
     */
    public function actionShipOrder($id = null)
    {
        $out = $this->envelope('shipOrder');

        if ($this->headerUserId() === null) {
            $out['message'] = 'Please login';
            return $out;
        }

        $onlineOrder = OnlineOrder::findOne($id);
        if (!$onlineOrder) {
            $out['message'] = 'Online Order not available';
            return $out;
        }

        $onlineOrder->is_shipped = OnlineOrder::ORDER_SHIPPED;
        $onlineOrder->order_status = OnlineOrder::ORDERSTATUS_SHIPPED;

        $this->dispatchNotify('sendemailnotificationdelivery', $onlineOrder->order_id, '2');

        if ($onlineOrder->save()) {
            if ($onlineOrder->delivery_boy_id != '' && $onlineOrder->delivery_boy_id !== null) {
                $rider = User::find()
                    ->where(['id' => $onlineOrder->delivery_boy_id])
                    ->andWhere('device_token IS NOT NULL')
                    ->one();
                if ($rider) {
                    $this->notifyDevices($rider, [$rider->device_token], [
                        'body' => 'A new order is assigned',
                        'message' => 'A new order is assigned',
                        'title' => 'New Order',
                        'sound' => 'default',
                        'type' => 1,
                        'id' => 1,
                    ]);
                }
            }

            $out['status'] = 'OK';
            $out['message'] = 'Order is shipped successfully';
            $out['assigned'] = $onlineOrder->toApiArray();
        }

        return $out;
    }

    /**
     * POST /v2/api/order/complete-order?id=N
     *
     * The rider marks their own delivery complete. Only the assigned rider may:
     * anyone else gets "Online Order not assigned to you".
     *
     * Note this sets order_status, where orderUpdate() sets the text status
     * column - they are different fields and neither touches the other.
     *
     * The push goes to every role-7 user with a device token, sent as the
     * calling user. If the caller id in the header matches no user row that is
     * a call on null, and it fails the same way on both stacks.
     */
    public function actionCompleteOrder($id = null)
    {
        $out = $this->envelope('completeOrder');

        $loginId = $this->headerUserId();
        if ($loginId === null) {
            $out['message'] = 'Please login';
            return $out;
        }

        $loginUser = User::findOne($loginId);
        $onlineOrder = OnlineOrder::findOne($id);
        if (!$onlineOrder) {
            $out['message'] = 'Online Order not available';
            return $out;
        }

        if ($onlineOrder->delivery_boy_id != $loginId) {
            $out['message'] = 'Online Order not assigned to you';
            return $out;
        }

        $onlineOrder->order_status = OnlineOrder::ORDERSTATUS_COMPLETED;

        $this->dispatchNotify('sendemailnotificationcomplete', $onlineOrder->order_id, '3');

        if ($onlineOrder->save()) {
            $users = User::find()
                ->where('role_id = 7')
                ->andWhere('device_token IS NOT NULL')
                ->orderBy(['id' => SORT_ASC])
                ->all();

            if ($users) {
                $tokens = [];
                foreach ($users as $user) {
                    $tokens[] = $user->device_token;
                }
                $orderId = $onlineOrder->order_id;
                $this->notifyDevices($loginUser, $tokens, [
                    'body' => "$orderId order is completed",
                    'message' => "$orderId order is completed",
                    'title' => 'New Order',
                    'sound' => 'default',
                    'type' => 1,
                    'id' => 1,
                ]);
            }

            $out['status'] = 'OK';
            $out['message'] = 'Order is completed successfully';
            $out['assigned'] = $onlineOrder->toApiArray();
        }

        return $out;
    }

    /**
     * The webshop's dispatch-status callback. The shared key lives in the
     * environment now; it used to be a literal in this file and is therefore
     * in the repository's history, so it needs rotating.
     */
    private function dispatchNotify($endpoint, $orderId, $status)
    {
        $url = 'http://sect4.soulbowl.in/deliveryoption/index/' . $endpoint;
        $fields = [
            'sKeY' => getenv('POS_SOULBOWL_KEY'),
            // string, as Yii 1 sends it straight from a stringified fetch
            'order_id' => (string)$orderId,
            'action' => 'update_dispatch_status',
            'status' => $status,
        ];

        if (class_exists('PosOutbound') && PosOutbound::isStubbed()) {
            return PosOutbound::intercept(PosOutbound::CHANNEL_HTTP, 'POST ' . $url, $fields);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $body = curl_exec($ch);
        curl_close($ch);

        return json_decode($body, true);
    }
}
