<?php
/**
 * Ported from protected/views/b2bpurchaseBill/userwise.php.
 */

use app\components\Ui;
use app\models\Item;
use app\models\Order;
use app\widgets\ActionColumn;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\EChosenWidget;
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

<style>
.btn-info.export-btn {
	background-color: #00c0ef;
	border-color: #00acd6;
	margin-left: 16px;
	margin-top: 10px;
}
.mb-10 {
    margin-bottom: 1rem;
}
</style>

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
							'action' => Ui::to( 'b2bpurchaseBill/userWiseExport?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => [
									'enctype' => 'multipart/form-data' 
							] 
					] );
					?>
<?php

				
					$cols = [
							'username' => 'Username',
							'amount' => 'Net Amount'
				
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
  <div class="">
    <div class="col-md-12 col-xs-12">
      <div class="box">
         <div class="box-header"><h3 class="box-title"><?php echo  Html::encode($model->label(2));?></h3></div>
        <div class="box-body">
     <!--   <a target = "_blank" href="<?php echo Ui::to('order/userwisePdf');?>" class="btn btn-warning pull-right">
		Pdf</a>-->
		</br>
          <div class="">
               <?php $form = ActiveForm::begin([
	'id' => 'stock-adjust-log-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);

$enddate=Yii::$app->session ['end_date'];

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
					'value'=>$enddate,
								'options'=>['format'=>'yyyy-mm-dd']])

; ?>
</div>
<div class="col-md-6">
<div class="form-actions mb-10">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		]); ?>
	</div>
	</div>
<?php /*?>
<div class="form-group">
<label class="control-label col-md-3">
Item
</label>
<div class="col-md-9">
<?php echo Html::activeListBox($model, 'item_id',Item::getActiveItems(), array('class'=>'chosen', 'multiple'=>true, 'data-placeholder'=>'Select Item')) ?>
</div>
</div>	*/?>


<?php 
   
?>
 <?php echo EChosenWidget::widget([
    // the select selector
    'selector'=>'.chosen',
    // Chosen options
]);?>

	

<?php ActiveForm::end(); ?>

<?php 

$model->userwisesearch();
if(isset(Yii::$app->session ['gross_total'])){
	$gross_total = Yii::$app->session ['gross_total'];
	
}else{
	$gross_total = 0;
}

if(isset(Yii::$app->session ['gross_total_amt'])){
	$gross_total_amt = Yii::$app->session ['gross_total_amt'];

}else{
	$gross_total_amt = 0;
}?>
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
 
<?php echo GridView::widget([
	'id' => 'order-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->userwisesearch(),
	'filter' => $model,
	'columns' => [
		//'id',
		
			[
					'header' => '<a>Username</a>',
					'value' => function ($data, $key, $index) { return isset($data->createUser)?$data->createUser:""; },
					//'filter'=>Order::getStatusOptions(),
			],
			[
					'header' => '<a>Taxable Amount</a>',
					'value' => function ($data, $key, $index) { return $data->getTotalUserwiseGrossAmount(); },
				//	'footer'=>$gross_total
					//'filter'=>Order::getStatusOptions(),
			],
				
			/*array(
					'header' => '<a>Gross Amount old</a>',
					'value' => function ($data, $key, $index) { return $data->getTotalNetAmount(); },
				//	'footer'=>$gross_total_amt
					//'filter'=>Order::getStatusOptions(),
			),*/
			
			
			[
					'header' => '<a>Gross Amount</a>',
					'value' => function ($data, $key, $index) { return $data->getTotalB2bUserwiseGrossAmountData(); },
				
			],
	    
	    [
	        'header' => '<a>Total Refund Amount</a>',
	        'value' => function ($data, $key, $index) { return $data->getUserTotalRefundAmountData(); },
	        
	    ],
	    
	    [
	        'header' => '<a>Total Discount Amount</a>',
	        'value' => function ($data, $key, $index) { return $data->getUserTotalDiscountAmountData(); },
	        
	    ],
	    
	    
			
			[
					'header' => '<a>Net Amount</a>',
					'value' => function ($data, $key, $index) { return $data->getTotalUserwiseNetAmountData(); },
				
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