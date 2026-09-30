<?php
/**
 * "Read a vendor bill" on the GRN screen, under the Merge button.
 *
 * Rendered at the end of purchaseBillDetail/index for the AI roles only. It
 * prints settings and loads a script, nothing else: the button and the result
 * are drawn by v2/js/grn-bill.js, so the page's own text and element ids -
 * what the page sweeps compare with Yii 1 - stay as they were.
 *
 * @var yii\web\View $this
 * @var int|string|null $poid the GRN that is open, if any
 */
use app\components\ai\AiConfig;
use yii\helpers\Json;
use yii\helpers\Url;

$request = Yii::$app->request;
$last = Yii::$app->session['ai_bill_grn'];
$settings = [
	'poid' => (int) $poid,
	'readUrl' => Url::to(['/ai/bill-grn']),
	'lastUrl' => Url::to(['/ai/bill-grn-last', 'poid' => (int) $poid]),
	'csvUrl' => Url::to(['/ai/bill-csv']),
	'csrfParam' => $request->csrfParam,
	'csrfToken' => $request->getCsrfToken(),
	'reason' => AiConfig::unavailableReason(),
	'last' => ($last && (int) $poid > 0 && (int) $last['poid'] === (int) $poid)
		? ['file' => (string) ($last['result']['file'] ?? ''), 'time' => date('H:i', (int) $last['time'])] : null,
];
$this->registerCssFile('/v2/css/ai.css?v=20260930e');
?>
<script>window.DASPOS_GRN_BILL = <?php echo Json::htmlEncode($settings); ?>;</script>
<script src="/v2/js/grn-bill.js?v=20260930e"></script>
