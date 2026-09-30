<?php
/**
 * Read a vendor bill: upload, then a checklist for entering the GRN.
 *
 * @var yii\web\View $this
 * @var array $vendors
 * @var int $vendorId
 * @var array|null $result
 * @var string|null $error
 * @var string|null $reason
 */
use app\components\ai\AiConfig;
use yii\helpers\Html;
use yii\helpers\Url;

$this->registerCssFile('/v2/css/ai.css?v=20260930');
$request = Yii::$app->request;
$money = function ($v) { return $v === null || $v === '' ? '' : number_format((float) $v, 2); };
$qty = function ($v) { return $v === null || $v === '' ? '' : rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.'); };
?>
<div class="ai-page">
	<div class="ai-head">
		<div>
			<h1><i class="fa fa-file-text-o"></i> Read a vendor bill</h1>
			<p class="ai-sub">Upload a clear photo or the PDF of a supplier's bill. Each line is matched to the item master and checked before you enter the GRN. Nothing is saved - the GRN is entered on the usual screen.</p>
		</div>
		<div>
			<a class="btn btn-default btn-sm" href="<?php echo Html::encode(\app\components\Ui::to('purchaseBillDetail/index')); ?>">GRN screen</a>
			<a class="btn btn-default btn-sm" href="<?php echo Url::to(['/ai/index']); ?>">AI Assistant</a>
		</div>
	</div>

	<?php echo $this->render('_status', ['reason' => $reason]); ?>
	<?php if ($error) { ?><div class="ai-error"><?php echo Html::encode($error); ?></div><?php } ?>

	<div class="box box-primary">
		<div class="box-body">
			<form method="post" enctype="multipart/form-data" class="form-inline" id="ai-bill-form">
				<input type="hidden" name="<?php echo Html::encode($request->csrfParam); ?>" value="<?php echo Html::encode($request->getCsrfToken()); ?>">
				<div class="form-group" style="margin-right:12px">
					<label for="ai-bill-file">Bill&nbsp;</label>
					<input type="file" name="bill" id="ai-bill-file" accept="image/jpeg,image/png,image/webp,image/gif,application/pdf" required>
				</div>
				<div class="form-group" style="margin-right:12px">
					<label for="ai-bill-vendor">Vendor&nbsp;</label>
					<select name="vendor_id" id="ai-bill-vendor" class="form-control">
						<option value="">Find it from the bill's GSTIN</option>
						<?php foreach ($vendors as $v) { ?>
						<option value="<?php echo (int) $v['id']; ?>" <?php echo (int) $v['id'] === $vendorId ? 'selected' : ''; ?>><?php echo Html::encode($v['name']); ?></option>
						<?php } ?>
					</select>
				</div>
				<button type="submit" class="btn btn-primary" id="ai-bill-go" <?php echo $reason !== null ? 'disabled' : ''; ?>><i class="fa fa-magic"></i> Read the bill</button>
				<span class="ai-thinking" id="ai-bill-wait" style="display:none;margin-left:10px"><i class="fa fa-spinner fa-spin"></i>Reading - this takes up to a minute.</span>
			</form>
		</div>
	</div>

	<?php if ($result) {
		$bill = $result['bill'];
	?>
	<div class="box">
		<div class="box-body">
			<div class="ai-bill-head">
				<div><span>File</span><?php echo Html::encode($result['file']); ?></div>
				<div><span>Vendor on bill</span><?php echo Html::encode((string) $bill['vendor_name']); ?></div>
				<div><span>GSTIN</span><?php echo Html::encode((string) $bill['vendor_gstin']); ?></div>
				<div><span>Vendor in DASPOS</span><?php echo $result['vendor'] ? Html::encode($result['vendor']['name']) : '<em>not found</em>'; ?></div>
				<div><span>Bill no. / date</span><?php echo Html::encode(trim($bill['bill_no'] . ' / ' . $bill['bill_date'], ' /')); ?></div>
				<div><span>Bill total / GST</span>&#8377;<?php echo $money($bill['bill_total']); ?> / &#8377;<?php echo $money($bill['tax_total']); ?></div>
				<div><span>Lines matched</span><?php echo (int) $result['matched']; ?> of <?php echo count($result['lines']); ?></div>
				<div><span>Read by</span><?php echo Html::encode(AiConfig::label($result['model'] ?? '')); ?></div>
			</div>
			<?php if (!empty($result['retry'])) { $r = $result['retry']; ?>
			<div class="ai-note"><i class="fa fa-refresh"></i>
				<?php echo Html::encode(AiConfig::label($r['first'])); ?> could not read this bill cleanly (<?php echo Html::encode(rtrim($r['why'], '.')); ?>),
				so it was read again by <?php echo Html::encode(AiConfig::label($r['second'])); ?>.
				<?php if ($r['failed']) { ?>That second reading failed too (<?php echo Html::encode(rtrim($r['failed'], '.')); ?>); the first reading is shown.<?php } ?>
			</div>
			<?php } ?>
			<?php foreach ($result['flags'] as $f) { ?><div class="ai-warn"><?php echo Html::encode($f); ?></div><?php } ?>
			<p>
				<a class="btn btn-default btn-sm" href="<?php echo Url::to(['/ai/bill-csv']); ?>"><i class="fa fa-download"></i> Download as CSV</a>
				<span class="ai-sub">Check each line against the paper bill; the reading can be wrong where the print is unclear.</span>
			</p>
			<div class="ai-scroll" style="max-height:none">
				<table class="table table-bordered table-condensed ai-table">
					<thead><tr>
						<th>#</th><th>On the bill</th><th>Qty</th><th>Free</th><th>Rate</th><th>MRP</th><th>GST %</th><th>Batch</th><th>Expiry</th><th>Amount</th>
						<th>Item in DASPOS</th><th>Checks</th>
					</tr></thead>
					<tbody>
					<?php foreach ($result['lines'] as $l) { $x = $l['line']; $m = $l['match']; ?>
						<tr>
							<td><?php echo (int) $l['n']; ?></td>
							<td><?php echo Html::encode((string) $x['description']); ?>
								<?php if (!empty($x['barcode']) || !empty($x['hsn'])) { ?><div class="ai-alt"><?php echo Html::encode(trim(($x['barcode'] ? 'Barcode ' . $x['barcode'] : '') . ($x['hsn'] ? '  HSN ' . $x['hsn'] : ''))); ?></div><?php } ?>
							</td>
							<td class="ai-nowrap"><?php echo $qty($x['qty']); ?> <?php echo Html::encode((string) $x['unit']); ?></td>
							<td><?php echo $qty($x['free_qty']); ?></td>
							<td><?php echo $money($x['rate']); ?></td>
							<td><?php echo $money($x['mrp']); ?></td>
							<td><?php echo $x['gst_percent'] === null ? '' : Html::encode((string) (float) $x['gst_percent']); ?></td>
							<td class="ai-nowrap"><?php echo Html::encode((string) $x['batch']); ?></td>
							<td class="ai-nowrap"><?php echo Html::encode((string) $x['expiry']); ?></td>
							<td><?php echo $money($x['amount']); ?></td>
							<td>
								<?php if ($m) { ?>
									<a href="<?php echo Html::encode($m['link']); ?>" target="_blank"><?php echo Html::encode($m['title']); ?></a>
									<div class="ai-alt"><?php echo Html::encode($m['bar_code']); ?> &middot; by <?php echo Html::encode($l['how']); ?></div>
								<?php } ?>
								<?php foreach ($l['alternatives'] as $a) { ?>
									<div class="ai-alt"><?php echo $m ? 'or' : 'maybe'; ?> <a href="<?php echo Html::encode($a['link']); ?>" target="_blank"><?php echo Html::encode($a['title']); ?></a> (<?php echo Html::encode($a['barcode']); ?>)</div>
								<?php } ?>
							</td>
							<td><?php foreach ($l['flags'] as $f) { ?><span class="ai-flag <?php echo Html::encode($f[0]); ?>"><?php echo Html::encode($f[1]); ?></span> <?php } ?></td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<?php } ?>
</div>
<?php
$this->registerJs(<<<JS
jQuery('#ai-bill-form').on('submit', function () {
	jQuery('#ai-bill-go').prop('disabled', true);
	jQuery('#ai-bill-wait').show();
});
JS
);
