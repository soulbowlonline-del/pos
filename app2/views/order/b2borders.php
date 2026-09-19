<?php
/**
 * Ported from protected/views/order/b2borders.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Customer;
use app\models\Order;
use app\models\PaymentMode;
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
<?php ?>
<style>
.btn-info.export-btn {
	background-color: #00c0ef;
	border-color: #00acd6;
	margin-left: 16px;
	margin-top: 10px;
}
.mb-10 {
    margin-bottom: 10px;
}
</style>
   <?php 	if(empty($model->start_date) && empty($model->end_date ))
		{
			
				Yii::$app->session['start_date'] ='';
				Yii::$app->session['end_date'] ='';
				
			
		}
		if(empty($model->min_amt) && empty($model->max_amt ))
		{
				
			Yii::$app->session['order_min_amt'] ='';
			Yii::$app->session['order_max_amt'] ='';
		
				
		}?> 
<section class="content-header">

	<h1><?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?></h1>
</section>

<?php    /* echo Menu::widget(array(
       'type' => 'pills',
       'stacked' => false,
       'items' => array(
        		array('label' => 'Export',
        				'url' => array('order/admin' ,'exportCSV'=>'1',
        						
        		
        		),
       		
       		),
       ),
   )); */  ?>
   
   <ul class="nav nav-pills" id="yw2">
	<li><button type="button" class="btn btn-info export-btn" data-toggle="modal"
			data-target="#myModal">Export</button></li>
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
							'action' => Ui::to( 'order/b2borders?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => [
									'enctype' => 'multipart/form-data' 
							] 
					] );
					?>
<?php

					$cols = [
							'bill_no' => 'Bill No',
							'start_date' => 'Bill Date',
							'customer_id' => 'Customer',
							'bill_amount' => 'Total Amount',
							'tax_amount' => 'Tax Amount',
							'outlet' => 'Outlet'
				
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
         <div class="box-header"><h3 class="box-title"><?php echo  Html::encode($model->label(2));?></h3></div>
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

<div class="col-md-6">
	<div class="form-actions  mb-10">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		]); ?>
	</div>
	</div>

<?php ActiveForm::end(); ?>
            <div class="col-md-12">
<div class="table-responsive customsmallgridwidth">
 
<?php echo GridView::widget([
	'id' => 'order-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
		'pager'=>true,
	'columns' => [
		//'id',
			[
					'attribute' =>'bill_no',
					'value' => function ($data, $key, $index) { return $data->getOrderBillNo(); },
					
			],
		//'bill_no',
		[
			'header' => 'Receiving Date',
					'attribute' =>'start_date',
					'value' => function ($data, $key, $index) { return $data->start_date; },
					
			],
			// array(
					// 'header' => '<a>Customer</a>',
					// 'attribute' =>'customer_id',
					// 'value' => function ($data, $key, $index) { return isset($data->customer)?$data->customer:""; },
					// 'filter' => Gx::listData(Customer::class)
			// ),
			
			[
					'header' => 'Employee',
					'attribute' =>'create_user_id',
					'value' => function ($data, $key, $index) { return isset($data->createUser)?$data->createUser:""; },
					'filter' => Gx::listData( User::find()->where(['role_id'=>7])->all())
			]
			,
			[
					'header' => '<a>Total Amount</a>',
					'attribute' =>'bill_amount',
					'value' => function ($data, $key, $index) { return $data->bill_amount; },
			
			],
			//'total_amt',
			'discount_amount',
			// array(
					// 'header' => '<a>Refund Amount</a>',
						
					// 'value' => function ($data, $key, $index) { return $data->getOrderRefundAmount(); },
			
			// ),
			// array(
					// 'header' => '<a>Refund By</a>',
			
					// 'value' => function ($data, $key, $index) { return $data->getOrderRefundBy(); },
						
			// ),
			//'paid_amt',
			[
					'header' => '<a>Tax Amount</a>',
					
					'value' => function ($data, $key, $index) { return $data->tax_amount; },
				
			],
		/* 	array(
					'attribute' =>'mode_of_payment',
					'value' => function ($data, $key, $index) { return Gx::str($data->modePayment); },
					'filter'=>Gx::listData(PaymentMode::find()->where(array('type_id'=>0)->all())),
			),
			array(
					'attribute' =>'mode_of_delivery',
					'value' => function ($data, $key, $index) { return Gx::str($data->modeDelivery); },
					'filter'=>Gx::listData(PaymentMode::find()->where(array('type_id'=>1)->all())),
			),
			
			array(
					'attribute' => 'type_id',
					'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
					'filter'=>Order::getTypeOptions(),
			), */
			[
					'header' => '<a>Outlet</a>',
					'value' => function ($data, $key, $index) { return isset($data->outlet)?$data->outlet:""; },
					//'filter'=>Order::getStatusOptions(),
			],
			[
			
					'header'=>'<a>Status</a>',
					'class' => ActionColumn::class,
					'template' => '{view}', //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					'buttons'=>[
							'view'=>[
								//	'visible' => function ($data) { return $data->state_id==User::STATUS_INACTIVE; },
									'url' => function ($data) { return Ui::to("order/details/", ["id" => $data->id]); },
									'label'=>'view',
									'options'=>['class'=>'view'],
			
							],
							
					]	
						
			],
		//'qty',
		/*
		'discount_amt',
		'total_amt',
		'paid_amt',
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>Order::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>Order::getTypeOptions(),
				),
		'city_id',
		array(
				'attribute' => 'state_id',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->state_id); },
				'filter'=>Order::getStatusOptions(),
				),
		'country_id',
		'outlet_id',
		'address:html',
		'note:html',
		'update_time',
		'customer_id',
		'updated_by',
		*/
		/* array(
			'class' => ActionColumn::class,
			'htmlOptions' => array('nowrap'=>'nowrap'),
		), */
	],
]); ?>

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