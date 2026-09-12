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
							'action' => Yii::app ()->createUrl ( 'order/b2bItemWiseExport?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => array (
									'enctype' => 'multipart/form-data' 
							) 
					) );
					?>
<?php

				
					$cols = array (
							'item_detail_id' => 'Bar Code',
							'item_id' => 'Item',
							'qty' => 'Quantity',
							'price' => 'MRP',
						  'tax_amount' => 'Tax Amount',
							'amount' => 'Total Amount',
				
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
          <div class="row">
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
<div class="col-md-6 mb-10">
<div class="form-actions">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		)); ?>
	</div>
	</div>

<?php /*?>
<div class="form-group">
<label for="inputEmail3" class="control-label col-md-3">
Item
</label>
<div class="col-md-9">
<?php echo CHtml::activeListBox($model, 'item_id',Item::getActiveItems(), array('class'=>'chosen', 'multiple'=>true, 'data-placeholder'=>'Select Item')) ?>
</div>
</div>*/?>	


<?php Yii::import('application.extensions.widgets.yii-chosen.EChosenWidget');
   
?>
 <?php $this->widget('EChosenWidget',array(
    // the select selector
    'selector'=>'.chosen',
    // Chosen options
));?>

	
<?php $this->endWidget(); ?>
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
 
								
<?php

/* $model->itemwisesearch ();

if(isset(Yii::app ()->session ['itemwise_total_amt'])){
	$itemwise_total = Yii::app ()->session ['itemwise_total_amt'];
}else{
	$itemwise_total =0;
} */
$this->widget ( 'bootstrap.widgets.TbGridView', array (
		'id' => 'order-item-grid',
		'type' => 'striped bordered condensed',
		'dataProvider' => $model->itemwisesearch (),
		'filter' => $model,
		'pager'=>true,
		'columns' => array (
				// 'id',
				/* 	array (
						'header' => 'Bill No',
						'value' => 'isset($data->order)?$data->order->bill_no:""' 
				), */
					// array (
							// 'header' => 'Bill Date',
							// 'value' => 'isset($data->order)?$data->order->bill_date:""'
					// ),
				array (
						'header' => 'Barcode',
						'name'=>'item_detail_id',
						'value' => 'isset($data->itemDetail)?$data->itemDetail->bar_code:""' 
				),
				array (
						'header' => 'Item',
						'name'=>'item_id',
						'value' => '$data->getItemName()'
				)
				,
				// 'item_detail_id',
				array (
						'header' => 'Quantity',
						
						'value' => '$data->approved_qty'
				)
				,
				array(
						'header'=>'MRP',
						'value'=>'$data->mrp',
						
				),
				/* array(
						'name'=>'discount_amt',
						'value'=>'$data->discount_amt',
						'footer'=>$model->getTotals($model->itemwisesearch()->getKeys(),'discount_amt','tbl_order_item'),
				), */
				array(
						'header'=>'Tax Amount',
						'value'=>'$data->cess_amt + $data->sgst_amt + $data->igst_amt + $data->cgst_amt',
						
				),
				//'qty',
				/* 'price',
				'discount_amt',
				'tax_amount', */
				array (
						'header' => 'Total Amount',
						'value' => '$data->amount',
						// 'footer'=>$model->getTotals($model->itemwisesearch()->getKeys(),'amount','tbl_b2bpurchase_bill_detail'),
				),
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