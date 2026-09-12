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
.mb-10 {
    margin-bottom: 1rem;
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
	<h1>B2b Department with group tax wise report</h1>
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
							'action' => Yii::app ()->createUrl ( 'b2bpurchaseBillDetail/taxwise?exportCSV=1' ),
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
							
							'customer' => 'Customer',
							'taxable' => 'Taxable',
							'gst_per' => 'Tax',
							'cgst_per' => 'Cgst(%)',
							'sgst_per' => 'Sgst(%)',
							'cess_per' => 'Cess(%)',
							'igst_per' => 'Igst(%)',
							'cgst_amt' => 'Cgst Amount',
							'sgst_amt' => 'sgst Amount',
							'cess_amt' => 'cess Amount',
							'igst_amt' => 'Igst Amount',
							
							'round_amt' => ' Amount'
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
								
<?php

$this->widget ( 'bootstrap.widgets.TbGridView', array (
		'id' => 'order-item-grid',
		'type' => 'striped bordered condensed',
		'dataProvider' => $model->b2bTaxwisesearch (),
		'filter' => $model,
		'pager'=>true,
		'columns' => array (
				// 'id',
					array (
						'header' => 'Bill No',
							'name'=>'purchase_bill_id',
						'value' => '$data->getOrderBillNo()' 
				),
				array (
						'header' => 'Bill Date',
						'name'=>'bill_date',
						'value' => '$data->getOrderBillDate()'
				),
				
				array (
						'header' => 'Customer',
						// 'name'=>'vendor',
						'value' => '$data->getVendorName()',
						
				)
				,
					array (
						'header' => 'Taxable',
						
							'value' => '$data->price-($data->discount_amt1 + $data->discount_amt) ', 
				
				),
					array (
						'header' => 'Tax',
						
						'value' => '$data->getTaxTitle()',
				
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
						'value' => '$data->cgst_amt',
						
				),
				array (
						'header' => 'Sgst',
						'value' => '$data->sgst_amt',
					
				),
				array (
						'header' => 'Cess',
							'value' => '$data->cess_amt',
						
				),
				array (
						'header' => 'Igst',
						'value' => '$data->igst_amt',
						
				),
				
				
				array (
						'header' => 'Amount',
						'name'=>'amount',
						'value' => '$data->amount',
				
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