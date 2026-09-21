<?php
namespace app\controllers;

use Yii;
use app\models\Emp;
use app\models\Item;
use app\models\ItemDetail;
use app\models\Outlet;
use app\models\PurchaseBill;
use app\models\PurchaseBillDetail;
use app\models\User;
use app\models\StockAdjustLog;
use app\models\ScannedItems;
use app\models\Tax;
use app\models\ItemStock;
use app\models\ItemVendor;
use app\models\MrsAdjust;
use app\models\Organization;
use app\models\Mrs;
use app\models\MrsDetail;
use app\models\Mrn;
use app\models\StockLog;
use app\models\Order;
use app\models\OrderItem;
use app\models\OrderHold;
use app\models\OrderHoldItem;
use app\models\Customer;
use app\models\CreditNote;
use app\models\OnlineOrder;
use app\models\Discount;
use app\components\InteraktApi;
use yii\web\Controller;
use yii\web\Response;

/**
 * Partial Yii 2 port of protected/modules/api/controllers/ItemController.php.
 *
 * The Yii 1 controller is 2,343 lines over fourteen actions. Eight are ported.
 *
 * Ported: list        - the catalogue feed, built on ItemDetail::toonlineArray()
 *         getItem     - a single item by barcode, or the first 50, built on
 *                       ItemDetail::toArray()
 *         search      - the same payload, filtered by name/title/rate
 *         getGRN      - the caller's outlet's unapproved purchase bills
 *         getGRNItems - the lines of one purchase bill
 *         billUpdate  - see the note on the action; it is one hardcoded row
 *         adjustitemtozero - writes a stock adjustment log
 *         scannedItem - records what a till scanned
 *
 * Not ported - the POS transaction paths:
 *   order (349 lines), adjust (412), punchorder (481), ordertest (329),
 *   updateStock (129), barcode (62)
 *
 *   The first five create orders and move stock, across several models that do
 *   not exist on this side yet (StockLog, Mrs, MrsDetail, Mrn), and they carry
 *   most of this controller's cURL calls. They want a fixture-based harness of
 *   their own rather than being folded in with the endpoints around them.
 *   barcode is small but needs ItemDetail::toArray() plus the stockAdjustLogs
 *   and itemVendors relations.
 *
 * Yii 1 action ids are camelCase; the Yii 2 routes are hyphenated, so
 * /api/item/scannedItem is /v2/api/item/scanned-item.
 *
 * Several Yii 1 behaviours are reproduced rather than corrected, each
 * commented at its call site and written up in docs/live-bugs-found.md:
 * billUpdate mutates one hardcoded row for any caller, adjustitemtozero logs
 * every adjustment against the first outlet whatever outlet it happened at,
 * and scannedItem reads two request keys without checking them, so a request
 * missing either returns a 500 rather than the "details are missing" reply the
 * code appears to offer.
 */
class ItemController extends Controller
{
    public $enableCsrfValidation = false;

    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    /**
     * POST /v2/api/item/list
     *
     * Reproduces the Yii 1 paging exactly, quirks included: it sets a limit of
     * 50, counts with that criteria, then hands the count to CPagination with a
     * pageSize of 10 - whose applyLimit() overwrites the limit with 10 and sets
     * the offset from the page parameter. The status filter is added after the
     * count, so the count and the filtered rows disagree. All reproduced rather
     * than corrected, since the count is not returned to the caller anyway.
     *
     * The Yii 1 query has no ORDER BY, leaving the page contents to MySQL.
     * Ordered by id on both sides.
     */
    public function actionList()
    {
        $out = [
            'controller' => 'item',
            'action' => 'list',
            'status' => 'NOK',
        ];

        $page = (int)Yii::$app->request->get('page', 1);
        if ($page < 1) {
            $page = 1;
        }
        $pageSize = 10;

        $rows = ItemDetail::find()
            ->where(['status' => ItemDetail::STATUS_ACTIVE])
            ->orderBy(['id' => SORT_ASC])
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->all();

        $list = [];
        foreach ($rows as $row) {
            $list[] = $row->toOnlineApiArray();
        }

        // Yii 1 reports OK even when the list is empty.
        $out['status'] = 'OK';
        $out['item'] = $list;
        return $out;
    }

    /**
     * POST /v2/api/item/get-item?code=BARCODE
     *
     * With a code, returns that one active item. The code may carry an
     * overriding price after a '!' - "12345!99.50" - which is used in place of
     * the item's own sale rate throughout the pricing calculation.
     *
     * With no code, returns the first 50 active items.
     *
     * Neither query in the Yii 1 version has an ORDER BY, so which 50 items
     * came back was left to MySQL. Ordered by id on both sides.
     */
    public function actionGetItem($code = null)
    {
        $out = [
            'controller' => 'item',
            'action' => 'getItem',
            'status' => 'NOK',
        ];

        if ($code !== null && $code !== '') {
            $price = null;
            $parts = explode('!', $code, 2);
            if (isset($parts[0])) {
                $code = $parts[0];
            }
            if (isset($parts[1])) {
                $price = $parts[1];
            }

            $itemDetail = ItemDetail::find()
                ->where(['bar_code' => $code, 'status' => ItemDetail::STATUS_ACTIVE])
                ->orderBy(['id' => SORT_ASC])
                ->one();

            if ($itemDetail) {
                $out['status'] = 'OK';
                $out['item'] = [$itemDetail->toApiArray($price)];
            }
            // No match leaves the NOK envelope with no message, as in Yii 1.
            return $out;
        }

        $rows = ItemDetail::find()
            ->where(['status' => ItemDetail::STATUS_ACTIVE])
            ->orderBy(['id' => SORT_ASC])
            ->limit(50)
            ->all();

        $list = [];
        foreach ($rows as $row) {
            $list[] = $row->toApiArray();
        }
        $out['status'] = 'OK';
        $out['item'] = $list;
        return $out;
    }

    /**
     * POST /v2/api/item/search?name=&title=&rate=
     *
     * name matches the start of the item title, title matches anywhere in it,
     * and supplying both requires both. rate is a partial match against
     * sale_price, reproducing Yii 1's compare(..., true).
     *
     * Only rows with stock are returned - checkStock() treats the first active
     * detail of each item as always available. Capped at 50 before that filter,
     * so fewer than 50 may come back.
     *
     * The Yii 1 query has no ORDER BY; ordered by id on both sides.
     */
    public function actionSearch($name = null, $rate = null, $title = null)
    {
        $out = [
            'controller' => 'item',
            'action' => 'search',
            'status' => 'NOK',
        ];

        // Aliases are explicit: Yii 1 aliases a joined table by the relation
        // name ("item"), Yii 2 by the table name ("tbl_item"). Naming both
        // keeps the conditions readable and identical to the Yii 1 ones.
        $query = ItemDetail::find()
            ->alias('t')
            ->joinWith(['item item'], true, 'INNER JOIN')
            ->andWhere(['item.status' => Item::STATUS_ACTIVE])
            ->andWhere(['t.status' => ItemDetail::STATUS_ACTIVE])
            ->orderBy(['t.id' => SORT_ASC])
            ->limit(50);

        $name = ($name === null || $name === '') ? null : $name;
        $title = ($title === null || $title === '') ? null : $title;

        if ($name !== null && $title !== null) {
            $query->andWhere(['like', 'item.title', trim($name) . '%', false])
                  ->andWhere(['like', 'item.title', '%' . trim($title) . '%', false]);
        } elseif ($title !== null) {
            $query->andWhere(['like', 'item.title', '%' . trim($title) . '%', false]);
        } elseif ($name !== null) {
            $query->andWhere(['like', 'item.title', trim($name) . '%', false]);
        }

        if ($rate !== null && $rate !== '') {
            $query->andWhere(['like', 'item.sale_price', $rate]);
        }

        $rows = $query->all();

        // Yii 1 only reports OK when the query returned rows, and builds the
        // list from those with stock - so a non-empty result whose rows all
        // lack stock yields OK with an empty list.
        if (empty($rows)) {
            return $out;
        }

        $list = [];
        foreach ($rows as $row) {
            if ($row->checkStock() > 0) {
                $list[] = $row->toApiArray();
            }
        }

        $out['status'] = 'OK';
        $out['item'] = $list;
        return $out;
    }
    /** The response envelope every action in this controller starts from. */
    private function envelope($action)
    {
        return [
            'controller' => 'item',
            'action' => $action,
            'status' => 'NOK',
        ];
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
     * POST /v2/api/item/get-grn
     *
     * The unapproved purchase bills for the outlet the caller belongs to, as
     * bare ids. The outlet comes from the caller's employee record; if the user
     * has no employee row, Yii 1 falls back to whichever outlet the database
     * returns first, and if there are no outlets at all it goes on to use an
     * undefined $outlet_id - a 500 on PHP 8. Reproduced, since a deployment
     * with no outlets is not a real state.
     *
     * status stays 'NOK' when the caller is unknown or has no bills, and the
     * 'grns' key is absent - not an empty list.
     *
     * The Yii 1 query had no ORDER BY. Ordered by id on both sides.
     */
    public function actionGetGrn()
    {
        $out = [
            'controller' => 'item',
            'action' => 'getGRN',
            'status' => 'NOK',
        ];

        $loginId = $this->headerUserId();
        if ($loginId === null) {
            return $out;
        }

        $user = User::findOne($loginId);
        if (!$user) {
            return $out;
        }

        $emp = Emp::findOne($user->emp_id);
        if ($emp) {
            $outletId = $emp->outlet_id;
        } else {
            $outlet = Outlet::find()->orderBy(['id' => SORT_ASC])->one();
            if ($outlet) {
                $outletId = $outlet->id;
            }
        }

        $bills = PurchaseBill::find()
            ->where([
                'outlet_id' => $outletId,
                'status' => PurchaseBill::STATUS_UNAPPROVED,
            ])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        if ($bills) {
            $list = [];
            foreach ($bills as $bill) {
                $list[] = ['id' => (string)$bill->id];   // string, as Yii 1 returns
            }
            $out['status'] = 'OK';
            $out['grns'] = $list;
        }

        return $out;
    }

    /**
     * POST /v2/api/item/getGRNItems?id=N, as Yii 1 spells it
     *
     * The lines of one purchase bill. Yii 1 loads the bill itself into a
     * variable it never uses, and logs the id at warning level on every call;
     * neither is reproduced, as neither is observable in the response.
     *
     * The Yii 1 query had no ORDER BY. Ordered by id on both sides.
     */
    public function actionGetGrnItems($id)
    {
        $out = [
            'controller' => 'item',
            'action' => 'getGRNItems',
            'status' => 'NOK',
        ];

        if ($id === null) {
            return $out;
        }

        $details = PurchaseBillDetail::find()
            ->where(['purchase_bill_id' => $id])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        if ($details) {
            $list = [];
            foreach ($details as $detail) {
                $list[] = $detail->toApiArray();
            }
            $out['status'] = 'OK';
            $out['items'] = $list;
        }

        return $out;
    }
    /**
     * POST /v2/api/item/bill-update
     *
     * Sets a purchase bill back to unapproved, so it can be received again.
     *
     * It used to load bill 97 - a literal id in the source - and flip that one
     * row for any caller, answering OK whether or not the row existed or the
     * save worked. It takes purchase_bill_id from the request now and reports
     * what happened. Changed on both stacks at the owner's request; the old
     * behaviour is in docs/live-bugs-found.md.
     *
     * Still no authentication: that was not part of the change.
     */
    public function actionBillUpdate()
    {
        $out = $this->envelope('billUpdate');

        $post = Yii::$app->request->post();
        $billId = isset($post['purchase_bill_id'])
            ? $post['purchase_bill_id']
            : Yii::$app->request->get('purchase_bill_id');

        if ($billId === null || $billId === '') {
            $out['message'] = 'purchase_bill_id is required';
            return $out;
        }

        $purchaseBill = PurchaseBill::findOne($billId);
        if (!$purchaseBill) {
            $out['message'] = 'Purchase bill not found';
            return $out;
        }

        $purchaseBill->status = PurchaseBill::STATUS_UNAPPROVED;
        if ($purchaseBill->save()) {
            $out['status'] = 'OK';
        }

        return $out;
    }

    /**
     * POST /v2/api/item/adjustitemtozero
     *
     * Writes a stock adjustment log that zeroes an item detail's counted stock.
     * It records the adjustment; it does not change tbl_item_stock.
     *
     * The outlet is not a parameter - Yii 1 takes the first outlet by id and
     * uses that for every adjustment, whichever outlet the caller is at. Kept,
     * and noted, because changing it would change what gets logged.
     */
    public function actionAdjustitemtozero()
    {
        $out = $this->envelope('adjustitemtozero');

        $post = Yii::$app->request->post();
        if (!isset($post['itemdetail_id']) || !isset($post['user_id'])) {
            $out['message'] = 'Please pass required parameters';
            return $out;
        }

        $itemDetail = ItemDetail::findOne($post['itemdetail_id']);
        if (!$itemDetail) {
            $out['message'] = 'Item not found with the provided ID.';
            return $out;
        }

        $outletId = null;
        $outlet = Outlet::find()->orderBy(['id' => SORT_ASC])->one();
        if ($outlet) {
            $outletId = $outlet->id;
        }

        // Yii 1 does not check this lookup before reading ->id, so an item
        // detail pointing at a missing item is a fatal on both stacks.
        $item = Item::findOne($itemDetail->item_id);

        $log = new StockAdjustLog();
        $log->date = date('Y-m-d');
        $log->item_detail_id = $itemDetail->id;
        $log->item_id = $item->id;
        $log->mrp = $itemDetail->getItemDetailMrp();
        $log->outlet_id = $outletId;
        $log->current_stock = 0;
        $log->actual_stock = 0;
        $log->adjusted = 0;
        $log->create_user_id = $post['user_id'];
        $log->remarks = 'Reset';

        if ($log->save()) {
            $out['status'] = 'OK';
            $out['message'] = 'Stock adjusted to zero.';
        }

        return $out;
    }

    /**
     * POST /v2/api/item/scanned-item
     *
     * Records what a till scanned, for later analysis. Each element of the
     * posted `items` JSON becomes a row; the whole element is also stored
     * verbatim in item_detail.
     *
     * Yii 1 saves each row with validation on and ignores the result, so a row
     * that fails validation is dropped silently and the response is still OK.
     * It also reads $_POST['user_email'] and each element's keys without
     * checking them, which warns on PHP 8. Both reproduced.
     */
    public function actionScannedItem()
    {
        $out = $this->envelope('scannedItem');

        $post = Yii::$app->request->post();

        // Written the way Yii 1 writes it, unchecked reads and all: it tests
        // $post['computer_name'] and $post['user_id'] without isset, so a
        // request missing either is a PHP 8 warning and a 500 rather than the
        // "details are missing" reply it looks like it would give. count() on
        // a failed json_decode is a TypeError for the same reason. Guarding
        // them here would make the ported endpoint behave better than the one
        // it replaces, which is not what this port is for.
        if (!empty($post['items']) && count(json_decode($post['items'], true)) > 0
            && $post['computer_name'] && $post['user_id']) {
            $items = json_decode($post['items'], true);
        } else {
            $out['message'] = 'No item provided or some details are missing';
            return $out;
        }

        foreach ($items as $item) {
            $model = new ScannedItems();
            $model->user_id = $post['user_id'];
            $model->computer_name = $post['computer_name'];
            $model->user_email = $post['user_email'];
            $model->item_id = $item['item_id'];
            $model->bar_code = $item['bar_code'];
            $model->is_coupon = $item['is_coupon'];
            $model->qty = $item['qty'];
            $model->sale_rate = $item['sale_rate'];
            $model->base_price = $item['base_price'];
            $model->mrp = $item['mrp'];
            $model->item_detail = json_encode($item);
            $model->created_at = date('Y-m-d H:i:s', strtotime($post['created_at']));
            $model->save();
        }

        $out['status'] = 'OK';
        return $out;
    }
    /**
     * POST /v2/api/item/barcode
     *
     * One item detail by bar code, with the last stock adjustment and the
     * first vendor bolted onto the payload.
     *
     * Yii 1 builds a CDbCriteria with an order and a limit just above and then
     * throws it away, calling findByAttributes() instead - so the row is
     * whichever one MySQL returns first for that bar code. Ordered by id here,
     * and on the Yii 1 side, so the two agree on which.
     */
    public function actionBarcode()
    {
        $out = $this->envelope('barcode');

        $post = Yii::$app->request->post();
        if (empty($post['barcode'])) {
            $out['message'] = 'Please add barcode to url';
            return $out;
        }

        $itemDetail = ItemDetail::find()
            ->where(['bar_code' => $post['barcode']])
            ->orderBy(['id' => SORT_ASC])
            ->one();

        if (!$itemDetail) {
            $out['message'] = 'item not found';
            return $out;
        }

        $out['status'] = 'OK';
        $row = $itemDetail->toApiArray();

        $row['last_adjust_time'] = '';
        $row['last_adjust_by_username'] = '';
        $logs = $itemDetail->stockAdjustLogs;
        if (count($logs) > 0) {
            $row['last_adjust_time'] = $logs[0]->create_time;
            if ($logs[0]->createUser && $logs[0]->createUser->username) {
                $row['last_adjust_by_username'] = $logs[0]->createUser->username;
            }
        }

        $row['vendor_id'] = '';
        $row['vendor_name'] = '';
        $item = $itemDetail->item;
        if ($item && count($item->itemVendors) > 0 && $item->itemVendors[0]->vendor) {
            // Yii 1 emits the stringified column; Yii 2's AR casts it to int
            $row['vendor_id'] = (string)$item->itemVendors[0]->vendor->id;
            $row['vendor_name'] = $item->itemVendors[0]->vendor->name;
        }

        $out['barcode_item'] = $row;
        return $out;
    }
    /**
     * POST /v2/api/item/update-stock
     *
     * Receives a GRN: marks the purchase bill received and rewrites each of its
     * detail lines with the quantity actually counted, the recomputed tax and
     * the margin.
     *
     * It does NOT add stock, despite the name. The ItemStock row is built here
     * exactly as Yii 1 builds it, and then not saved - because in Yii 1 the
     * save is commented out:
     *
     *     //if ($model->save ()) {
     *     ...
     *     $arr ['status'] = 'OK';
     *     //}
     *
     * along with the StockLog write below it. So a received GRN updates the
     * bill and its lines and leaves the stock level alone, and still answers
     * OK. The row is built here rather than skipped so that the two stacks stay
     * line-for-line comparable if that save is ever uncommented. Recorded in
     * docs/live-bugs-found.md.
     *
     * Reproduced faults: the purchase bill is read without a null check, so an
     * unknown purchase_bill_id is a fatal; and $itemdetail->id is read one line
     * before the `if ($itemdetail && ...)` that tests it, so a bar code
     * matching nothing is a fatal too. Both behave the same on either stack.
     */
    public function actionUpdateStock()
    {
        $out = $this->envelope('updateStock');

        $post = Yii::$app->request->post();
        if (!isset($post['stock_details'])) {
            return $out;
        }

        $loginId = $this->headerUserId();
        if (!$loginId) {
            return $out;
        }

        $stocks = json_decode($post['stock_details']);
        if (!$stocks || !isset($stocks[0])) {
            return $out;
        }

        // no null check, as in Yii 1
        $firstBill = PurchaseBill::findOne($stocks[0]->purchase_bill_id);

        if ($firstBill->status != PurchaseBill::STATUS_UNAPPROVED) {
            $out['status'] = 'OK';
            $out['message'] = 'GRN is already received';
            return $out;
        }

        $firstBill->status = PurchaseBill::STATUS_RECEIVED;
        $firstBill->save();

        foreach ($stocks as $stock) {
            if (!isset($stock->bar_code) || !isset($stock->qty) || !isset($stock->purchase_bill_id)) {
                continue;
            }

            // Yii 1 uses compare(), which drops the condition when the bar code
            // is empty - so an empty one matches the first detail rather than
            // none. Unordered there; ordered by id on both sides now.
            $itemDetail = ItemDetail::find()->orderBy(['id' => SORT_ASC]);
            if ($stock->bar_code !== null && $stock->bar_code !== '') {
                $itemDetail->andWhere(['bar_code' => $stock->bar_code]);
            }
            $itemDetail = $itemDetail->one();

            $purchaseBill = PurchaseBill::findOne($stock->purchase_bill_id);

            // $itemDetail->id, before the null test below - as in Yii 1
            $billDetail = PurchaseBillDetail::find()
                ->where([
                    'purchase_bill_id' => $stock->purchase_bill_id,
                    'item_detail_id' => $itemDetail->id,
                ])
                ->orderBy(['id' => SORT_ASC])
                ->one();

            $batchNo = isset($stock->batch_no) ? $stock->batch_no : User::randomBarcode('5');

            if (!$itemDetail || !$billDetail) {
                continue;
            }

            $tax = Tax::findOne($billDetail->tax_id);
            if ($tax) {
                $billDetail->cgst_amt = ($stock->qty * $billDetail->price) * ($billDetail->cgst_per / 100);
                $billDetail->sgst_amt = ($stock->qty * $billDetail->price) * ($billDetail->sgst_per / 100);
                $billDetail->cess_amt = ($stock->qty * $billDetail->price) * ($billDetail->cess_per / 100);
                $billDetail->igst_amt = ($stock->qty * $billDetail->price) * ($billDetail->igst_per / 100);
            }

            if ($billDetail->getGstTrue($stock->purchase_bill_id) == true) {
                $billDetail->amount = ($stock->qty * $billDetail->price)
                    + $billDetail->cgst_amt + $billDetail->sgst_amt + $billDetail->cess_amt;
                $calculatedGst = ($billDetail->price * $billDetail->cgst_per) / 100
                    + ($billDetail->price * $billDetail->sgst_per) / 100
                    + ($billDetail->price * $billDetail->cess_per) / 100;
            } else {
                $billDetail->amount = ($stock->qty * $billDetail->price) + $billDetail->igst_amt;
                $calculatedGst = ($billDetail->price * $billDetail->igst_per) / 100;
            }

            // a string comparison in Yii 1, so a price of '0.000' still divides
            if ($billDetail->price != '0.00') {
                $billDetail->margin = ($billDetail->mrp - ($billDetail->price + $calculatedGst))
                    * 100 / ($billDetail->price + $calculatedGst);
            }

            $billDetail->approved_qty = $stock->qty;
            $billDetail->order = $stock->entry_position;
            $billDetail->save();

            $itemStock = ItemStock::find()
                ->where([
                    'item_detail_id' => $itemDetail->id,
                    'item_id' => $itemDetail->item_id,
                    'batch_number' => $batchNo,
                ])
                ->orderBy(['id' => SORT_ASC])
                ->one();

            if ($itemStock === null) {
                $itemStock = new ItemStock();
                $purchaseQty = $stock->qty;
                $balanceQty = $stock->qty;
            } else {
                $purchaseQty = $itemStock->purchase_qty + $stock->qty;
                $balanceQty = $itemStock->balance_qty + $stock->qty;
            }

            $itemStock->batch_number = $batchNo;
            $itemStock->item_detail_id = $itemDetail->id;
            $itemStock->base_price = $billDetail->price;
            $itemStock->mrp = $billDetail->mrp;
            $itemStock->vendor_id = $purchaseBill->vendor_id;
            $itemStock->outlet_id = $billDetail->outlet_id;
            $itemStock->tax_id = $billDetail->tax_id;
            $itemStock->item_id = $itemDetail->item_id;
            $itemStock->purchase_qty = $purchaseQty;
            $itemStock->balance_qty = $balanceQty;
            $itemStock->create_user_id = $loginId;

            // deliberately not saved - see the docblock

            $out['status'] = 'OK';
        }

        return $out;
    }
    /**
     * POST /v2/api/item/adjust
     *
     * A stocktake adjustment for one item detail: sets the stock to the counted
     * figure, logs it twice (StockAdjustLog and StockLog), touches the item and
     * its detail, and then either cancels a pending requisition for the item -
     * if the adjustment took it back above its reorder level - or raises one, if
     * it took it below.
     *
     * saleStatus (config/params.php, currently true) decides how qty and
     * remain_qty combine. With it on, the larger of the two wins and the
     * difference is the adjustment; with it off the posted qty is added as-is.
     *
     * Three places read a property off something that may be null, and are a
     * fatal on PHP 8 rather than the notice they were on 5.6. All three are
     * reproduced, because each one is reachable only with data this endpoint
     * would not normally be given, and guarding them would change what a caller
     * sees:
     *
     *   - $itemStock->vendor_id is written before $itemStock is known to exist,
     *     so an item detail with no stock row at this outlet is a fatal;
     *   - so is $ItemVendor->vendor_id, when the item has no vendor row;
     *   - $vendorMRS->id in the create-a-requisition branch, when the vendor has
     *     no requisition at all.
     *
     * Recorded in docs/live-bugs-found.md.
     *
     * Note the item-vendor lookup matches item_detail_id against the *item's*
     * id, the same mismatch already noted on Item::getItemVendors().
     */
    public function actionAdjust()
    {
        $out = $this->envelope('adjust');

        $post = Yii::$app->request->post();
        foreach (['itemdetail_id', 'qty', 'remain_qty', 'remark', 'original_item_id', 'user_id'] as $k) {
            if (!isset($post[$k])) {
                return $out;
            }
        }

        $userId = $post['user_id'];
        $itemDetailId = $post['itemdetail_id'];
        $qty = $post['qty'];
        $remainQty = $post['remain_qty'];
        $remarks = !empty($post['remark']) ? $post['remark'] : ' ';

        if ($itemDetailId == '' || $qty == '') {
            return $out;
        }

        $saleStatus = (Yii::$app->params['saleStatus'] ?? null);

        $outletModel = Outlet::find()->orderBy(['id' => SORT_ASC])->one();
        $outlet = $outletModel ? $outletModel->id : null;

        $itemDetail = ItemDetail::findOne($itemDetailId);
        $current = '0.000';
        $adjusted = '0.000';
        $actual = '0.000';

        if (!$itemDetail) {
            return $out;
        }

        $itemStock = ItemStock::find()
            ->where(['item_detail_id' => $itemDetail->id, 'outlet_id' => $outlet])
            ->orderBy(['id' => SORT_ASC])
            ->one();

        // both of these are read without a null check in Yii 1
        $itemVendor = ItemVendor::find()
            ->where(['item_detail_id' => $itemDetail->item_id])
            ->orderBy(['id' => SORT_ASC])
            ->one();
        $itemStock->vendor_id = $itemVendor->vendor_id;

        $item = Item::findOne($itemDetail->item_id);

        if ($saleStatus) {
            if ($remainQty > $qty) {
                $type = ItemStock::TYPE_SUBSTRACT;
                $qty = $remainQty - $qty;
            } else {
                $type = ItemStock::TYPE_ADDED;
                $qty = $qty - $remainQty;
            }
        } else {
            $type = ItemStock::TYPE_ADDED;
        }

        if ($itemStock === null) {
            $itemStock = new ItemStock();
            if ($type == ItemStock::TYPE_ADDED) {
                $itemStock->purchase_qty = $itemStock->purchase_qty + $qty;
                $itemStock->balance_qty = $itemStock->balance_qty + $qty;
            } else {
                $itemStock->purchase_qty = $qty;
                $itemStock->balance_qty = $qty;
            }
            $itemStock->batch_number = User::randomBarcode('5');
            $itemStock->item_detail_id = $itemDetail->id;
            if ($item) {
                $itemStock->item_id = $item->id;
                $itemStock->mrp = $itemDetail->getItemDetailMrp();
                $itemStock->base_price = $item->purchase_price;
                $itemStock->outlet_id = $outlet;
            }
        } else {
            if ($type == ItemStock::TYPE_ADDED) {
                $itemStock->purchase_qty = $itemStock->purchase_qty + $qty;
                $itemStock->balance_qty = $itemStock->balance_qty + $qty;
            } else {
                $itemStock->balance_qty = $itemStock->balance_qty - $qty;
            }
        }

        $current = $item->getOutletTotalRemainingQuantity($itemDetail->id, $outlet);

        if ($type == ItemStock::TYPE_ADDED) {
            $actual = bcadd((string)$current, (string)$qty, 3);
        } else {
            // three separate ifs in Yii 1, not a chain, and none covers
            // $current == 0 together with the others - so a zero current stock
            // takes the first branch only.
            if ($current == 0) {
                $actual = bcsub((string)$current, (string)$qty, 3);
            }
            if ($current < 0) {
                $actual = '-' . bcadd((string)$current, (string)$qty, 3);
            }
            if ($current > 0) {
                if ($current > $qty) {
                    $actual = bcsub((string)$current, (string)$qty, 3);
                } else {
                    $actual = bcsub((string)$qty, (string)$current, 3);
                }
            }
        }

        $adjusted = ($type == ItemStock::TYPE_ADDED) ? $qty : '-' . $qty;

        if (!$itemStock->save()) {
            return $out;
        }

        $mrsAdjust = MrsAdjust::find()
            ->where(['status' => MrsAdjust::STATUS_PENDING, 'item_id' => $itemStock->item_id])
            ->orderBy(['id' => SORT_DESC])
            ->one();
        if ($mrsAdjust) {
            $mrsAdjust->status = MrsAdjust::STATUS_DONE;
            $mrsAdjust->save(false, ['status']);
        }

        $itemDetail->update_time = date('Y-m-d H:i:s');
        $itemDetail->save(false, ['update_time']);
        $item->update_time = date('Y-m-d H:i:s');
        $item->save(false, ['update_time']);

        $log = new StockAdjustLog();
        $log->date = date('Y-m-d');
        $log->item_detail_id = $itemDetail->id;
        $log->item_id = $item->id;
        $log->mrp = $itemDetail->getItemDetailMrp();
        $log->outlet_id = $outlet;
        $log->current_stock = $current;
        $log->actual_stock = $actual;
        $log->adjusted = $adjusted;
        $log->create_user_id = $userId;
        $log->remarks = $remarks;

        if (!$log->save()) {
            $out['message'] = 'last else';
            return $out;
        }

        $stockLog = new StockLog();
        $stockLog->item_detail_id = $itemDetail->id;
        $stockLog->item_id = $item->id;
        $stockLog->batch_no = $itemStock->batch_number;
        $stockLog->current_qty = $itemDetail->getStockQty();
        $stockLog->previous_qty = ($type == ItemStock::TYPE_ADDED)
            ? bcsub((string)$itemDetail->getStockQty(), (string)$qty, 3)
            : bcadd((string)$itemDetail->getStockQty(), (string)$qty, 3);
        $stockLog->Qty = $adjusted;
        $stockLog->outlet_id = $outlet;
        $stockLog->vendor_id = $itemStock->vendor_id;
        $stockLog->type_id = StockLog::TYPE_ADJUSTED;

        if (!$stockLog->save()) {
            $out['message'] = 'second last else';
            return $out;
        }

        $out['message'] = 'save successfully';
        $out['status'] = 'OK';

        $mrsItemStock = ItemStock::find()
            ->select('SUM(balance_qty) AS balance_qty')
            ->where(['item_id' => $itemDetail->item_id])
            ->groupBy('item_id')
            ->one();

        $remaining = $item->getTotalRemainingQuantity();

        if ($remaining > $item->min_qty) {
            $this->cancelPendingRequisitions($item);
        } else {
            $this->raiseRequisition($item, $itemDetail, $itemStock, $outlet, $mrsItemStock, $out);
        }

        return $out;
    }

    /**
     * The adjustment took the item back above its reorder level, so the pending
     * requisition lines for it go - and the requisition itself, if that was its
     * only line and no goods receipt exists against it.
     */
    private function cancelPendingRequisitions($item)
    {
        $mrsDetails = MrsDetail::find()
            ->where(['item_id' => $item->id, 'status' => Mrs::STATUS_PENDING])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        foreach ($mrsDetails as $mrsDetail) {
            $mrsId = $mrsDetail->mrs_id;
            $lineCount = MrsDetail::find()->where(['mrs_id' => $mrsDetail->mrs_id])->count();
            $mrs = Mrs::findOne($mrsId);

            if ($mrs && $item->id == $mrsDetail->item_id && $mrs->status != Mrs::STATUS_DONE) {
                $mrsDetail->delete();
            }

            if ($lineCount == 1) {
                $mrs = Mrs::findOne($mrsId);
                if ($mrs && $item->id == $mrsDetail->item_id && $mrs->status != Mrs::STATUS_DONE) {
                    $mrn = Mrn::find()->where(['mrs_id' => $mrs->id])->orderBy(['id' => SORT_ASC])->one();
                    if (!$mrn) {
                        $mrs->delete();
                    }
                }
            }
        }
    }

    /**
     * The adjustment took the item below its reorder level, so a requisition is
     * raised - or an existing pending one for the same vendor and outlet is
     * reused.
     *
     * $vendorMRS->id is read without a null check, as in Yii 1: a vendor with no
     * requisition at all is a fatal.
     */
    private function raiseRequisition($item, $itemDetail, $itemStock, $outlet, $mrsItemStock, &$out)
    {
        $vendorMRS = Mrs::find()
            ->where('vendor_id = :v', [':v' => $itemStock->vendor_id])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        if (!$vendorMRS->id) {
            return;
        }

        $item = Item::findOne($item->id);

        $existingLine = MrsDetail::find()
            ->where('mrs_id = :m', [':m' => $vendorMRS->id])
            ->andWhere('item_id = :i', [':i' => $item->id])
            ->orderBy(['id' => SORT_DESC])
            ->one();
        if (!empty($existingLine)) {
            return;
        }

        $organization = Organization::find()->orderBy(['id' => SORT_ASC])->one();
        $detailAgain = ItemDetail::findOne($itemDetail->id);
        $tax = null;
        if ($detailAgain) {
            $tax = Tax::findOne($detailAgain->tax_id);
        }

        if ($itemStock->vendor_id === null) {
            return;
        }

        $mrs = Mrs::find()
            ->where(['status' => Mrs::STATUS_PENDING, 'vendor_id' => $itemStock->vendor_id, 'outlet_id' => $outlet])
            ->orderBy(['id' => SORT_ASC])
            ->one();

        $reorderQty = $item->reorder_qty != '' ? $item->reorder_qty : 10;
        $maxQty = $item->max_qty != '' ? $item->max_qty : 10;
        $minQty = $item->min_qty != '' ? $item->min_qty : 10;

        if (!($minQty >= $mrsItemStock->balance_qty)) {
            return;
        }

        if ($mrs === null) {
            $mrs = new Mrs();
        }
        $mrs->code = 'ddd';
        $mrs->mrs_date = date('Y-m-d');
        $mrs->mrs_req_date = date('Y-m-d');
        $mrs->outlet_id = $outlet;
        $mrs->vendor_id = $itemStock->vendor_id;
        $mrs->organization_id = $organization->id;

        if (!$mrs->save()) {
            return;
        }

        $mrsDetail = MrsDetail::find()
            ->where(['item_detail_id' => $itemStock->item_detail_id, 'mrs_id' => $mrs->id])
            ->orderBy(['id' => SORT_ASC])
            ->one();
        if ($mrsDetail === null) {
            $mrsDetail = new MrsDetail();
        }

        $mrsDetail->price = $item->purchase_price;
        $mrsDetail->req_qty = $maxQty;
        $mrsDetail->approved_qty = $reorderQty;
        $mrsDetail->min_qty = $minQty;

        if ($tax) {
            $mrsDetail->cgst_per = $tax->tax_val1;
            $mrsDetail->sgst_per = $tax->tax_val2;
            $mrsDetail->cess_per = $tax->tax_val3;
            $mrsDetail->igst_per = $tax->tax_val4;
            $mrsDetail->cgst_amt = ($reorderQty * $mrsDetail->price) * ($tax->tax_val1 / 100);
            $mrsDetail->sgst_amt = ($reorderQty * $mrsDetail->price) * ($tax->tax_val2 / 100);
            $mrsDetail->cess_amt = ($reorderQty * $mrsDetail->price) * ($tax->tax_val3 / 100);
            $mrsDetail->igst_amt = ($reorderQty * $mrsDetail->price) * ($tax->tax_val4 / 100);
            $mrsDetail->tax_id = $tax->id;
        }

        $mrsDetail->item_detail_id = $itemDetail->id;
        $mrsDetail->item_id = $itemDetail->item_id;
        $mrsDetail->outlet_id = $mrs->outlet_id;
        $mrsDetail->mrp = $detailAgain->getItemDetailMrp();
        $mrsDetail->sale_rate = $detailAgain->getItemDetailSaleRate();
        $mrsDetail->mrs_id = $mrs->id;
        $mrsDetail->discount = '0.00';
        $mrsDetail->discount_amt = '0.00';
        $mrsDetail->other_charge = '0.00';

        if ($mrsDetail->getGstTrue($mrs->id) == true) {
            $mrsDetail->amount = ($reorderQty * $mrsDetail->price)
                + $mrsDetail->cgst_amt + $mrsDetail->sgst_amt + $mrsDetail->cess_amt;
            $calculatedGst = ($mrsDetail->price * $mrsDetail->cgst_per) / 100
                + ($mrsDetail->price * $mrsDetail->sgst_per) / 100
                + ($mrsDetail->price * $mrsDetail->cess_per) / 100;
        } else {
            $mrsDetail->amount = ($reorderQty * $mrsDetail->price) + $mrsDetail->igst_amt;
            $calculatedGst = ($mrsDetail->price * $mrsDetail->igst_per) / 100;
        }

        if ($mrsDetail->price != '0.00' && $mrsDetail->price !== null) {
            $mrsDetail->margin = ($mrsDetail->mrp - ($mrsDetail->price + $calculatedGst))
                * 100 / ($mrsDetail->price + $calculatedGst);
        }

        if ($mrsDetail->save()) {
            $out['message'] = 'save successfully';
            $out['status'] = 'OK';
        } else {
            $out['message'] = 'save time error';
        }
    }
    /**
     * POST /v2/api/item/order
     *
     * The till's checkout. status_id 1 raises a real Order and deducts stock;
     * status_id 2 parks it as an OrderHold and does not. Everything runs in one
     * transaction, rolled back unless every line saved.
     *
     * The bill number is assigned *after* the commit, not before: the order is
     * written with no bill_no, then the highest bill_no of the current
     * financial year is read and incremented and saved on its own. Two tills
     * checking out at the same moment can therefore read the same number. That
     * is how Yii 1 does it and it is reproduced here - fixing it means deciding
     * on a numbering scheme, which is not a porting decision. Recorded in
     * docs/live-bugs-found.md.
     *
     * The financial year runs April to March, which is what decides the window
     * the bill number is drawn from.
     *
     * Reproduced faults, each commented at its call site: an unknown
     * customer_id is a fatal, because $customer is read without a check; a
     * status_id other than 1 or 2 answers "Order status wrong" and then carries
     * on to use an $order that was never created; and the credit-note branch
     * marks the note used before the items are known to save, so a rolled-back
     * order can still consume one.
     *
     * The online-order callback and the SMS both go through PosOutbound when
     * stubbed.
     */
    public function actionOrder()
    {
        $out = $this->envelope('order');

        $loginId = $this->headerUserId();
        if (!$loginId) {
            return $out;
        }

        $post = Yii::$app->request->post();
        foreach (['item_details', 'mode_of_payment', 'mode_of_delivery', 'status_id'] as $k) {
            if (!isset($post[$k])) {
                return $out;
            }
        }

        $status = $post['status_id'];
        if ($status == '1') {
            $order = new Order();
            $order->gross_total_amt = isset($post['gross_total_amt']) ? $post['gross_total_amt'] : 0;
        } elseif ($status == '2') {
            $order = new OrderHold();
        } else {
            // Yii 1 answers here and then keeps going with no $order at all
            $out['message'] = 'Order status wrong';
            return $out;
        }

        $ok = true;
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $customer = null;
            if (isset($post['customer_id'])) {
                $customer = Customer::findOne($post['customer_id']);
            }

            // April to March
            $month = date('m');
            if ($month > 3) {
                $startDate = date('Y') . '-04-01';
                $endDate = (date('Y') + 1) . '-03-31';
            } else {
                $startDate = (date('Y') - 1) . '-04-01';
                $endDate = date('Y') . '-03-31';
            }

            $order->bill_date = date('Y-m-d');
            $order->mode_of_payment = $post['mode_of_payment'];
            $order->mode_of_delivery = $post['mode_of_delivery'];
            $order->total_amt = $post['total_amt'];
            $order->discount_amt = $post['discount_amt'];
            $order->outlet_id = $post['outlet_id'];
            if (isset($post['customer_id'])) {
                // no null check on $customer, as in Yii 1
                $order->city_id = $customer->city_id;
                $order->state_id = $customer->state_id;
                $order->country_id = $customer->country_id;
                $order->customer_id = $customer->id;
            }
            if (isset($post['online_order_id'])) {
                $order->online_order_id = $post['online_order_id'];
            }
            if (isset($post['is_mobile'])) {
                $order->is_mobile = $post['is_mobile'];
            }
            $order->create_user_id = $loginId;

            if (!$order->save()) {
                $transaction->rollBack();
                return $out;
            }

            if ($status == '1' && isset($post['credit_note_id'])) {
                $creditNote = CreditNote::find()
                    ->where(['credit_number' => $post['credit_note_id']])
                    ->orderBy(['id' => SORT_ASC])
                    ->one();
                if ($creditNote) {
                    if (($creditNote->amt - $creditNote->amt_used) >= $order->total_amt) {
                        // marked used before the lines are known to save
                        $creditNote->amt_used = $creditNote->amt_used + $order->total_amt;
                        $creditNote->save();
                    } else {
                        // the message set here is overwritten by 'Try again'
                        // when the rollback below runs, as in Yii 1
                        $ok = false;
                        $out['message'] = 'Credit note amount is less than total amount';
                    }
                } else {
                    $ok = false;
                    $out['message'] = 'Credit note is not found';
                }
            }

            $itemArrays = json_decode($post['item_details']);
            if ($itemArrays) {
                foreach ($itemArrays as $itemArray) {
                    $orderItem = ($status == '1') ? new OrderItem() : new OrderHoldItem();

                    // compare() in Yii 1, so an empty bar code drops the
                    // condition and matches the first detail
                    $q = ItemDetail::find()->orderBy(['id' => SORT_ASC]);
                    if ($itemArray->bar_code !== null && $itemArray->bar_code !== '') {
                        $q->andWhere(['bar_code' => $itemArray->bar_code]);
                    }
                    $itemDetail = $q->one();

                    if (!$itemDetail) {
                        $ok = false;
                        continue;
                    }

                    $orderItem->item_detail_id = $itemDetail->id;
                    $orderItem->item_id = $itemDetail->item_id;
                    $orderItem->qty = $itemArray->qty;
                    $orderItem->price = $orderItem->remove_format($itemArray->base_price);
                    if ($itemArray->discount_id != 0) {
                        $orderItem->discount_id = $itemArray->discount_id;
                        $orderItem->discount_amt = $itemArray->discount_amt;
                    }
                    if ($itemArray->tax_id != 0) {
                        $orderItem->tax_id = $orderItem->getTaxValueID($itemArray->tax_id);
                        if ($status == '1') {
                            $orderItem->original_tax = $itemArray->tax_id;
                        }
                        $orderItem->tax_amount = $itemArray->tax_amt;
                    }
                    $orderItem->sale_rate = $itemArray->sale_rate;
                    $orderItem->mrp = $itemArray->mrp;
                    $orderItem->total_amt = $itemArray->total_amount;
                    $orderItem->cgst_per = $itemArray->cgst_per;
                    $orderItem->sgst_per = $itemArray->sgst_per;
                    $orderItem->cess_per = $itemArray->cess_per;
                    $orderItem->igst_per = $itemArray->igst_per;
                    $orderItem->cgst_amt = $itemArray->cgst_amt;
                    $orderItem->sgst_amt = $itemArray->sgst_amt;
                    $orderItem->cess_amt = $itemArray->cess_amount;
                    $orderItem->igst_amt = $itemArray->igst_amount;
                    $orderItem->create_user_id = $loginId;
                    if ($status == '1') {
                        $orderItem->order_id = $order->id;
                        $orderItem->status = '1';
                    } else {
                        $orderItem->order_hold_id = $order->id;
                    }

                    if ($orderItem->save()) {
                        if ($status == '1') {
                            $order->UpdateStock($itemArray->qty, $itemDetail->id);
                        }
                    } else {
                        $ok = false;
                    }
                }
            }

            if (!$ok) {
                $transaction->rollBack();
                $out['message'] = 'Try again';
                return $out;
            }

            $transaction->commit();

            // the bill number, read and assigned after the commit
            $latest = Order::find()
                ->where(['between', 'date(create_time)', $startDate, $endDate])
                ->orderBy(['bill_no' => SORT_DESC])
                ->one();
            $order->bill_no = $latest ? $latest->bill_no + 1 : 1;
            $order->save(false, ['bill_no']);

            if ($status == '1') {
                $this->notifyOnlineOrderPacked($order, $post);
                $order->SendSms();
            }

            $out['status'] = 'OK';
            // Yii 1 emits the stringified column; Yii 2's AR casts it to int
            $out['order_id'] = (string)$order->id;

            if ($status == '1') {
                $out['bill_no'] = $order->getOrderBillNo();

                $items = OrderItem::find()
                    // t.* FIRST, then the aggregates. The select names qty, tax_amount
                    // and the four gst columns twice - once as a SUM and once inside
                    // t.* - and a duplicate column name in a PDO row is resolved by
                    // whichever comes last. Yii 1's CActiveFinder expands t.* into
                    // explicit columns ahead of the criteria's own select, so the SUMs
                    // win there; Yii 2 emits t.* verbatim at the end, so the raw column
                    // won and a group of two lines reported one line's quantity.
                    ->select('t.*, SUM(qty) AS qty, SUM(tax_amount) AS tax_amount, SUM(cgst_amt) AS cgst_amt,'
                           . ' SUM(sgst_amt) AS sgst_amt, SUM(cess_amt) AS cess_amt, SUM(igst_amt) AS igst_amt')
                    ->alias('t')
                    ->joinWith('item item')
                    ->where(['t.order_id' => $order->id])
                    ->groupBy(['t.tax_id', 't.item_id', 'item.hsn_code'])
                    ->orderBy('t.tax_id, t.item_id, item.hsn_code')
                    ->all();

                $taxes = [];
                foreach ($items as $item) {
                    $taxes[] = $item->getTaxApiArray();
                }
                $out['taxes'] = $taxes;
            } else {
                $out['bill_no'] = '0';
            }

            $out['message'] = 'Order is saved Successfully';
        } catch (\yii\base\ErrorException $e) {
            // A PHP warning - reading a property on a missing customer, say -
            // reaches here as an ErrorException in Yii 2, where Yii 1's error
            // handler renders it as a 500 without the catch ever seeing it.
            // Roll back and let it through, so both stacks answer 500.
            $transaction->rollBack();
            throw $e;
        } catch (\Exception $e) {
            // Exception, not Throwable, as in Yii 1: on PHP 8 an Error is not
            // an Exception, so it escapes this catch on both stacks.
            //
            // Yii 1 swallows this silently and answers NOK with no message,
            // which is reproduced - but it is logged here, because a checkout
            // that fails without saying why is not something to leave
            // undiagnosable. The log is not part of the response.
            Yii::error('item/order rolled back: ' . $e->getMessage()
                . ' at ' . $e->getFile() . ':' . $e->getLine(), __METHOD__);
            $transaction->rollBack();
        }

        return $out;
    }

    /**
     * Marks the online order packed and tells the webshop, with the order's
     * lines as a JSON blob. Shipping is hardcoded to '0.00' - the branch that
     * charged 50 below 2,000 is commented out in Yii 1.
     */
    private function notifyOnlineOrderPacked($order, $post, $url = 'http://sect4.soulbowl.in/deliveryoption/index/sendemailnotification')
    {
        if (!isset($post['online_order_id'])) {
            return;
        }

        $itemList = [];
        $orderItems = OrderItem::find()
            ->where(['order_id' => $order->id])
            ->orderBy(['id' => SORT_ASC])
            ->all();
        foreach ($orderItems as $orderItem) {
            $item = Item::findOne($orderItem->item_id);
            if ($item) {
                $itemList[$item->item_code] = [
                    'name' => $item->title,
                    'qty' => $orderItem->qty,
                    'price' => ($orderItem->qty * $orderItem->sale_rate),
                ];
            }
        }

        $data = [
            'items' => $itemList,
            'sub_total' => $order->total_amt,
            'grand_total' => $order->total_amt,
            'shipping' => '0.00',
        ];

        $onlineOrder = OnlineOrder::find()
            ->where(['id' => $post['online_order_id']])
            ->orderBy(['id' => SORT_ASC])
            ->one();
        if (!$onlineOrder) {
            return;
        }

        $onlineOrder->order_status = OnlineOrder::ORDERSTATUS_PACKED;
        $onlineOrder->save(false, ['order_status']);

        $fields = [
            'sKeY' => getenv('POS_SOULBOWL_KEY'),
            'order_id' => (string)$onlineOrder->order_id,
            'action' => 'update_dispatch_status2',
            'status' => '1',
            'data' => json_encode($data),
            'date' => date('Y-m-d H:i:s'),
        ];

        if (class_exists('PosOutbound') && PosOutbound::isStubbed()) {
            PosOutbound::intercept(PosOutbound::CHANNEL_HTTP, 'POST ' . $url, $fields);
            return;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }
    /**
     * POST /v2/api/item/ordertest
     *
     * An older copy of item/order that is still routed. Diffed against it line
     * for line, the two are the same 330 lines apart from five things, all of
     * which this reproduces:
     *
     *   - it does not set gross_total_amt on the order;
     *   - it does not set status on the order line;
     *   - it does not return order_id;
     *   - its webshop callback goes to http://soulbowl.in/rest/api rather than
     *     the sect4 dispatch endpoint;
     *   - its tax summary groups by tax_id alone, with no SUM() columns - so
     *     each row carries one line's figures rather than the group's, and two
     *     lines sharing a tax collapse to whichever the database returns.
     *
     * Ported because it is reachable, not because it should be. Whether it
     * should still exist is a question for the owner; see
     * docs/live-bugs-found.md.
     */
    public function actionOrdertest()
    {
        $out = $this->envelope('ordertest');

        $loginId = $this->headerUserId();
        if (!$loginId) {
            return $out;
        }

        $post = Yii::$app->request->post();
        foreach (['item_details', 'mode_of_payment', 'mode_of_delivery', 'status_id'] as $k) {
            if (!isset($post[$k])) {
                return $out;
            }
        }

        $status = $post['status_id'];
        if ($status == '1') {
            $order = new Order();   // no gross_total_amt here, unlike item/order
        } elseif ($status == '2') {
            $order = new OrderHold();
        } else {
            $out['message'] = 'Order status wrong';
            return $out;
        }

        $ok = true;
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $customer = null;
            if (isset($post['customer_id'])) {
                $customer = Customer::findOne($post['customer_id']);
            }

            $month = date('m');
            if ($month > 3) {
                $startDate = date('Y') . '-04-01';
                $endDate = (date('Y') + 1) . '-03-31';
            } else {
                $startDate = (date('Y') - 1) . '-04-01';
                $endDate = date('Y') . '-03-31';
            }

            $order->bill_date = date('Y-m-d');
            $order->mode_of_payment = $post['mode_of_payment'];
            $order->mode_of_delivery = $post['mode_of_delivery'];
            $order->total_amt = $post['total_amt'];
            $order->discount_amt = $post['discount_amt'];
            $order->outlet_id = $post['outlet_id'];
            if (isset($post['customer_id'])) {
                $order->city_id = $customer->city_id;
                $order->state_id = $customer->state_id;
                $order->country_id = $customer->country_id;
                $order->customer_id = $customer->id;
            }
            if (isset($post['online_order_id'])) {
                $order->online_order_id = $post['online_order_id'];
            }
            if (isset($post['is_mobile'])) {
                $order->is_mobile = $post['is_mobile'];
            }
            $order->create_user_id = $loginId;

            if (!$order->save()) {
                $transaction->rollBack();
                return $out;
            }

            if ($status == '1' && isset($post['credit_note_id'])) {
                $creditNote = CreditNote::find()
                    ->where(['credit_number' => $post['credit_note_id']])
                    ->orderBy(['id' => SORT_ASC])
                    ->one();
                if ($creditNote) {
                    if (($creditNote->amt - $creditNote->amt_used) >= $order->total_amt) {
                        $creditNote->amt_used = $creditNote->amt_used + $order->total_amt;
                        $creditNote->save();
                    } else {
                        $ok = false;
                        $out['message'] = 'Credit note amount is less than total amount';
                    }
                } else {
                    $ok = false;
                    $out['message'] = 'Credit note is not found';
                }
            }

            $itemArrays = json_decode($post['item_details']);
            if ($itemArrays) {
                foreach ($itemArrays as $itemArray) {
                    $orderItem = ($status == '1') ? new OrderItem() : new OrderHoldItem();

                    $q = ItemDetail::find()->orderBy(['id' => SORT_ASC]);
                    if ($itemArray->bar_code !== null && $itemArray->bar_code !== '') {
                        $q->andWhere(['bar_code' => $itemArray->bar_code]);
                    }
                    $itemDetail = $q->one();

                    if (!$itemDetail) {
                        $ok = false;
                        continue;
                    }

                    $orderItem->item_detail_id = $itemDetail->id;
                    $orderItem->item_id = $itemDetail->item_id;
                    $orderItem->qty = $itemArray->qty;
                    $orderItem->price = $orderItem->remove_format($itemArray->base_price);
                    if ($itemArray->discount_id != 0) {
                        $orderItem->discount_id = $itemArray->discount_id;
                        $orderItem->discount_amt = $itemArray->discount_amt;
                    }
                    if ($itemArray->tax_id != 0) {
                        $orderItem->tax_id = $orderItem->getTaxValueID($itemArray->tax_id);
                        if ($status == '1') {
                            $orderItem->original_tax = $itemArray->tax_id;
                        }
                        $orderItem->tax_amount = $itemArray->tax_amt;
                    }
                    $orderItem->sale_rate = $itemArray->sale_rate;
                    $orderItem->mrp = $itemArray->mrp;
                    $orderItem->total_amt = $itemArray->total_amount;
                    $orderItem->cgst_per = $itemArray->cgst_per;
                    $orderItem->sgst_per = $itemArray->sgst_per;
                    $orderItem->cess_per = $itemArray->cess_per;
                    $orderItem->igst_per = $itemArray->igst_per;
                    $orderItem->cgst_amt = $itemArray->cgst_amt;
                    $orderItem->sgst_amt = $itemArray->sgst_amt;
                    $orderItem->cess_amt = $itemArray->cess_amount;
                    $orderItem->igst_amt = $itemArray->igst_amount;
                    $orderItem->create_user_id = $loginId;
                    if ($status == '1') {
                        $orderItem->order_id = $order->id;
                        // no ->status here, unlike item/order
                    } else {
                        $orderItem->order_hold_id = $order->id;
                    }

                    if ($orderItem->save()) {
                        if ($status == '1') {
                            $order->UpdateStock($itemArray->qty, $itemDetail->id);
                        }
                    } else {
                        $ok = false;
                    }
                }
            }

            if (!$ok) {
                $transaction->rollBack();
                $out['message'] = 'Try again';
                return $out;
            }

            $transaction->commit();

            $latest = Order::find()
                ->where(['between', 'date(create_time)', $startDate, $endDate])
                ->orderBy(['bill_no' => SORT_DESC])
                ->one();
            $order->bill_no = $latest ? $latest->bill_no + 1 : 1;
            $order->save(false, ['bill_no']);

            if ($status == '1') {
                $this->notifyOnlineOrderPacked($order, $post, 'http://soulbowl.in/rest/api');
                $order->SendSms();
            }

            $out['status'] = 'OK';
            // no order_id here, unlike item/order

            if ($status == '1') {
                $out['bill_no'] = $order->getOrderBillNo();

                // grouped by tax_id alone and with no SUM(), so each row is one
                // line's figures - not the group's. As in Yii 1.
                $items = OrderItem::find()
                    ->alias('t')
                    ->where(['t.order_id' => $order->id])
                    ->groupBy(['t.tax_id'])
                    ->orderBy('t.tax_id')
                    ->all();

                $taxes = [];
                foreach ($items as $item) {
                    $taxes[] = $item->getTaxApiArray();
                }
                $out['taxes'] = $taxes;
            } else {
                $out['bill_no'] = '0';
            }

            $out['message'] = 'Order is saved Successfully';
        } catch (\yii\base\ErrorException $e) {
            $transaction->rollBack();
            throw $e;
        } catch (\Exception $e) {
            Yii::error('item/ordertest rolled back: ' . $e->getMessage()
                . ' at ' . $e->getFile() . ':' . $e->getLine(), __METHOD__);
            $transaction->rollBack();
        }

        return $out;
    }
    /**
     * POST /v2/api/item/punchorder
     *
     * A till checkout that prices the basket itself rather than trusting the
     * client: for each bar code it reads the item detail, applies an
     * order-level discount if one was asked for, and derives the base price,
     * tax and totals from the sale rate. Then it raises the order through
     * processOrder() and sends the customer a PDF bill over WhatsApp.
     *
     * Unlike item/order it returns the computed basket in item_details, along
     * with totalSaleValue (at MRP), netAmount (at sale rate) and the saving
     * between them.
     *
     * $_POST['customer_id'] is read without a check in generateBillAndSend, so
     * a request without one is a PHP 8 warning; reproduced.
     */
    public function actionPunchorder()
    {
        $out = $this->envelope('punchorder');

        try {
            $loginId = $this->headerUserId();
            if (!$loginId) {
                return $out;
            }

            $post = Yii::$app->request->post();
            if (!isset($post['item_details'])) {
                return $out;
            }

            $itemArrays = json_decode($post['item_details']);
            if (!is_array($itemArrays) || count($itemArrays) === 0) {
                $out['message'] = 'No item details provided';
                return $out;
            }

            $basket = [];
            $totalSaleValue = 0;
            $netAmount = 0;

            foreach ($itemArrays as $item) {
                $detail = ItemDetail::find()
                    ->where(['bar_code' => $item->bar_code])
                    ->orderBy(['id' => SORT_ASC])
                    ->one();
                if (!$detail) {
                    continue;
                }

                $d = $detail->toApiArray();
                $line = [];
                $line['item_id'] = $d['item_id'];
                $line['bar_code'] = $d['bar_code'];
                $line['qty'] = $item->qty;
                $line['sale_rate'] = $d['sale_rate'];
                $line['base_price'] = $d['base_price'];
                $line['mrp'] = $d['mrp'];
                $line['tax_id'] = $d['tax_id'];
                $line['cgst_per'] = $d['cgst_per'];
                $line['sgst_per'] = $d['sgst_per'];
                $line['cess_per'] = $d['cess_per'];
                $line['igst_per'] = $d['igst_per'];
                $line['discount_id'] = $d['discount_id'];
                $line['discount_val'] = $d['discount_val'];
                $line['discount_amt'] = $d['discount_amt'];
                $line['discount_type'] = $d['discount_type'];
                $line['stock_qty'] = $d['stock_qty'];
                $line['product_name'] = $d['item_desc'];
                $line['hsn_code'] = $d['hsn_code'];
                $line['unit_name'] = $d['unit_name'];

                if (isset($post['apply_discount']) && $post['apply_discount']
                    && isset($post['discount_id']) && $post['discount_id'] > 0) {
                    $discount = Discount::findOne($post['discount_id']);
                    if ($discount) {
                        $line['discount_id'] = $post['discount_id'];
                        // Yii 1 emits the stringified column; Yii 2's AR casts to int
                        $line['discount_type'] = (string)$discount->type_id;
                        if ($discount->type_id == Discount::TYPE_PERCENTAGE) {
                            // the discount amount is computed from the rate
                            // *after* it has already been reduced, as in Yii 1
                            $line['sale_rate'] = $line['sale_rate'] - ($line['sale_rate'] * floatval($discount->amount) / 100);
                            $line['discount_amt'] = ($line['sale_rate'] * floatval($discount->amount) / 100);
                        } elseif ($discount->type_id == Discount::TYPE_AMOUNT) {
                            $line['sale_rate'] = $line['sale_rate'] - floatval($discount->applicable_amt);
                            $line['discount_amt'] = $discount->applicable_amt;
                        }
                    }
                }

                $line['base_price'] = $this->punchBasePrice($line['sale_rate'], $d['tax_percent']);
                $line['total_amount'] = round($line['sale_rate'] * $item->qty, 2);
                $line['tax_amt'] = round($this->punchTaxAmount($line['base_price'], $d['tax_percent']) * $item->qty, 2);
                $line['tax_percent'] = $d['tax_percent'];
                $line['taxable_amount'] = round($line['base_price'] * $item->qty, 2);
                $line['sgst_amt'] = round($this->punchTaxAmount($line['base_price'], $line['sgst_per']) * $item->qty, 2);
                $line['cgst_amt'] = round($this->punchTaxAmount($line['base_price'], $line['cgst_per']) * $item->qty, 2);
                $line['cess_amount'] = round($this->punchTaxAmount($line['base_price'], $line['cess_per']) * $item->qty, 2);
                $line['igst_amount'] = round($this->punchTaxAmount($line['base_price'], $line['igst_per']) * $item->qty, 2);

                $totalSaleValue += round($line['mrp'] * $item->qty);
                $netAmount += $line['total_amount'];

                $basket[] = $line;
            }

            $out['item_details'] = $basket;
            $out['totalSaleValue'] = round($totalSaleValue);
            $out['netAmount'] = round($netAmount);
            $out['saving'] = round($totalSaleValue - $netAmount);

            // processOrder builds its own response array and sends it through
            // sendJSONResponse(), which calls Yii::app()->end() - so a bad
            // status_id answers with just {"message":"Order status wrong"} and
            // nothing else. $halt carries that back.
            $halt = null;
            $billNo = $this->punchProcessOrder($loginId, $basket, $out, $halt);
            if ($halt !== null) {
                return $halt;
            }

            if ($billNo) {
                $this->punchGenerateBillAndSend($out, $billNo, $loginId);
                $out['status'] = 'OK';
                $out['bill_no'] = $billNo;
            }
        } catch (\yii\base\ErrorException $e) {
            throw $e;
        } catch (\Exception $e) {
            $out['message'] = 'Error processing order: ' . $e->getMessage();
            $out['error_details'] = $e->getTraceAsString();
        }

        return $out;
    }

    /** Yii 1's getBasePrice(): the sale rate less the tax it already includes. */
    private function punchBasePrice($saleRate, $taxPercent)
    {
        if ($taxPercent > 0) {
            return round($saleRate / (1 + ($taxPercent / 100)), 2);
        }
        return round($saleRate, 2);
    }

    /** Yii 1's getTaxAmount(). */
    private function punchTaxAmount($basePrice, $taxPercent)
    {
        return $basePrice * ($taxPercent / 100);
    }

    /**
     * Yii 1's processOrder(): the same body as item/order with four
     * differences - the totals come from the computed basket rather than the
     * request, is_mobile is forced to 1, and the order line takes its
     * sale_rate from mrp rather than the sale rate.
     *
     * It builds a full response array internally and then returns only the
     * bill number, so everything it puts in that array is discarded. Kept out
     * of $out here for the same reason: the caller does not see it either.
     */
    private function punchProcessOrder($loginId, $basket, &$outer, &$halt = null)
    {
        $post = Yii::$app->request->post();
        foreach (['mode_of_payment', 'mode_of_delivery', 'status_id'] as $k) {
            if (!isset($post[$k])) {
                return null;
            }
        }

        $status = $post['status_id'];
        if ($status == '1') {
            $order = new Order();
        } elseif ($status == '2') {
            $order = new OrderHold();
        } else {
            // Yii 1 sends this and ends the request here, with none of the
            // envelope the action had built.
            $halt = ['message' => 'Order status wrong'];
            return null;
        }

        $ok = true;
        $billNo = null;
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $customer = null;
            if (isset($post['customer_id'])) {
                $customer = Customer::findOne($post['customer_id']);
            }

            $month = date('m');
            if ($month > 3) {
                $startDate = date('Y') . '-04-01';
                $endDate = (date('Y') + 1) . '-03-31';
            } else {
                $startDate = (date('Y') - 1) . '-04-01';
                $endDate = date('Y') . '-03-31';
            }

            $order->bill_date = date('Y-m-d');
            $order->mode_of_payment = $post['mode_of_payment'];
            $order->mode_of_delivery = $post['mode_of_delivery'];
            $order->total_amt = $outer['netAmount'];
            $order->discount_amt = $outer['saving'];
            $order->outlet_id = $post['outlet_id'];
            if (isset($post['customer_id'])) {
                $order->city_id = $customer->city_id;
                $order->state_id = $customer->state_id;
                $order->country_id = $customer->country_id;
                $order->customer_id = $customer->id;
            }
            if (isset($post['online_order_id'])) {
                $order->online_order_id = $post['online_order_id'];
            }
            $order->is_mobile = 1;   // forced, unlike item/order
            $order->create_user_id = $loginId;

            if (!$order->save()) {
                $transaction->rollBack();
                return null;
            }

            if ($status == '1' && isset($post['credit_note_id'])) {
                $creditNote = CreditNote::find()
                    ->where(['credit_number' => $post['credit_note_id']])
                    ->orderBy(['id' => SORT_ASC])
                    ->one();
                if ($creditNote) {
                    if (($creditNote->amt - $creditNote->amt_used) >= $order->total_amt) {
                        $creditNote->amt_used = $creditNote->amt_used + $order->total_amt;
                        $creditNote->save();
                    } else {
                        $ok = false;
                    }
                } else {
                    $ok = false;
                }
            }

            foreach ($basket as $line) {
                $orderItem = ($status == '1') ? new OrderItem() : new OrderHoldItem();

                $q = ItemDetail::find()->orderBy(['id' => SORT_ASC]);
                if ($line['bar_code'] !== null && $line['bar_code'] !== '') {
                    $q->andWhere(['bar_code' => $line['bar_code']]);
                }
                $detail = $q->one();

                if (!$detail) {
                    $ok = false;
                    continue;
                }

                $orderItem->item_detail_id = $detail->id;
                $orderItem->item_id = $detail->item_id;
                $orderItem->qty = $line['qty'];
                $orderItem->price = $orderItem->remove_format($line['base_price']);
                if ($line['discount_id'] != 0) {
                    $orderItem->discount_id = $line['discount_id'];
                    $orderItem->discount_amt = $line['discount_amt'];
                }
                if ($line['tax_id'] != 0) {
                    $orderItem->tax_id = $orderItem->getTaxValueID($line['tax_id']);
                    if ($status == '1') {
                        $orderItem->original_tax = $line['tax_id'];
                    }
                    $orderItem->tax_amount = $line['tax_amt'];
                }
                $orderItem->sale_rate = $line['mrp'];   // mrp, not sale_rate
                $orderItem->mrp = $line['mrp'];
                $orderItem->total_amt = $line['total_amount'];
                $orderItem->cgst_per = $line['cgst_per'];
                $orderItem->sgst_per = $line['sgst_per'];
                $orderItem->cess_per = $line['cess_per'];
                $orderItem->igst_per = $line['igst_per'];
                $orderItem->cgst_amt = $line['cgst_amt'];
                $orderItem->sgst_amt = $line['sgst_amt'];
                $orderItem->cess_amt = $line['cess_amount'];
                $orderItem->igst_amt = $line['igst_amount'];
                $orderItem->create_user_id = $loginId;
                if ($status == '1') {
                    $orderItem->order_id = $order->id;
                    $orderItem->status = '1';
                } else {
                    $orderItem->order_hold_id = $order->id;
                }

                if ($orderItem->save()) {
                    if ($status == '1') {
                        $order->UpdateStock($line['qty'], $detail->id);
                    }
                } else {
                    $ok = false;
                }
            }

            if (!$ok) {
                $transaction->rollBack();
                return null;
            }

            $transaction->commit();

            $latest = Order::find()
                ->where(['between', 'date(create_time)', $startDate, $endDate])
                ->orderBy(['bill_no' => SORT_DESC])
                ->one();
            $billNo = $latest ? $latest->bill_no + 1 : 1;
            $order->bill_no = $billNo;
            $order->save(false, ['bill_no']);

            if ($status == '1') {
                $this->notifyOnlineOrderPacked($order, $post);
                $order->SendSms();
            }
        } catch (\yii\base\ErrorException $e) {
            $transaction->rollBack();
            throw $e;
        } catch (\Exception $e) {
            Yii::error('item/punchorder rolled back: ' . $e->getMessage()
                . ' at ' . $e->getFile() . ':' . $e->getLine(), __METHOD__);
            $transaction->rollBack();
            return null;
        }

        return $billNo;
    }

    /**
     * Yii 1's generateBillAndSend(): renders the bill to a PDF under the web
     * root, posts it to the remote file endpoint and sends the customer a
     * WhatsApp message pointing at it.
     *
     * Yii 1 goes through its ePdf extension, a wrapper round the same mPDF this
     * uses directly. The view is app2/views/item/_pdf.php, a copy of
     * protected/views/item/_pdf.php - it is plain PHP and needed no changes.
     *
     * $_POST['customer_id'] is read unchecked, as in Yii 1.
     */
    private function punchGenerateBillAndSend($billData, $billNo = null, $loginId = null)
    {
        $post = Yii::$app->request->post();
        $customer = Customer::findOne($post['customer_id']);
        $user = User::findOne($loginId);

        if (!$customer || !$customer->contact_no) {
            return ['message' => 'user not found'];
        }

        // @legacyroot, not @webroot - see the alias note in config/web.php
        $uploadDir = Yii::getAlias('@legacyroot') . '/uploadbills/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = $billNo . '.pdf';
        $filePath = $uploadDir . $fileName;

        $html = $this->renderPartial('//item/_pdf', [
            'billData' => $billData,
            'customer' => $customer,
            'billNo' => $billNo,
            'username' => $user ? $user->username : '',
        ]);

        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4',
            'tempDir' => Yii::getAlias('@runtime'),
        ]);
        $mpdf->WriteHTML($html);
        $mpdf->Output($filePath, \Mpdf\Output\Destination::FILE);

        $api = new InteraktApi(getenv('POS_INTERAKT_API_KEY') ?: null);
        $api->uploadFileToServer($filePath);

        $whatsappNo = preg_replace('/[^0-9]/', '', (string)$customer->contact_no);

        $template = 'purchase_order';
        if (strpos(strtolower($fileName), 'reprint') !== false) {
            $template = 'reprint_order';
        } elseif (strpos(strtolower($fileName), 'refund') !== false) {
            $template = 'refund_order';
        }

        $pdfName = str_replace('.pdf', '', $fileName);
        $pdfName = str_replace(['_Reprint', '_Refund', '-Reprint', '-Refund'], '', $pdfName);

        return ['message' => $api->sendApprovalOrderMessageNew(
            $template,
            $whatsappNo,
            [$customer->name, $pdfName],
            ['http://61.2.241.71/pos/whatapporder/' . $fileName],
            $fileName,
            [
                'user_id' => isset($post['user_id']) ? $post['user_id'] : null,
                'computer_name' => isset($post['computer_name']) ? $post['computer_name'] : null,
            ]
        )];
    }
}
