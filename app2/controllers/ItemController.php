<?php
namespace app\controllers;

use Yii;
use app\models\Item;
use app\models\ItemDetail;
use yii\web\Controller;
use yii\web\Response;

/**
 * Partial Yii 2 port of protected/modules/api/controllers/ItemController.php.
 *
 * The Yii 1 controller is 2,343 lines over fourteen actions and contains 37
 * cURL calls. Most of it is the POS write path - actionOrder creates orders,
 * actionAdjust and actionUpdateStock move stock, actionPunchorder and
 * actionBillUpdate post to external services. None of that is ported here.
 *
 * Ported: list    - the catalogue feed, built on ItemDetail::toonlineArray()
 *         getItem - a single item by barcode, or the first 50, built on
 *                   ItemDetail::toArray()
 *         search  - the same payload, filtered by name/title/rate
 *
 * Not ported:
 *   order, adjust, updateStock, punchorder, billUpdate, adjustitemtozero,
 *   scannedItem, barcode, getGRN, getGRNItems, ordertest
 *                     write paths and external integrations; they need a
 *                     fixture-based harness and, for the cURL calls, a stubbed
 *                     transport before they can be compared safely.
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
}
