<?php
/**
 * Ported from protected/views/itemReturn/list.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Outlet;
use app\models\User;
use app\models\Vendor;
use app\widgets\ActionColumn;
use app\widgets\ActiveForm;
use app\widgets\CheckboxColumn;
use app\widgets\GridView;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	'Manage',
];


$this->registerJs("
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$.fn.yiiGridView.update('mrs-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content-header">
	<h1> <?php echo 'Manage'; ?> <?php echo Html::encode($model->label(2)) ?> </h1>

</section>
<section class="content">

	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">
				<div class="box-header">
					<h3 class="box-title">ItemReturns</h3>
				</div>
				<div class="box-body">
					<div class="row">
						<!--  form code start here -->

						<div class="col-md-12">
							<?php $form = ActiveForm::begin([
								'id' => 'item-form',
								'type' => 'horizontal',
								'enableAjaxValidation' => true,
								'htmlOptions' => ['enctype' => 'multipart/form-data'],
							]);
							?>
							<?php echo $form->dropdownListRow($model, 'vendor_id', Gx::listData(Vendor::find()->where(['status' => Vendor::STATUS_ACTIVE])->all()), ['class' => 'form-control']); ?>
							<?php ActiveForm::end(); ?>
							<div class="table-responsive">
								<?php echo GridView::widget([
									'id' => 'purchase-bill-grid',
									'type' => 'striped bordered condensed',
									'dataProvider' => $model->listsearch($val = true),
									'filter' => $model,
									'pager' => true,
									'afterAjaxUpdate' => "function(){
                                                       $.datepicker.setDefaults($.datepicker.regional['en']);
                                                        $('#Projects_projStart').datepicker({'dateFormat': 'yy-mm-dd'});
		
                                                }",
									'columns' => [
										[
											'class' => CheckboxColumn::class,
											'selectableRows'  => 100,
											'value' => function ($data, $key, $index) { return $data["id"]; },
											'checkBoxHtmlOptions' => ["name" => "idList[]"],

										],
										'id',
										[
											'attribute' => 'gross_amt',
											'value' => function ($data, $key, $index) { return $data->gross_amt; },
											'footer' => $model->getTotals($model->search()->getKeys(), 'gross_amt', 'tbl_item_return'),
										],
										[
											'attribute' => 'tax_amt',
											'value' => function ($data, $key, $index) { return $data->tax_amt; },
											'footer' => $model->getTotals($model->search()->getKeys(), 'tax_amt', 'tbl_item_return'),
										],
										[
											'attribute' => 'discount_amt',
											'value' => function ($data, $key, $index) { return $data->discount_amt; },
											'footer' => $model->getTotals($model->search()->getKeys(), 'discount_amt', 'tbl_item_return'),
										],
										[
											'attribute' => 'total_amt',
											'value' => function ($data, $key, $index) { return $data->total_amt; },
											'footer' => $model->getTotals($model->search()->getKeys(), 'total_amt', 'tbl_item_return'),
										],

										[
											'attribute' => 'vendor_id',
											'value' => function ($data, $key, $index) { return Gx::str($data->vendor); },
											'filter' => Gx::listData(Vendor::find()->where(['status'=>Vendor::STATUS_ACTIVE])->all()),
										],
										[
											'attribute' => 'outlet_id',
											'value' => function ($data, $key, $index) { return Gx::str($data->outlet); },
											'filter' => Gx::listData(Outlet::class),
										],
										[
											'attribute' => 'credit_note_id',
											'value' => function ($data, $key, $index) { return Gx::str($data->creditNote); },
											//	'filter'=>Gx::listData(Outlet::class),
										],
										[

											'header' => '<a>Status</a>',
											'class' => ActionColumn::class,
											'template' => '{view}',  //include the standard buttons plus the new status button
											'htmlOptions' => ['style' => 'width:80px'],
											'buttons' => [
												'view' => [
													//	'visible' => function ($data) { return $data->state_id==User::STATUS_INACTIVE; },
													'url' => function ($data) { return Ui::to("itemReturn/view", ["id" => $data->id]); },
													'label' => 'view',
													'options' => ['class' => 'view'],

												],

											]
										],


									],
								]); ?>

							</div>
							<input type="button" value="Merge ItemReturn" onclick="act();" />
							<br>
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>

</section>
<script>
	function act() {

		var idList = [];
		var vendor_id = $('#ItemReturn_vendor_id').val();
		$('input[type=checkbox]:checked').each(function() {
			idList.push(this.value);


		});
		console.log('idList' + idList);


		if ($('#purchase-bill-grid_c0_all').prop("checked") == true) {

			var all_check = $('#purchase-bill-grid_c0_all').val();
			var all_check_arr = jQuery.makeArray(all_check);
			var idList = $(idList).not(all_check_arr).get();
		}




		//var selected = item-detail-grid_c0_all
		//var idList    = $("input[type=checkbox]:checked").serialize();
		var url = "<?php echo Ui::to('itemReturn/merge') ?>";
		jQuery.ajax({
			'type': 'POST',
			'url': '<?php echo Ui::to('itemReturn/merge') ?>',
			//  'dataType':"json",
			'data': {
				'idList': idList,
				'vendor_id': vendor_id
			},
			'success': function(data) {
				console.log('data' + data);
				location.reload();



			},
			'cache': false
		});
		console.log(idList);
	}
</script>