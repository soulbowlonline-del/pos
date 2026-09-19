<?php
/**
 * Ported from protected/views/orderItem/admin.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Customer;
use app\models\OrderItem;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\GridView;
use app\widgets\Menu;
use yii\helpers\Html;
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

 <?php 	/*   if(empty($model->start_date) && empty($model->end_date ))
		{
			
				Yii::$app->session['order_item_start_date'] ='';
				Yii::$app->session['order_item_end_date'] ='';
				
			
		}   */
		
	/*	if(empty($model->min_amt) && empty($model->max_amt ))
		{
		
			Yii::$app->session['order_item_min_amt'] ='';
			Yii::$app->session['order_item_max_amt'] ='';
		
		
		} */
		?> 

<section class="content-header">
	<h1><?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?></h1>
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
							'action' => Ui::to( 'orderItem/admin?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => [
									'enctype' => 'multipart/form-data' 
							] 
					] );
					?>
<?php

					$cols = [
							'bill_no' => 'Bill No',
							'bill_date' => 'Bill Date',
							'bar_code' => 'Barcode',
							'customer_id' => 'Customer',
							'employee_id' => 'Employee',
							'item' => 'Item',
							'qty' => 'Quantity',
							'mrp' => 'Mrp',
							'discount_amt' => 'Discounted Amount',
							'tax_amount' => 'Tax Amount',
							'total_amt' => 'Total Amount'
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
					<h3 class="box-title"><?php echo  Html::encode($model->label(2));?></h3>
				</div>
				<div class="box-body">
					<div class="">
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
<div class="col-md-6">
<?php echo $form->textFieldRow($model,'min_amt');?>
</div>
<div class="col-md-6">
<?php echo $form->textFieldRow($model,'max_amt');?>
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
							<div class="table-responsive customsmallgridwidth">
								
<?php

echo GridView::widget([
		'id' => 'order-item-grid',
		'type' => 'striped bordered condensed',
		'dataProvider' => $model->search (),
		'filter' => $model,
		'pager'=>true,
		'columns' => [
				// 'id',
					[
						'header' => 'Bill No',
							'attribute' =>'order_id',
						'value' => function ($data) { return $data->order->getOrderBillNo(); } 
				],
				[
						'header' => 'Bill Date',
						'attribute' =>'bill_date',
						'value' => function ($data) { return isset($data->order)?$data->order->bill_date:""; }
				],
				[
						'header' => 'Barcode',
						'attribute' =>'item_detail_id',
						'value' => function ($data) { return isset($data->itemDetail)?$data->itemDetail->bar_code:""; },
						'filterInputOptions' =>['class'=>'item_detail_bar_code'],
				]
				,
				[
						'header' => 'Item',
						'attribute' =>'item_id',
						'value' => function ($data) { return $data->getItemName(); },
						'filterInputOptions' =>['class'=>'item_detail_bar_code'],
				]
				,
				[
						'header' => 'Customer',
						'attribute' =>'customer_id',
						'value' => function ($data) { return isset($data->order)?$data->order->customer:""; },
						'filter' => Gx::listData( Customer::findAll( [], ['order'=>'name ASC'] ) )
				]
				,
				[
						'header' => 'Employee',
						'attribute' =>'create_user_id',
						'value' => function ($data) { return isset($data->order)?$data->order->createUser:""; },
						'filter' => Gx::listData( User::findAll( ['role_id'=>7] ,['order'=>'full_name ASC'] ) )
				]
				,
				// 'item_detail_id',
				'qty',
				[
						'header' => 'Refund Qty',
					//	'attribute' =>'refund_qty',
						'value' => function ($data) { return $data->getOrderRefundQty(); }
				
				]
				,
				[
						'header' => 'Mrp',
						'attribute' =>'mrp',
						'value' => function ($data) { return $data->getItemOrderMrp(); },
						
				]
				,
			//	'price',
				'discount_amt',
				'tax_amount',
				[
						'header' => 'Total Amt',
						'attribute' =>'total_amt',
						'value' => function ($data) { return $data->total_amt; }
				
				]
				,
		/*
		'discount_amt',
		'tax_id',
		'tax_amount',
		array(
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>OrderItem::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
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