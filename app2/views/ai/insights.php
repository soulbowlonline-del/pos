<?php
/**
 * The daily checks. Each card loads on its own, so a slow check does not hold
 * up the others; results are cached for ten minutes.
 *
 * @var yii\web\View $this
 * @var array $checks
 * @var string|null $reason
 */
use app\components\ai\InsightSummary;
use yii\helpers\Html;
use yii\helpers\Url;

$this->registerCssFile('/v2/css/ai.css?v=20260930');
$request = Yii::$app->request;
?>
<div class="ai-page">
	<div class="ai-head">
		<div>
			<h1><i class="fa fa-heartbeat"></i> Insights</h1>
			<p class="ai-sub">Worked out from today's data by fixed rules - no AI cost. Click a check to see the rows; each row links to the screen where it is fixed.</p>
		</div>
		<div>
			<?php if ($reason === null) { ?>
			<select id="ai-summary-lang" class="form-control input-sm" style="display:inline-block;width:auto" title="Language of the summary">
				<?php foreach (InsightSummary::LANGUAGES as $code => $label) { ?>
				<option value="<?php echo $code; ?>"><?php echo Html::encode(explode(' (', $label)[0]); ?></option>
				<?php } ?>
			</select>
			<button type="button" class="btn btn-primary btn-sm" id="ai-summary-btn"><i class="fa fa-magic"></i> Summarise for me</button>
			<?php } ?>
			<a class="btn btn-default btn-sm" href="<?php echo Url::to(['/ai/index']); ?>">AI Assistant</a>
		</div>
	</div>

	<div class="ai-summary" id="ai-summary"></div>

	<?php
	$group = null;
	foreach ($checks as $key => list($g, $title, $why)) {
		if ($g !== $group) {
			$group = $g;
			echo '<div class="ai-group">' . Html::encode($g) . '</div>';
		}
	?>
	<div class="ai-check" data-key="<?php echo Html::encode($key); ?>" data-url="<?php echo Html::encode(Url::to(['/ai/check', 'key' => $key])); ?>">
		<div class="ai-check-head" role="button" tabindex="0">
			<span class="ai-count"><i class="fa fa-spinner fa-spin"></i></span>
			<h4><?php echo Html::encode($title); ?></h4>
			<i class="fa fa-chevron-down text-muted"></i>
		</div>
		<div class="ai-check-body"><p class="ai-why"><?php echo Html::encode($why); ?></p></div>
	</div>
	<?php } ?>
</div>
<?php
$csrfParam = json_encode($request->csrfParam);
$csrfToken = json_encode($request->getCsrfToken());
$summaryUrl = json_encode(Url::to(['/ai/summary']));
$this->registerJs(<<<JS
(function ($) {
	function load(card, refresh) {
		var url = card.data('url') + (refresh ? '&refresh=1' : '');
		card.find('.ai-count').attr('class', 'ai-count').html('<i class="fa fa-spinner fa-spin"></i>');
		$.get(url).done(function (html) {
			var frag = $('<div>').html(html);
			card.find('.ai-count').replaceWith(frag.find('.ai-count'));
			card.find('.ai-check-body').html(frag.find('.ai-check-body').html());
		}).fail(function () {
			card.find('.ai-count').attr('class', 'ai-count err').text('!');
			card.find('.ai-check-body').prepend('<div class="ai-error">This check could not be loaded. Reload the page to try again.</div>');
		});
	}
	var cards = $('.ai-check').toArray();
	// A few at a time: the checks are database work on the live server.
	var running = 0;
	function next() {
		while (running < 3 && cards.length) {
			running++;
			(function (card) {
				var url = card.data('url');
				$.get(url).always(function () { running--; next(); }).done(function (html) {
					var frag = $('<div>').html(html);
					card.find('.ai-count').replaceWith(frag.find('.ai-count'));
					card.find('.ai-check-body').html(frag.find('.ai-check-body').html());
				}).fail(function () {
					card.find('.ai-count').attr('class', 'ai-count err').text('!');
				});
			})($(cards.shift()));
		}
	}
	next();
	$(document).on('click keydown', '.ai-check-head', function (e) {
		if (e.type === 'keydown' && e.which !== 13 && e.which !== 32) { return; }
		e.preventDefault();
		$(this).closest('.ai-check').toggleClass('open');
	});
	$(document).on('click', '.ai-refresh', function (e) {
		e.preventDefault();
		load($(this).closest('.ai-check'), true);
	});
	$('#ai-summary-btn').on('click', function () {
		var btn = $(this), box = $('#ai-summary');
		btn.prop('disabled', true);
		box.addClass('show').html('<p class="ai-thinking"><i class="fa fa-spinner fa-spin"></i>Reading the checks and writing the summary - this takes up to a minute.</p>');
		var data = { lang: $('#ai-summary-lang').val() };
		data[$csrfParam] = $csrfToken;
		$.post($summaryUrl, data).done(function (r) {
			box.html(r.ok ? r.html : '<div class="ai-error"></div>');
			if (!r.ok) { box.find('.ai-error').text(r.error); }
		}).fail(function () {
			box.html('<div class="ai-error">The summary could not be written. Try again in a minute.</div>');
		}).always(function () { btn.prop('disabled', false); });
	});
})(jQuery);
JS
);
