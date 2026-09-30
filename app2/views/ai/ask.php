<?php
/**
 * Ask DASPOS: questions in plain words.
 *
 * @var yii\web\View $this
 * @var string|null $reason
 */
use yii\helpers\Url;

$this->registerCssFile('/v2/css/ai.css?v=20260930');
$request = Yii::$app->request;
$examples = [
	'Total sales yesterday, and the same day last week',
	'Top 10 items by amount this month',
	'Milk sales this week vs last week',
	'Current stock of Verka paneer',
	'Which vendors did we buy the most from last month?',
	'Gross profit and margin by month since April',
	'Which items earned the most and the least profit last month?',
	'Which items should I stock more of, and which less?',
	'पिछले 7 दिनों की बिक्री दिन के हिसाब से',
];
?>
<div class="ai-page">
	<div class="ai-head">
		<div>
			<h1><i class="fa fa-comments-o"></i> Ask DASPOS</h1>
			<p class="ai-sub">Ask about sales, profit and margins, items, stock, purchases and refunds in English, Hindi or Punjabi. Answers come from the live data; the figures used are listed under each answer.</p>
		</div>
		<div><a class="btn btn-default btn-sm" href="<?php echo Url::to(['/ai/index']); ?>">AI Assistant</a></div>
	</div>

	<?php echo $this->render('_status', ['reason' => $reason]); ?>

	<div class="ai-chat" id="ai-chat">
		<p class="ai-sub" id="ai-empty">Try one of these, or type your own question below.</p>
		<div class="ai-examples">
			<?php foreach ($examples as $e) { ?>
			<button type="button" class="btn btn-default btn-sm ai-example"><?php echo \yii\helpers\Html::encode($e); ?></button>
			<?php } ?>
		</div>
		<div id="ai-thread"></div>
	</div>
	<form class="ai-ask-box" id="ai-ask-form" autocomplete="off">
		<textarea class="form-control" id="ai-question" rows="2" maxlength="2000" placeholder="Type a question and press Enter"></textarea>
		<button type="submit" class="btn btn-primary" id="ai-send" <?php echo $reason !== null ? 'disabled' : ''; ?>><i class="fa fa-paper-plane"></i> Ask</button>
	</form>
	<p class="ai-sub" style="margin-top:8px">Answers are worked out by Claude from the store's data and can be wrong - check anything important on the usual report before acting on it. Nothing is changed in the system.</p>
</div>
<?php
$csrfParam = json_encode($request->csrfParam);
$csrfToken = json_encode($request->getCsrfToken());
$askUrl = json_encode(Url::to(['/ai/ask']));
$this->registerJs(<<<JS
(function ($) {
	var history = [], busy = false;
	function esc(s) { return $('<div>').text(s).html(); }
	function add(cls, who, html) {
		var el = $('<div class="ai-msg ' + cls + '"><div class="ai-who"></div><div class="ai-bubble"></div></div>');
		el.find('.ai-who').text(who);
		el.find('.ai-bubble').html(html);
		$('#ai-thread').append(el);
		el[0].scrollIntoView({ block: 'end', behavior: 'smooth' });
		return el;
	}
	function ask(q) {
		q = $.trim(q);
		if (!q || busy) { return; }
		busy = true;
		$('#ai-send').prop('disabled', true);
		$('#ai-empty').hide();
		add('q', 'You', esc(q));
		var wait = add('a', 'DASPOS', '<span class="ai-thinking"><i class="fa fa-spinner fa-spin"></i>Looking it up - this can take up to a minute.</span>');
		var data = { question: q, history: JSON.stringify(history) };
		data[$csrfParam] = $csrfToken;
		$.ajax({ url: $askUrl, type: 'POST', data: data, dataType: 'json', timeout: 330000 }).done(function (r) {
			if (!r.ok) {
				wait.find('.ai-bubble').html('<div class="ai-error"></div>').find('.ai-error').text(r.error);
				return;
			}
			wait.find('.ai-bubble').html(r.html);
			if (r.calls && r.calls.length) {
				var d = $('<details><summary>Figures used (' + r.calls.length + ')</summary><ul></ul></details>');
				$.each(r.calls, function (i, c) {
					d.find('ul').append($('<li>').append($('<code>').text(c.tool + ' ' + JSON.stringify(c.input))).append(' &rarr; ' + esc(c.result)));
				});
				wait.append(d);
			}
			history.push([q, r.answer]);
			if (history.length > 3) { history.shift(); }
		}).fail(function (x, status) {
			wait.find('.ai-bubble').html('<div class="ai-error">' + (status === 'timeout'
				? 'No answer after five minutes. Try a narrower question.'
				: 'The question could not be answered just now. Try again in a minute.') + '</div>');
		}).always(function () {
			busy = false;
			$('#ai-send').prop('disabled', false);
			$('#ai-question').focus();
		});
	}
	$('#ai-ask-form').on('submit', function (e) {
		e.preventDefault();
		var q = $('#ai-question').val();
		$('#ai-question').val('');
		ask(q);
	});
	$('#ai-question').on('keydown', function (e) {
		if (e.which === 13 && !e.shiftKey) { e.preventDefault(); $('#ai-ask-form').submit(); }
	});
	$('.ai-example').on('click', function () {
		if (!$('#ai-send').prop('disabled')) { ask($(this).text()); }
	});
})(jQuery);
JS
);
