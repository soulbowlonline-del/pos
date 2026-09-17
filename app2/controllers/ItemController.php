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
 * Ported: list - the catalogue feed, built on ItemDetail::toonlineArray().
 *
 * Not ported:
 *   getItem, search   need ItemDetail::toArray(), 127 lines calling six further
 *                     helpers (getItemDetailSaleRate, getBasePrice,
 *                     getItemDetailMrp, getItemTaxAmount and the item discount
 *                     chain). Worth doing, but as its own increment.
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
}
