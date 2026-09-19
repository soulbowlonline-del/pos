<?php
/**
 * Ported from protected/views/item/scannedItems.php.
 */

use app\components\Ui;
use app\models\User;
use app\widgets\ActiveForm;
use app\widgets\Button;
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
	$.fn.yiiGridView.update('order-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<style>
	.btn-info.export-btn {
		background-color: #00c0ef;
		border-color: #00acd6;
		margin-left: 16px;
		margin-top: 10px;
	}
	input[type=checkbox] {
    margin: -6px 0 0;
    margin-top: 1px \9;
    line-height: normal;
	}
</style>
<ul class="nav nav-pills" id="yw2">
	<li><button type="button" class="btn btn-info export-btn" data-toggle="modal"
			data-target="#myModal">Export</button></li>
		
		
</ul>
<div id="myModal" class="modal fade" role="dialog">
	<div class="modal-dialog">

		<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Select Columns</h4>
			</div>
			<div class="modal-body">
     <?php
					
					$form = ActiveForm::begin([
							'id' => 'customer-export-form',
							'type' => 'horizontal',
							'action' => Ui::to( 'item/scannedItems?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => [
									'enctype' => 'multipart/form-data' 
							] 
					] );
					?>
<?php

				
					$cols = [
							'username' => 'Username',
							'user_email' => 'User Email',
							'computer_name' => 'Computer Name',
							'item' => 'Item',
							'bar_code' => 'Bar Code',
							'quantity' => 'Quantity',
							'sale_rate' => 'Sale Rate',
							'base_price' => 'Base Price',
							'mrp' => 'MRP',
							'created_at' => 'Created At',
					];
					?>
<div class="form-group ">
					<label for="ItemStock_item_id"
						class="control-label col-md-3 required"> </label>
					<div class="col-md-9">
			<?php echo $form->checkboxListRow($model,'columns',$cols); ?>
		</div>
				</div>

				<div class="form-actions">
		<?php
		
		echo Button::widget([
				'buttonType' => 'submit',
				'type' => 'primary',
				'label' => 'Export',
				'htmlOptions' => [
						'id' => 'form-export'
				]
		] );
		?>
	</div>
<?php ActiveForm::end(); ?>
      </div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"
					id="close_modal">Close</button>
			</div>
		</div>

	</div>
</div>
<section class="content-header">

	<h1><?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?></h1>
</section>

<section class="content">
	<div class="">
		<div class="col-md-12 col-xs-12">
			<div class="box">

				</br>
				<div class="">
					<?php $form = ActiveForm::begin([
						'id' => 'stock-adjust-log-form',
						'type' => 'horizontal',
						'enableAjaxValidation' => true,
						'htmlOptions' => ['enctype' => 'multipart/form-data'],
					]);
					?>
					<?php echo $form->datepickerRow(
						$model,
						'start_date',
						[
							'hint' => 'Click inside! to select a date.',
							'prepend' => '<i class="icon-calendar"></i>',
							'options' => ['format' => 'yyyy-mm-dd']
						]
					); ?>

					<?php echo $form->datepickerRow(
						$model,
						'end_date',
						[
							'hint' => 'Click inside! to select a date.',
							'prepend' => '<i class="icon-calendar"></i>',
							'options' => ['format' => 'yyyy-mm-dd']
						]
					)

					; ?>
				</div>

				<div class="form-actions" style="padding: 13px;">
					<?php echo Button::widget([
						'buttonType' => 'submit',
						'type' => 'primary',
						'label' => 'Search',
					]); ?>
				</div>

				<?php ActiveForm::end(); ?>

				<?php

				// $model->scannedItemsearch();
				if (isset(Yii::$app->session['gross_total'])) {
					$gross_total = Yii::$app->session['gross_total'];

				} else {
					$gross_total = 0;
				}

				if (isset(Yii::$app->session['gross_total_amt'])) {
					$gross_total_amt = Yii::$app->session['gross_total_amt'];

				} else {
					$gross_total_amt = 0;
				}
				?>
				<div class="col-md-12">
					<div class="table-responsive customgridwidth">

						<?php echo GridView::widget([
							'id' => 'order-grid',
							'type' => 'striped bordered condensed',
							'dataProvider' => $model->scannedItemsearch(),
							'pager' => true,
							'filter' => $model,
							'columns' => [
								//'id',
						
								[
									'attribute' => 'user_id',
									'header' => '<a>Username</a>',
									'value' => function ($data, $key, $index) { return isset($data->createUser)?$data->createUser:""; },
									'filter'=>User::getAllUserOptions(),
								],
								[
									'header' => '<a>User Email</a>',
									'value' => function ($data, $key, $index) { return $data->user_email; },
								],
								'computer_name',
								[
									'header' => '<a>Item</a>',
									'value' => function ($data, $key, $index) { return isset($data->getItemDetail)?$data->getItemDetail ? $data->getItemDetail->item : "":""; },
								],
								'bar_code',
								[
									'header' => '<a>Quantity</a>',
									'value' => function ($data, $key, $index) { return $data->qty; },
								],
								[
									'header' => '<a>Sale Rate</a>',
									'value' => function ($data, $key, $index) { return $data->sale_rate; },
								],
								[
									'header' => '<a>Base Price</a>',
									'value' => function ($data, $key, $index) { return $data->base_price; },
								],
								[
									'header' => '<a>MRP</a>',
									'value' => function ($data, $key, $index) { return $data->mrp; },
								],
								'created_at'
							],
						]); ?>

					</div>
				</div>
			</div>
		</div>
	</div>
</section>