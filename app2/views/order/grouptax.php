<?php
/**
 * Ported from protected/views/order/grouptax.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Customer;
use app\models\OrderItem;
use app\models\PaymentMode;
use app\widgets\ActionColumn;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\GridView;
use app\widgets\Menu;
?>
<?php
$this->params['breadcrumbs'] = [
		$model->label ( 2 ) => [
				'index'
		],
		Yii::t ( 'app', 'Manage' )
];

$this->registerJs("
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$.fn.yiiGridView.update('order-item-grid', {
		data: $(this).serialize()
	});
	return false;
});
" );
?>


<style>
.btn-info.export-btn {
	background-color: #00c0ef;
	border-color: #00acd6;
	margin-left: 16px;
	margin-top: 10px;
}
</style>

 

<section class="content-header">
	<h1><?php echo 'Department with group tax wise report' ; ?></h1>
</section>

<?php 
/*
	       * echo Menu::widget(array(
	       * 'type' => 'pills',
	       * 'stacked' => false,
	       * 'items' => array(
	       * array('label' => 'Export',
	       * 'url' => array('orderItem/admin' ,'exportCSV'=>'1',
	       *
	       *
	       * ),
	       *
	       * ),
	       * ),
	       * ));
	       */
?>

<ul class="nav nav-pills" id="yw2">
	<li><button type="button" class="btn btn-info export-btn"
			data-toggle="modal" data-target="#myModal">Export</button></li>
</ul>

<!-- Modal -->
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
							'action' => Ui::to( 'order/groupTax?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => [
									'enctype' => 'multipart/form-data' 
							] 
					] );
					?>
<?php

					$cols = [
							'taxable' => 'Taxable',
							'bill_date' => 'Bill Date',
							'Gst' => 'Gst',
							'Cgst_per' => 'Cgst(%age)',
							'Sgst_per' => 'Sgst(%age)',
							'Cess_per' => 'Cess(%age)',
							'Igst_per' => 'Igst(%age)',
							'Cgst' => 'Cgst',
							'Sgst' => 'Sgst',
							'Cess' => 'Cess',
							'Igst' => 'Igst',
							'Amount' => 'Amount'
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
<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">
				<div class="box-header">
					<h3 class="box-title"><?php echo  'Report';?></h3>
				</div>
				<div class="box-body">
					<div class="">
					<?php if(empty($model->start_date)){
						Yii::$app->session ['order_item_start_date'] = '';
					}
					if(empty($model->end_date)){
						Yii::$app->session ['order_item_end_date'] = '';
					}?>
					
							       <?php $form = ActiveForm::begin([
	'id' => 'stock-adjust-log-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>

<div class="col-md-6">

<?php echo $form->datepickerRow($model, 'start_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
						'options'=>['format'=>'yyyy-mm-dd']])

; ?>
</div>
<div class="col-md-6">
<?php echo $form->datepickerRow($model, 'end_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
								'options'=>['format'=>'yyyy-mm-dd']])

; ?>
</div>

	<div class="form-actions pull-left">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		]); ?>
	</div>

<?php ActiveForm::end(); ?>
					
						<div class="col-md-12">
						<?php $model->groupTaxsearch ();?>
						<?php if(isset(Yii::$app->session ['group_tax_gst'])){
						$gst = Yii::$app->session ['group_tax_gst'];
						}else{
							$gst = 0;
						}
						if(isset(Yii::$app->session ['group_tax_cgst'])){
							$cgst = Yii::$app->session ['group_tax_cgst'];
						}else{
							$cgst = 0;
						}
						if(isset(Yii::$app->session ['group_tax_sgst'])){
							$sgst = Yii::$app->session ['group_tax_sgst'];
						}else{
							$sgst = 0;
						}
						if(isset(Yii::$app->session ['group_tax_cess'])){
							$cess = Yii::$app->session ['group_tax_cess'];
						}else{
							$cess = 0;
						}
						if(isset(Yii::$app->session ['group_tax_igst'])){
							$igst = Yii::$app->session ['group_tax_igst'];
						}else{
							$igst = 0;
						}
						if(isset(Yii::$app->session ['group_tax_total'])){
							$tax_total = Yii::$app->session ['group_tax_total'];
						}else{
							$tax_total = 0;
						}
						if(isset(Yii::$app->session ['group_taxable_total'])){
							$taxable_total = Yii::$app->session ['group_taxable_total'];
						}else{
							$taxable_total = 0;
						}
						
						?>
						
							<div class="table-responsive customsmallgridwidth">
							
								
<?php

echo GridView::widget([
		'id' => 'order-item-grid',
		'type' => 'striped bordered condensed',
		'dataProvider' => $model->groupTaxsearch (),
		'filter' => $model,
		'pager'=>true,
		'columns' => [
				// 'id',
				[
						'header' => 'Bill Date',
						'attribute' =>'order_id',
						'value' => function ($data, $key, $index) { return isset($data->order)?$data->order->bill_date:""; }
				],
					[
						'header' => 'Taxable',
						'value' => function ($data, $key, $index) { return $data->getTotalItemTaxableAmount(); }, 
						'footer'=>$taxable_total
				],
				[
						'header' => 'Mode of Payment',
						'attribute' =>'mode_of_payment',
						'value' => function ($data, $key, $index) { return isset($data->order)?$data->order->modePayment:""; },
						'filter'=>Gx::listData(PaymentMode::find()->where(['type_id'=>0])->orderBy(['id' => SORT_DESC])->all()),
				]
				,
				/* array (
						'header' => 'Customer',
						'attribute' =>'customer_id',
						'value' => function ($data, $key, $index) { return isset($data->order)?$data->order->customer:""; },
						'filter' => Gx::listData(Customer::class)
				)
				, */
				[
						'header' => 'Gst',
						'value' => function ($data, $key, $index) { return $data->getOrderTotalgstAmount(); },
						'footer'=>$gst,
				],
				[
						'header' => 'Cgst(%age)',
						'value' => function ($data, $key, $index) { return $data->cgst_per; }
				],
				[
						'header' => 'Sgst(%age)',
						'value' => function ($data, $key, $index) { return $data->sgst_per; }
				],
				[
						'header' => 'Cess(%age)',
						'value' => function ($data, $key, $index) { return $data->cess_per; }
				],
				[
						'header' => 'Igst(%age)',
						'value' => function ($data, $key, $index) { return $data->igst_per; }
				],
				[
						'header' => 'Cgst',
						'value' => function ($data, $key, $index) { return $data->getGroupTaxCgstAmount(); },
						'footer'=>$cgst,
				],
				[
						'header' => 'Sgst',
						'value' => function ($data, $key, $index) { return $data->getGroupTaxSgstAmount(); },
						'footer'=>$sgst,
				],
				[
						'header' => 'Cess',
							'value' => function ($data, $key, $index) { return $data->getGroupTaxCessAmount(); },
						'footer'=>$cess,
				],
				[
						'header' => 'Igst',
						'value' => function ($data, $key, $index) { return $data->getGroupTaxIgstAmount(); },
						'footer'=>$igst,
				],
				[
						'header' => 'Amount',
						'value' => function ($data, $key, $index) { return $data->getGroupTaxOrderTotalAmount(); },
						'footer'=>$tax_total,
				],
				
				// 'item_detail_id',
				
				
		/*
		'discount_amt',
		'tax_id',
		'tax_amount',
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>OrderItem::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>OrderItem::getTypeOptions(),
				),
		'update_time',
		'updated_by',
		*/
		/* array(
			'class' => ActionColumn::class,
			'htmlOptions' => array('nowrap'=>'nowrap'),
		), */
	] 
] );
?>

							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<script>
$('#form-export').click(function(){
	$('#close_modal').trigger('click');
});
</script>
