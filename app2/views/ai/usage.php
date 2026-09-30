<?php
/**
 * What the AI features have cost, from tbl_ai_log.
 *
 * @var yii\web\View $this
 * @var array $report
 * @var float $spent
 * @var float $budget
 * @var string $model
 * @var string|null $reason
 */
use yii\helpers\Html;
use yii\helpers\Url;

$this->registerCssFile('/v2/css/ai.css?v=20260930');
$usd = function ($v) { return '$' . number_format((float) $v, 4); };
$names = ['ask' => 'Ask DASPOS', 'bill' => 'Vendor bills', 'bill-retry' => 'Vendor bills, read again', 'summary' => 'Insights summary'];
?>
<div class="ai-page">
	<div class="ai-head">
		<div>
			<h1><i class="fa fa-pie-chart"></i> AI usage and spend</h1>
			<p class="ai-sub">
				This month: <strong>$<?php echo number_format($spent, 2); ?></strong> of the $<?php echo number_format($budget, 2); ?> limit
				(set by POS_AI_MONTHLY_BUDGET_USD). Model: <?php echo Html::encode($model); ?><?php if (\app\components\ai\AiConfig::billFallbackModel()) { ?>; a bill it cannot read cleanly is read again by <?php echo Html::encode(\app\components\ai\AiConfig::billFallbackModel()); ?><?php } ?>. At the limit the AI features pause until the 1st; the rest of DASPOS is unaffected.
			</p>
		</div>
		<div><a class="btn btn-default btn-sm" href="<?php echo Url::to(['/ai/index']); ?>">AI Assistant</a></div>
	</div>
	<?php echo $this->render('_status', ['reason' => $reason]); ?>

	<div class="row">
		<div class="col-md-6">
			<div class="box"><div class="box-header"><h3 class="box-title">Last 31 days by feature</h3></div><div class="box-body">
				<table class="table table-condensed ai-table">
					<thead><tr><th>Feature</th><th>Calls</th><th>Input tokens</th><th>Output tokens</th><th>Cost</th></tr></thead>
					<tbody>
					<?php foreach ($report['features'] as $r) { ?>
						<tr><td><?php echo Html::encode($names[$r['feature']] ?? $r['feature']); ?></td><td><?php echo (int) $r['calls']; ?></td>
						<td><?php echo number_format((int) $r['input_tokens']); ?></td><td><?php echo number_format((int) $r['output_tokens']); ?></td>
						<td><?php echo $usd($r['cost']); ?></td></tr>
					<?php } ?>
					<?php if (!$report['features']) { ?><tr><td colspan="5">Nothing yet.</td></tr><?php } ?>
					</tbody>
				</table>
			</div></div>
		</div>
		<div class="col-md-6">
			<div class="box"><div class="box-header"><h3 class="box-title">By day</h3></div><div class="box-body">
				<table class="table table-condensed ai-table">
					<thead><tr><th>Day</th><th>Calls</th><th>Cost</th></tr></thead>
					<tbody>
					<?php foreach ($report['days'] as $r) { ?>
						<tr><td><?php echo Html::encode($r['day']); ?></td><td><?php echo (int) $r['calls']; ?></td><td><?php echo $usd($r['cost']); ?></td></tr>
					<?php } ?>
					<?php if (!$report['days']) { ?><tr><td colspan="3">Nothing yet.</td></tr><?php } ?>
					</tbody>
				</table>
			</div></div>
		</div>
	</div>
	<div class="box"><div class="box-header"><h3 class="box-title">Last 30 calls</h3></div><div class="box-body ai-scroll" style="max-height:none">
		<table class="table table-condensed table-striped ai-table">
			<thead><tr><th>Time</th><th>User</th><th>Feature</th><th>Asked</th><th>Model</th><th>Result</th><th>Cost</th></tr></thead>
			<tbody>
			<?php foreach ($report['recent'] as $r) { ?>
				<tr><td><?php echo Html::encode($r['create_time']); ?></td><td><?php echo Html::encode((string) $r['full_name']); ?></td>
				<td><?php echo Html::encode($names[$r['feature']] ?? $r['feature']); ?></td><td><?php echo Html::encode((string) $r['summary']); ?></td>
				<td><?php echo Html::encode($r['model']); ?></td><td><?php echo Html::encode($r['status']); ?></td><td><?php echo $usd($r['cost_usd']); ?></td></tr>
			<?php } ?>
			<?php if (!$report['recent']) { ?><tr><td colspan="7">Nothing yet.</td></tr><?php } ?>
			</tbody>
		</table>
	</div></div>
</div>
