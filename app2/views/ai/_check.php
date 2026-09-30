<?php
/**
 * One check's result, loaded into its card on the insights page.
 *
 * @var string $key
 * @var array $check [group, title, why]
 * @var array|null $result
 * @var string|null $error
 */
use yii\helpers\Html;

$count = $result ? (int) $result['total'] : 0;
$class = $error ? 'err' : ($count > 0 ? 'hit' : 'ok');
?>
<span class="ai-count <?php echo $class; ?>"><?php echo $error ? '!' : ($count > 0 ? number_format($count) : '<i class="fa fa-check"></i>'); ?></span>
<div class="ai-check-body">
	<p class="ai-why"><?php echo Html::encode($check[2]); ?></p>
	<?php if ($error) { ?>
	<div class="ai-error"><?php echo Html::encode($error); ?></div>
	<?php } elseif (!$result['rows']) { ?>
	<p><strong><?php echo $result['note'] ? Html::encode($result['note']) : 'Nothing found.'; ?></strong></p>
	<?php } else { ?>
	<?php if ($result['note']) { ?><p><strong><?php echo Html::encode($result['note']); ?></strong></p><?php } ?>
	<div class="ai-scroll">
		<table class="table table-striped table-condensed ai-table">
			<thead><tr>
				<?php foreach ($result['columns'] as $label) { ?><th><?php echo Html::encode($label); ?></th><?php } ?>
				<th></th>
			</tr></thead>
			<tbody>
			<?php foreach ($result['rows'] as $row) { ?>
				<tr>
					<?php foreach (array_keys($result['columns']) as $col) { ?><td><?php echo Html::encode((string) ($row[$col] ?? '')); ?></td><?php } ?>
					<td><?php if (!empty($row['_link'])) { ?><a href="<?php echo Html::encode($row['_link']); ?>" target="_blank" title="Open">Open <i class="fa fa-external-link"></i></a><?php } ?></td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
	</div>
	<?php if ($count > count($result['rows'])) { ?>
	<p class="ai-meta">Showing the first <?php echo count($result['rows']); ?> of <?php echo number_format($count); ?>.</p>
	<?php } ?>
	<?php } ?>
	<p class="ai-meta">
		<?php if ($result) { ?>Worked out at <?php echo Html::encode($result['time']); ?>.<?php } ?>
		<a href="#" class="ai-refresh"><i class="fa fa-refresh"></i> Refresh</a>
	</p>
</div>
