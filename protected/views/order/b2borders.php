<?php

$this->breadcrumbs = array(
	$model->label(2) => array('index'),
	Yii::t('app', 'Manage'),
);


Yii::app()->clientScript->registerScript('search', "
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
			
				Yii::app()->session['start_date'] ='';
				Yii::app()->session['end_date'] ='';
				
			
		}
		if(empty($model->min_amt) && empty($model->max_amt ))
		{
				
			Yii::app()->session['order_min_amt'] ='';
			Yii::app()->session['order_max_amt'] ='';
		
				
		}?> 
<section class="content-header">

	<h1><?php echo Yii::t('app', 'Manage') . ' : ' . GxHtml::encode($model->label(2)); ?></h1>
</section>

<?php    /* $this->widget('bootstrap.widgets.TbMenu', array(
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
					
					$form = $this->beginWidget ( 'bootstrap.widgets.TbActiveForm', array (
							'id' => 'customer-export-form',
							'type' => 'horizontal',
							'action' => Yii::app ()->createUrl ( 'order/b2borders?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => array (
									'enctype' => 'multipart/form-data' 
							) 
					) );
					?>
<?php

					$cols = array (
							'bill_no' => 'Bill No',
							'start_date' => 'Bill Date',
							'customer_id' => 'Customer',
							'bill_amount' => 'Total Amount',
							'tax_amount' => 'Tax Amount',
							'outlet' => 'Outlet'
				
					);
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
		
		$this->widget ( 'bootstrap.widgets.TbButton', array (
				'buttonType' => 'submit',
				'type' => 'primary',
				'label' => 'Export',
				'htmlOptions' => array (
						'id' => 'form-export'
				)
		) );
		?>
	</div>
<?php $this->endWidget(); ?>
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
         <div class="box-header"><h3 class="box-title"><?php echo  GxHtml::encode($model->label(2));?></h3></div>
        <div class="box-body">
          <div class="">
            <?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'stock-adjust-log-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>

<div class="col-md-6">

<?php echo $form->datepickerRow($model, 'start_date',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
						'options'=>array('format'=>'yyyy-mm-dd')))

; ?>
</div>
<div class="col-md-6">
<?php echo $form->datepickerRow($model, 'end_date',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
								'options'=>array('format'=>'yyyy-mm-dd')))

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
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		)); ?>
	</div>
	</div>

<?php $this->endWidget(); ?>
            <div class="col-md-12">
<div class="table-responsive customsmallgridwidth">
 
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'order-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
		'pager'=>true,
	'columns' => array(
		//'id',
			array(
					'name'=>'bill_no',
					'value'=>'$data->getOrderBillNo()',
					
			),
		//'bill_no',
		array(
			'header' => 'Receiving Date',
					'name'=>'start_date',
					'value'=>'$data->start_date',
					
			),
			// array(
					// 'header' => '<a>Customer</a>',
					// 'name'=>'customer_id',
					// 'value'=>'isset($data->customer)?$data->customer:""',
					// 'filter' => GxHtml::listDataEx ( Customer::model ()->findAllAttributes ( null, true ) )
			// ),
			
			array (
					'header' => 'Employee',
					'name'=>'create_user_id',
					'value' => 'isset($data->createUser)?$data->createUser:""',
					'filter' => GxHtml::listDataEx ( User::model ()->findAllByAttributes ( array('role_id'=>7) ) )
			)
			,
			array(
					'header' => '<a>Total Amount</a>',
					'name'=>'bill_amount',
					'value'=>'$data->bill_amount',
			
			),
			//'total_amt',
			'discount_amount',
			// array(
					// 'header' => '<a>Refund Amount</a>',
						
					// 'value'=>'$data->getOrderRefundAmount()',
			
			// ),
			// array(
					// 'header' => '<a>Refund By</a>',
			
					// 'value'=>'$data->getOrderRefundBy()',
						
			// ),
			//'paid_amt',
			array(
					'header' => '<a>Tax Amount</a>',
					
					'value'=>'$data->tax_amount',
				
			),
		/* 	array(
					'name'=>'mode_of_payment',
					'value'=>'GxHtml::valueEx($data->modePayment)',
					'filter'=>GxHtml::listDataEx(PaymentMode::model()->findAllByAttributes(array('type_id'=>0))),
			),
			array(
					'name'=>'mode_of_delivery',
					'value'=>'GxHtml::valueEx($data->modeDelivery)',
					'filter'=>GxHtml::listDataEx(PaymentMode::model()->findAllByAttributes(array('type_id'=>1))),
			),
			
			array(
					'name' => 'type_id',
					'value'=>'$data->getTypeOptions($data->type_id)',
					'filter'=>Order::getTypeOptions(),
			), */
			array(
					'header' => '<a>Outlet</a>',
					'value'=>'isset($data->outlet)?$data->outlet:""',
					//'filter'=>Order::getStatusOptions(),
			),
			array(
			
					'header'=>'<a>Status</a>',
					'class'=>'FaButtonColumn',
					'template' => '{view}', //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
							'view'=>array(
								//	'visible'=>'$data->state_id=='.User::STATUS_INACTIVE,
									'url' =>'Yii::app()->controller->createUrl("order/details/", array("id" => $data->id))',
									'label'=>'view',
									'options'=>array('class'=>'view'),
			
							),
							
					)	
						
			),
		//'qty',
		/*
		'discount_amt',
		'total_amt',
		'paid_amt',
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>Order::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>Order::getTypeOptions(),
				),
		'city_id',
		array(
				'name' => 'state_id',
				'value'=>'$data->getStatusOptions($data->state_id)',
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
			'class'=>'bootstrap.widgets.TbButtonColumn',
			'htmlOptions' => array('nowrap'=>'nowrap'),
		), */
	),
)); ?>

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