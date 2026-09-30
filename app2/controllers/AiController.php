<?php
namespace app\controllers;

use app\components\ai\AiConfig;
use app\components\ai\AiException;
use app\components\ai\AiLog;
use app\components\ai\AiMarkdown;
use app\components\ai\AskDaspos;
use app\components\ai\BillReader;
use app\components\ai\GrnBillFill;
use app\components\ai\Insights;
use app\components\ai\InsightSummary;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * The AI screens, at /v2/ai. New in the port; there is no Yii 1 counterpart,
 * so this controller is deliberately not in Ui::PORTED (the parity sweeps read
 * that list and would look for it on Yii 1). The URL manager's default
 * parsing routes /v2/ai/<action> to it; no rule is needed.
 *
 * Everything here reads. Nothing changes stock, prices, bills or the item
 * master; where a finding needs action, it links to the existing screen.
 *
 * Only the roles in POS_AI_ROLES (default: admin) and the people named in
 * POS_AI_USERS get in. Unlike the rest of
 * the port, POSTs here are CSRF-checked: they spend money on the Anthropic
 * account, so a page elsewhere must not be able to trigger them.
 */
class AiController extends BaseUiController
{
    public $enableCsrfValidation = true;

    /**
     * The summary spends money, so it answers POST only: CSRF is checked on
     * POST, and a GET would let any link or image on another page start a
     * paid call in an admin's browser.
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => \yii\filters\VerbFilter::class,
                'actions' => ['summary' => ['POST'], 'bill-csv' => ['GET'], 'check' => ['GET'],
                              'bill-grn' => ['POST'], 'bill-grn-last' => ['GET']],
            ],
        ];
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        if (!AiConfig::userAllowed()) {
            // parent opened an output buffer; close it before the error page renders
            ob_end_clean();
            throw new ForbiddenHttpException('You are not allowed to access this page.');
        }
        // The layout's theme scripts need jQuery in the head. Other pages get
        // it from a grid or form widget; these may have none.
        \yii\web\YiiAsset::register($this->view);
        return true;
    }

    public function actionIndex()
    {
        $this->view->title = 'DASPOS - AI Assistant';
        return $this->render('index', [
            'reason' => AiConfig::unavailableReason(),
            'spent' => AiLog::spentThisMonth(),
            'budget' => AiConfig::monthlyBudget(),
        ]);
    }

    public function actionInsights()
    {
        $this->view->title = 'DASPOS - Insights';
        return $this->render('insights', ['checks' => Insights::checks(), 'reason' => AiConfig::unavailableReason()]);
    }

    /** One check, as an HTML fragment for the insights page. */
    public function actionCheck($key, $refresh = 0)
    {
        if (!isset(Insights::checks()[$key])) {
            throw new \yii\web\NotFoundHttpException('Unknown check.');
        }
        // The cards load in parallel; without this they queue on the session lock.
        $this->releaseSession();
        if ($refresh) {
            Insights::forget($key);
        }
        try {
            $result = Insights::run($key);
            $error = null;
        } catch (\Throwable $e) {
            Yii::warning('Insight ' . $key . ' failed: ' . $e->getMessage(), __METHOD__);
            $result = null;
            $error = 'This check could not be worked out just now (the database took too long or answered with an error). Try Refresh in a minute.';
        }
        return $this->renderPartial('_check', ['key' => $key, 'check' => Insights::checks()[$key], 'result' => $result, 'error' => $error]);
    }

    public function actionSummary()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->releaseSession();
        try {
            $text = InsightSummary::write((string) Yii::$app->request->post('lang', 'en'));
            return ['ok' => true, 'html' => AiMarkdown::toHtml($text)];
        } catch (AiException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function actionAsk()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            $this->view->title = 'DASPOS - Ask DASPOS';
            return $this->render('ask', ['reason' => AiConfig::unavailableReason()]);
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        $history = json_decode((string) $request->post('history', '[]'), true);
        $this->releaseSession();
        try {
            $r = AskDaspos::answer((string) $request->post('question', ''), is_array($history) ? $history : []);
        } catch (AiException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
        $calls = [];
        foreach ($r['calls'] as list($name, $input, $summary)) {
            $calls[] = ['tool' => $name, 'input' => $input, 'result' => $summary];
        }
        return ['ok' => true, 'answer' => $r['answer'], 'html' => AiMarkdown::toHtml($r['answer']), 'calls' => $calls];
    }

    public function actionBill()
    {
        $this->view->title = 'DASPOS - Read a vendor bill';
        $vendors = Yii::$app->db->createCommand('SELECT id, name FROM tbl_vendor WHERE status = 0 OR status IS NULL ORDER BY name')->queryAll();
        $result = null;
        $error = null;
        $vendorId = (int) Yii::$app->request->post('vendor_id', 0);
        if (Yii::$app->request->isPost) {
            try {
                list($mime, $bytes) = BillReader::validateUpload($_FILES['bill'] ?? []);
                $name = basename((string) ($_FILES['bill']['name'] ?? 'bill'));
                $this->releaseSession();
                $result = BillReader::read($mime, $bytes, $name, $vendorId ?: null);
                $result['file'] = $name;
                Yii::$app->session->open();
                Yii::$app->session['ai_bill_last'] = $result;
            } catch (AiException $e) {
                $error = $e->getMessage();
            }
        }
        return $this->render('bill', ['vendors' => $vendors, 'vendorId' => $vendorId, 'result' => $result,
            'error' => $error, 'reason' => AiConfig::unavailableReason()]);
    }

    /**
     * Reads a vendor's bill for the GRN that is open on the GRN screen and
     * answers with what to fill into its grid (GrnBillFill). Called by
     * v2/js/grn-bill.js from under the Merge button. Saves nothing: the
     * javascript types the values into the grid and the storekeeper presses
     * Update, as always. The reading is kept in the session so the grid can
     * be filled again after a reload without a second, paid, reading.
     */
    public function actionBillGrn()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $poid = (int) Yii::$app->request->post('poid', 0);
        try {
            $grn = GrnBillFill::grn($poid);
            list($mime, $bytes) = BillReader::validateUpload($_FILES['bill'] ?? []);
            $name = basename((string) ($_FILES['bill']['name'] ?? 'bill'));
            $this->releaseSession();
            $result = BillReader::read($mime, $bytes, $name, (int) $grn['vendor_id']);
            $result['file'] = $name;
            Yii::$app->session->open();
            Yii::$app->session['ai_bill_last'] = $result;
            Yii::$app->session['ai_bill_grn'] = ['poid' => $poid, 'time' => time(), 'result' => $result];
            return ['ok' => true] + GrnBillFill::plan($result, $grn, GrnBillFill::rows($poid));
        } catch (AiException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /** The same answer from the bill last read for this GRN in this session; no Claude call. */
    public function actionBillGrnLast($poid)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $last = Yii::$app->session['ai_bill_grn'];
        if (!$last || (int) $last['poid'] !== (int) $poid) {
            return ['ok' => false, 'error' => 'No bill has been read for this GRN since you signed in. Read the bill again.'];
        }
        try {
            return ['ok' => true] + GrnBillFill::plan($last['result'], GrnBillFill::grn((int) $poid), GrnBillFill::rows((int) $poid));
        } catch (AiException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /** The last bill read in this session, as CSV. */
    public function actionBillCsv()
    {
        $result = Yii::$app->session['ai_bill_last'];
        if (!$result) {
            return $this->redirect(['/ai/bill']);
        }
        $out = fopen('php://temp', 'w+');
        fwrite($out, "\xEF\xBB\xBF"); // so Excel reads the ₹ signs as UTF-8
        fputcsv($out, ['#', 'On bill', 'Barcode on bill', 'HSN', 'Qty', 'Free', 'Rate', 'MRP', 'GST %', 'Batch', 'Expiry', 'Amount',
                       'Matched barcode', 'Matched item', 'Matched by', 'Checks']);
        foreach ($result['lines'] as $l) {
            $x = $l['line'];
            fputcsv($out, array_map([$this, 'csvCell'], [$l['n'], $x['description'] ?? '', $x['barcode'] ?? '', $x['hsn'] ?? '',
                $x['qty'] ?? '', $x['free_qty'] ?? '', $x['rate'] ?? '', $x['mrp'] ?? '', $x['gst_percent'] ?? '', $x['batch'] ?? '',
                $x['expiry'] ?? '', $x['amount'] ?? '', $l['match']['bar_code'] ?? '', $l['match']['title'] ?? '', $l['how'] ?? '',
                implode('; ', array_map(function ($f) { return $f[1]; }, $l['flags']))]));
        }
        rewind($out);
        $csv = stream_get_contents($out);
        return Yii::$app->response->sendContentAsFile($csv, 'bill-' . date('Ymd-His') . '.csv', ['mimeType' => 'text/csv']);
    }

    /**
     * Text read off a vendor's bill goes into a spreadsheet; a cell starting
     * = + - @ would be run by Excel as a formula. Numbers are left alone.
     */
    private function csvCell($v)
    {
        if (is_string($v) && $v !== '' && !is_numeric($v) && strpos("=+-@\t\r", $v[0]) !== false) {
            return "'" . $v;
        }
        return $v;
    }

    /**
     * A Claude call can take a minute. PHP holds the session file locked for
     * the whole request, which would freeze every other tab of the same user
     * (and the layout's order poll) until the answer came back. Everything
     * this request needs from the session - the user, the CSRF check - has
     * been read by now.
     */
    private function releaseSession()
    {
        Yii::$app->session->close();
    }

    public function actionUsage()
    {
        $this->view->title = 'DASPOS - AI usage';
        return $this->render('usage', [
            'report' => AiLog::report(),
            'spent' => AiLog::spentThisMonth(),
            'budget' => AiConfig::monthlyBudget(),
            'model' => AiConfig::model(),
            'reason' => AiConfig::unavailableReason(),
        ]);
    }
}
