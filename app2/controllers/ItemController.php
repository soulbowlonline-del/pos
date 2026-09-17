<?php
namespace app\controllers;

use Yii;
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
 *
 * Not ported:
 *   search            needs the filter query builder on top of the payload
 *                     that getItem now uses.
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
}
