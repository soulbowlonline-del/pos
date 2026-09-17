<?php
namespace app\controllers;

use Yii;
use app\models\ItemReturnItem;
use app\models\PurchaseBill;
use app\models\PurchaseBillDetail;
use app\models\B2bPurchaseBill;
use app\models\Outlet;
use app\models\Vendor;
use yii\web\Controller;
use yii\web\Response;

/**
 * Partial Yii 2 port of protected/modules/api/controllers/TallyController.php.
 *
 * Ported: cashsale    - the per-tax-rate GST summary for the export
 *         stockreturn   - returns to vendors, for the credit-note side
 *         paymentreport - approved purchase bills for a day
 *         b2btaxwise    - B2B purchase tax summary
 * It is raw SQL end to end and depends on no model payloads, which makes it
 * portable in isolation.
 *
 * Not ported:
 *   b2bsales      the remaining B2B report
 *
 * Yii 1 continues to serve every /api/tally/* route.
 */
class TallyController extends Controller
{
    public $enableCsrfValidation = false;

    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    /**
     * POST /v2/api/tally/cashsale?date=YYYY-MM-DD
     *
     * Per tax rate for the given day: taxable value net of refunds, then CGST,
     * SGST, CESS and IGST derived from the tax row.
     *
     * The Yii 1 version concatenates $date straight into three queries. All
     * parameters are bound here.
     */
    public function actionCashsale($date = null)
    {
        $out = [
            'controller' => 'tally',
            'action' => 'cashsale',
            'status' => 'NOK',
        ];

        // A deliberate deviation from Yii 1, and the only one in this action.
        // With no date the Yii 1 version builds `create_date = ''`, which
        // throws CDbException; the same query here matches zero-dated rows and
        // then runs two subqueries per tax rate, so the request ran for the
        // full execution limit before dying. Neither behaviour is useful and a
        // request that occupies a worker for five minutes is the worse of the
        // two, so a missing date returns the normal empty envelope instead.
        if ($date === null || $date === '') {
            $out['message'] = 'data not available';
            return $out;
        }

        $db = Yii::$app->db;

        // One row per tax rate present on the day. The Yii 1 query groups
        // without ordering, so the row order was left to MySQL; ordered here
        // and on the Yii 1 side so the export is stable.
        $items = $db->createCommand(
            'SELECT * FROM `tbl_order_item` WHERE `create_date` = :date GROUP BY `tax_id` ORDER BY `tax_id`',
            [':date' => $date]
        )->queryAll();

        $list = [];
        foreach ($items as $item) {
            $taxId = $item['tax_id'];

            $totalTaxable = $db->createCommand(
                'SELECT sum(`price` * `qty`) as total FROM `tbl_order_item`'
                . ' WHERE `create_date` = :date AND `tax_id` = :tax',
                [':date' => $date, ':tax' => $taxId]
            )->queryOne();

            $refundRow = $db->createCommand(
                'SELECT sum(`price` * `qty`) as total FROM `tbl_order_refund_item`'
                . ' WHERE date(`create_time`) = :date AND `tax_id` = :tax',
                [':date' => $date, ':tax' => $taxId]
            )->queryOne();

            $taxable = $totalTaxable['total'] - $refundRow['total'];

            // Initialised before the lookup: the Yii 1 version leaves these
            // undefined when the tax row is missing, which on PHP 8 raises a
            // warning that Yii 1's error handler turns into a 500.
            $cgst = $sgst = $cess = $igst = $gst = $totalAmt = null;

            $taxRow = $db->createCommand(
                'SELECT * FROM `tbl_tax` WHERE `id` = :tax',
                [':tax' => $taxId]
            )->queryOne();

            if ($taxRow) {
                $cgst = $taxable * ($taxRow['tax_val1'] * 0.01);
                $sgst = $taxable * ($taxRow['tax_val2'] * 0.01);
                $cess = $taxable * ($taxRow['tax_val3'] * 0.01);
                $igst = $taxable * ($taxRow['tax_val4'] * 0.01);
                $tax = $taxRow['tax_val1'] + $taxRow['tax_val2'] + $taxRow['tax_val4'];
                $gst = ($taxable * ($tax * 0.01)) + ($taxable * ($item['cess_per'] * 0.01));
                $totalAmt = $taxable + $gst;
            }

            // Key order and spelling are reproduced exactly; these become column
            // headings in the Tally import.
            $list[] = [
                'Bill Date' => $date,
                'Taxable' => $taxable,
                'Gst' => $gst,
                'Cgst_per' => $item['cgst_per'],
                'Sgst_per' => $item['sgst_per'],
                'Cess_per' => $item['cess_per'],
                'Igst_per' => $item['igst_per'],
                'Cgst' => $cgst,
                'Sgst' => $sgst,
                'Cess' => $taxable * ($item['cess_per'] * 0.01),
                'Igst' => $igst,
                'Amount' => $totalAmt,
            ];
        }

        if (empty($list)) {
            $out['message'] = 'data not available';
            return $out;
        }

        $out['status'] = 'OK';
        $out['grouptax'] = $list;
        return $out;
    }

    /**
     * POST /v2/api/tally/stockreturn?date=YYYY-MM-DD
     *
     * Vendor returns saved against a GRN on the given day, excluding returns
     * with a zero total.
     *
     * The Yii 1 version concatenates $date into the condition; bound here.
     * Its tax columns are zeroed selectively depending on whether the vendor
     * and outlet share a state - see ItemReturnItem::getTaxPercentage().
     */
    public function actionStockreturn($date = null)
    {
        $out = [
            'controller' => 'tally',
            'action' => 'stockreturn',
            'status' => 'NOK',
        ];

        if ($date === null || $date === '') {
            $out['message'] = 'data not available';
            return $out;
        }

        $items = ItemReturnItem::find()
            ->alias('t')
            ->joinWith(['itemReturn itemReturn'], true, 'INNER JOIN')
            ->andWhere(['t.type_id' => 0])
            ->andWhere(['DATE(itemReturn.grn_save_date)' => $date])
            ->andWhere(['!=', 'itemReturn.total_amt', 0])
            ->orderBy(['t.id' => SORT_ASC])
            ->all();

        if (empty($items)) {
            $out['message'] = 'data not available';
            return $out;
        }

        $list = [];
        foreach ($items as $item) {
            $list[] = $item->toTallyApiArray();
        }
        $out['status'] = 'OK';
        $out['orders'] = $list;
        return $out;
    }

    /**
     * POST /v2/api/tally/paymentreport?date=YYYY-MM-DD&id=N
     *
     * Approved purchase bills starting on the given day, one row per
     * bill-and-tax-rate combination. The optional id returns only rows whose
     * detail id is greater than it, which is how the caller pages through.
     *
     * $date is bound rather than concatenated, as elsewhere in this controller.
     */
    public function actionPaymentreport($date = null, $id = null)
    {
        $out = [
            'controller' => 'tally',
            'action' => 'paymentreport',
            'status' => 'NOK',
        ];

        if ($date === null || $date === '') {
            $out['message'] = 'data not available';
            return $out;
        }

        $billIds = PurchaseBill::find()
            ->select('id')
            ->where(['start_date' => $date, 'status' => PurchaseBill::STATUS_APPROVED])
            ->column();

        // An empty IN() matches nothing, which is what Yii 1's addInCondition
        // produces for an empty array too.
        $details = empty($billIds) ? [] : PurchaseBillDetail::find()
            ->where(['purchase_bill_id' => $billIds])
            ->groupBy(['purchase_bill_id', 'tax_id'])
            ->orderBy(['approved_qty' => SORT_DESC])
            ->all();

        if (empty($details)) {
            $out['message'] = 'data not available';
            return $out;
        }

        $list = [];
        foreach ($details as $detail) {
            if ($id !== null && $id !== '') {
                if ($detail->id > $id) {
                    $list[] = $detail->toApiArray1(true);
                }
            } else {
                $list[] = $detail->toApiArray1(true);
            }
        }

        $out['status'] = 'OK';
        $out['orders'] = $list;
        return $out;
    }

    /**
     * POST /v2/api/tally/b2btaxwise?date=YYYY-MM-DD
     *
     * B2B purchase tax summary for a day, one row per bill-and-tax-rate.
     *
     * Raw SQL end to end, like cashsale. The Yii 1 version builds a CDbCriteria
     * query first and then overwrites its result with the raw query on the very
     * next line, so that criteria is dead; only the raw path is ported.
     *
     * Note the refund subquery: the Yii 1 code runs it and assigns the result,
     * then computes $taxable from the gross alone - the line that subtracted
     * refunds is commented out. The query is kept so the behaviour is identical
     * if it is ever reinstated, but it does not affect the output today.
     *
     * The bill-number prefix logic is inverted here exactly as in
     * Order::getOrderBillNo(): an outlet with a prefix has it discarded in
     * favour of 'B'. Reproduced; see the note on that method.
     */
    public function actionB2btaxwise($date = null)
    {
        $out = [
            'controller' => 'tally',
            'action' => 'b2btaxwise',
            'status' => 'NOK',
        ];

        if ($date === null || $date === '') {
            $out['message'] = 'data not available';
            return $out;
        }

        $db = Yii::$app->db;

        $items = $db->createCommand(
            'SELECT * FROM `tbl_b2bpurchase_bill_detail` WHERE date(create_time) = :date'
            . ' GROUP BY `tax_id`, `purchase_bill_id` ORDER BY `tax_id`, `purchase_bill_id`',
            [':date' => $date]
        )->queryAll();

        $list = [];
        foreach ($items as $item) {
            $taxId = $item['tax_id'];
            $billId = $item['purchase_bill_id'];

            $totalTaxable = $db->createCommand(
                'SELECT sum(`price` * `approved_qty`) as total FROM `tbl_b2bpurchase_bill_detail`'
                . ' WHERE date(`create_time`) = :date AND `tax_id` = :tax AND `purchase_bill_id` = :bill',
                [':date' => $date, ':tax' => $taxId, ':bill' => $billId]
            )->queryOne();

            // Run, as in Yii 1, but not subtracted - that line is commented out there.
            $db->createCommand(
                'SELECT sum(`price` * `qty`) as total FROM `tbl_order_refund_item`'
                . ' WHERE date(`create_time`) = :date AND `tax_id` = :tax',
                [':date' => $date, ':tax' => $taxId]
            )->queryOne();

            $taxable = $totalTaxable['total'];

            $cgst = $sgst = $cess = $igst = $gst = $totalAmt = null;
            $taxRow = $db->createCommand(
                'SELECT * FROM `tbl_tax` WHERE `id` = :tax', [':tax' => $taxId]
            )->queryOne();
            if ($taxRow) {
                $tax = $taxRow['tax_val1'] + $taxRow['tax_val2'] + $taxRow['tax_val4'];
                $cgst = $taxable * ($taxRow['tax_val1'] * 0.01);
                $sgst = $taxable * ($taxRow['tax_val2'] * 0.01);
                $cess = $taxable * ($taxRow['tax_val3'] * 0.01);
                $igst = $taxable * ($taxRow['tax_val4'] * 0.01);
                $gst = ($taxable * ($tax * 0.01)) + ($taxable * ($item['cess_per'] * 0.01));
                $totalAmt = $taxable + $gst;
            }

            $bill = B2bPurchaseBill::findOne($billId);
            $vendor = $bill ? Vendor::findOne($bill->vendor_id) : null;
            $vendorName = $vendor ? $vendor->name : '';

            $billNo = '';
            if ($bill && !empty($bill->start_date)) {
                $ts = strtotime((string)$bill->start_date);
                $month = (int)date('m', $ts);
                if ($month > 3) {
                    $year = substr(date('Y', $ts), -2);
                    $yearLast = substr((string)((int)$year + 1), -2);
                } else {
                    $year = substr((string)((int)date('Y', $ts) - 1), -2);
                    $yearLast = substr(date('Y', $ts), -2);
                }

                // Defaulted so a missing outlet cannot leave this undefined -
                // in Yii 1 that is a PHP 8 warning and therefore a 500.
                $billPrefix = 'B';
                $outlet = Outlet::findOne($item['outlet_id']);
                if ($outlet) {
                    $billPrefix = ($outlet->bill_prefix == '') ? $outlet->bill_prefix : 'B';
                }

                $billNo = 'B2B ' . $year . '-' . $yearLast . '/' . $billPrefix . '-' . $bill->id;
            }

            $list[] = [
                'Bill_No' => $billNo,
                'bill_date' => $date,
                'customer' => $vendorName,
                'taxable' => $taxable,
                'gst' => $gst,
                'cgst_per' => $item['cgst_per'],
                'Sgst_per' => $item['sgst_per'],
                'cess_per' => $item['cess_per'],
                'igst_per' => $item['igst_per'],
                'cgst' => $cgst,
                'sgst' => $sgst,
                'cess' => $taxable * ($item['cess_per'] * 0.01),
                'igst' => $igst,
                'amount' => $totalAmt,
            ];
        }

        if (empty($list)) {
            $out['message'] = 'data not available';
            return $out;
        }

        $out['status'] = 'OK';
        $out['grouptax'] = $list;
        return $out;
    }
}
