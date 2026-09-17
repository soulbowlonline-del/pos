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
}
