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

 

<section class="content-header">
	<h1><?php echo Yii::t('app', 'B2b Department with group tax wise report') ; ?></h1>
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
							'action' => Yii::app ()->createUrl ( 'order/b2bReport?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => array (
									'enctype' => 'multipart/form-data' 
							) 
					) );
					?>
<?php

					$cols = array (
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
					<h3 class="box-title"><?php echo  'Report';?></h3>
				</div>
				<div class="box-body">
					<div class="">
					<?php if(empty($model->start_date)){
						Yii::app ()->session ['order_b2b_start_date'] = '';
					}
					if(empty($model->end_date)){
						Yii::app ()->session ['order_b2b_end_date'] = '';
					}?>
					
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
		'dataProvider' => $model->b2bTaxsearch (),
		'filter' => $model,
		'pager'=>true,
		'columns' => array (
				// 'id',
				array (
						'header' => 'Bill Date',
						'name'=>'order_id',
						'value' => 'isset($data->order)?$data->order->bill_date:""'
				),
				array (
						'header' => 'Bill No',
						'name'=>'id',
						'value' => 'isset($data->order)?$data->order->getOrderBillNo():""'
				),
				 array (
						'header' => 'Customer',
						//'name'=>'customer_id',
						'value' => '$data->getItemCustomerName()'
				),  
					array (
						'header' => 'Taxable',
						'value' => '$data->getTotalItemB2bTaxableAmount()', 
					
				),
				
				
				array (
						'header' => 'Gst',
						'value' => '$data->getB2BOrdertotalgstAmount()',
						
				),
				array (
						'header' => 'Cgst(%age)',
						'value' => '$data->cgst_per'
				),
				array (
						'header' => 'Sgst(%age)',
						'value' => '$data->sgst_per'
				),
				array (
						'header' => 'Cess(%age)',
						'value' => '$data->cess_per'
				),
				array (
						'header' => 'Igst(%age)',
						'value' => '$data->igst_per'
				),
				array (
						'header' => 'Cgst',
						'value' => '$data->getB2BGroupTaxCgstAmount()',
						
				),
				array (
						'header' => 'Sgst',
						'value' => '$data->getB2BGroupTaxSgstAmount()',
					
				),
				array (
						'header' => 'Cess',
							'value' => '$data->getB2BGroupTaxCessAmount()',
						
				),
				array (
						'header' => 'Igst',
						'value' => '$data->getB2BGroupTaxIgstAmount()',
						
				),
				array (
						'header' => 'Amount',
						'value' => '$data->getB2BGroupTaxOrderTotalAmount()',
						
				),
				
				// 'item_detail_id',
				
				
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
