<?php
$this->breadcrumbs = array (
		$model->label ( 2 ) => array (
				'index' 
		),
		Yii::t ( 'app', 'Manage' ) 
);

Yii::app ()->clientScript->registerScript ( 'search', "
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
			
				Yii::app()->session['order_item_start_date'] ='';
				Yii::app()->session['order_item_end_date'] ='';
				
			
		}   */
		
	/*	if(empty($model->min_amt) && empty($model->max_amt ))
		{
		
			Yii::app()->session['order_item_min_amt'] ='';
			Yii::app()->session['order_item_max_amt'] ='';
		
		
		} */
		?> 

<section class="content-header">
	<h1><?php echo Yii::t('app', 'Manage') . ' : ' . GxHtml::encode($model->label(2)); ?></h1>
</section>

<?php 
/*
	       * $this->widget('bootstrap.widgets.TbMenu', array(
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
					
					$form = $this->beginWidget ( 'bootstrap.widgets.TbActiveForm', array (
							'id' => 'customer-export-form',
							'type' => 'horizontal',
							'action' => Yii::app ()->createUrl ( 'orderItem/admin?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => array (
									'enctype' => 'multipart/form-data' 
							) 
					) );
					?>
<?php

					$cols = array (
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
				<div class="box-header">
					<h3 class="box-title"><?php echo  GxHtml::encode($model->label(2));?></h3>
				</div>
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
	<div class="form-actions pull-left">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		)); ?>
	</div>

<?php $this->endWidget(); ?>
						<div class="col-md-12">
							<div class="table-responsive customsmallgridwidth">
								
<?php

$this->widget ( 'bootstrap.widgets.TbGridView', array (
		'id' => 'order-item-grid',
		'type' => 'striped bordered condensed',
		'dataProvider' => $model->search (),
		'filter' => $model,
		'pager'=>true,
		'columns' => array (
				// 'id',
					array (
						'header' => 'Bill No',
							'name'=>'order_id',
						'value' => '$data->order->getOrderBillNo()' 
				),
				array (
						'header' => 'Bill Date',
						'name'=>'bill_date',
						'value' => 'isset($data->order)?$data->order->bill_date:""'
				),
				array (
						'header' => 'Barcode',
						'name'=>'item_detail_id',
						'value' => 'isset($data->itemDetail)?$data->itemDetail->bar_code:""',
						'filterHtmlOptions'=>array('class'=>'item_detail_bar_code'),
				)
				,
				array (
						'header' => 'Item',
						'name'=>'item_id',
						'value' => '$data->getItemName()',
						'filterHtmlOptions'=>array('class'=>'item_detail_bar_code'),
				)
				,
				array (
						'header' => 'Customer',
						'name'=>'customer_id',
						'value' => 'isset($data->order)?$data->order->customer:""',
						'filter' => GxHtml::listDataEx ( Customer::model ()->findAllByAttributes ( [], array('order'=>'name ASC') ) )
				)
				,
				array (
						'header' => 'Employee',
						'name'=>'create_user_id',
						'value' => 'isset($data->order)?$data->order->createUser:""',
						'filter' => GxHtml::listDataEx ( User::model ()->findAllByAttributes ( array('role_id'=>7) ,array('order'=>'full_name ASC') ) )
				)
				,
				// 'item_detail_id',
				'qty',
				array (
						'header' => 'Refund Qty',
					//	'name'=>'refund_qty',
						'value' => '$data->getOrderRefundQty()'
				
				)
				,
				array (
						'header' => 'Mrp',
						'name'=>'mrp',
						'value'=>'$data->getItemOrderMrp()',
						
				)
				,
			//	'price',
				'discount_amt',
				'tax_amount',
				array (
						'header' => 'Total Amt',
						'name'=>'total_amt',
						'value' => '$data->total_amt'
				
				)
				,
		/*
		'discount_amt',
		'tax_id',
		'tax_amount',
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>OrderItem::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>OrderItem::getTypeOptions(),
				),
		'update_time',
		'updated_by',
		*/
		/* array(
			'class'=>'bootstrap.widgets.TbButtonColumn',
			'htmlOptions' => array('nowrap'=>'nowrap'),
		), */
	) 
) );
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