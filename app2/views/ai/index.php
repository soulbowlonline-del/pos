<?php
/**
 * The AI hub: what there is and what it costs.
 *
 * @var yii\web\View $this
 * @var string|null $reason
 * @var float $spent
 * @var float $budget
 */
use yii\helpers\Html;
use yii\helpers\Url;

$this->registerCssFile('/v2/css/ai.css?v=20260930');
?>
<div class="ai-page">
	<div class="ai-head">
		<div>
			<h1><i class="fa fa-magic"></i> AI Assistant</h1>
			<p class="ai-sub">Reads the store's data and points out what needs attention. It never changes a bill, a price or the stock.</p>
		</div>
	</div>

	<?php echo $this->render('_status', ['reason' => $reason]); ?>

	<div class="ai-cards">
		<a class="ai-card" href="<?php echo Url::to(['/ai/insights']); ?>">
			<div class="ai-card-icon"><i class="fa fa-heartbeat"></i></div>
			<h3>Insights</h3>
			<p>Today's checks: items running out, stock below zero or not selling, GRNs waiting, bills above MRP or entered twice, and item master gaps.</p>
			<span class="ai-tag free">Free - no AI cost</span>
		</a>
		<a class="ai-card" href="<?php echo Url::to(['/ai/ask']); ?>">
			<div class="ai-card-icon"><i class="fa fa-comments-o"></i></div>
			<h3>Ask DASPOS</h3>
			<p>Ask in English, Hindi or Punjabi: "Milk sales this week vs last week", "Top 10 items this month", "Stock of Verka paneer".</p>
			<span class="ai-tag">Uses Claude - about ₹2-5 a question</span>
		</a>
		<a class="ai-card" href="<?php echo Url::to(['/ai/bill']); ?>">
			<div class="ai-card-icon"><i class="fa fa-file-text-o"></i></div>
			<h3>Read a vendor bill</h3>
			<p>Upload a photo or PDF of a supplier's bill. Each line is matched to the item master and checked for MRP, GST, rate and expiry before the GRN is entered.</p>
			<span class="ai-tag">Uses Claude - about ₹5-15 a bill</span>
		</a>
		<a class="ai-card" href="<?php echo Url::to(['/ai/usage']); ?>">
			<div class="ai-card-icon"><i class="fa fa-pie-chart"></i></div>
			<h3>Usage and spend</h3>
			<p>This month: <strong>$<?php echo Html::encode(number_format($spent, 2)); ?></strong> of the $<?php echo Html::encode(number_format($budget, 2)); ?> limit. Who asked what, and what it cost.</p>
		</a>
	</div>

	<p class="ai-sub" style="margin-top:18px">
		Everything here only reads. Suggestions - what to reorder, which bill line to check - are for a person to act on in the usual screens.
		Customer names and phone numbers are never sent to the AI.
	</p>
</div>
