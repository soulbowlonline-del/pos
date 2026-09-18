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
     * POST /v2/api/item/get-grnitems?id=N
     *
     * The lines of one purchase bill. Yii 1 loads the bill itself into a
     * variable it never uses, and logs the id at warning level on every call;
     * neither is reproduced, as neither is observable in the response.
     *
     * The Yii 1 query had no ORDER BY. Ordered by id on both sides.
     */
    public function actionGetGrnitems($id)
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
     * Reproduced as found. This action takes no parameters and does one thing:
     * it loads purchase bill 97 - a literal id in the source - and sets its
     * status to 0. Any caller, authenticated or not, can flip that one row, and
     * it always answers OK whether or not the row exists or the save worked.
     *
     * It reads like a debug leftover that shipped. Ported rather than dropped
     * so the two stacks match, and recorded in docs/live-bugs-found.md, because
     * removing a live endpoint is the owner's call.
     */
    public function actionBillUpdate()
    {
        $out = $this->envelope('billUpdate');

        $purchaseBill = PurchaseBill::findOne(97);
        if ($purchaseBill) {
            $purchaseBill->status = 0;
            $purchaseBill->save();
        }

        $out['status'] = 'OK';
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

        $saleStatus = Yii::$app->params['saleStatus'];

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
}
