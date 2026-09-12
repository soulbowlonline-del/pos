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
	$.fn.yiiGridView.update('item-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<?php if($model->name != ''){
	 Yii::app()->session['item_name'] = $model->name;
 }else{
 	Yii::app()->session['item_name'] = '';
 }?>
<section class="content-header">
  <h1> <?php echo Yii::t('app', 'Stock Report'); ?> </h1>
  
</section>
<?php    $this->widget('bootstrap.widgets.TbMenu', array(
       'type' => 'pills',
       'stacked' => false,
       'items' => array(
        		array('label' => 'Export',
        				'url' => array('item/report' ,'exportCSV'=>'1',
        						
        		
        		),
       		
       		),
       ),
   ));  ?>

<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
     <div class="box">
        <div class="box-header"><h3 class="box-title">Stock Report</h3></div>
          <div class="box-body">
              <?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'item-form',
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

	<div class="form-actions">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		)); ?>
	</div>

<?php $this->endWidget(); ?>



          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">


<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'item-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->reportstocksearch(),
		'pager' => true,
	'filter' => $model,
	'columns' => array(
	//	'id',
			array(
					'name'=>'item_code',
					'value'=>'$data->item_code',
					'filterHtmlOptions'=>array('class'=>'item_item_code_field'),
			
			),
			array(
					'header'=>'Bar Code',
					'name'=>'bar_code',
					'value'=>'$data->getItemBarcodes()',
						
			),
		'title',
			//'hsn_code',
			/* array(
					'name'=>'hsn_code',
					'value'=>'$data->hsn_code',
					'filterHtmlOptions'=>array('class'=>'item_hsn_code_field'),
			
			), */
			
		//'item_code',
			
			
			
			
				array(
					'name'=>'mrp',
					'value'=>'$data->mrp',
					'filterHtmlOptions'=>array('class'=>'item_mrp_field'),
						
			),
			array(
					'name'=>'purchase_price',
					'value'=>'$data->purchase_price',
					'filterHtmlOptions'=>array('class'=>'item_purchase_price_field'),
			
			),
			//'purchase_price',
			 array(
					'header'=>'Opening',
					'value'=>'$data->getOpeningQuantity()',
					'htmlOptions'=>array('class'=>'item_qty_field'),
			
			),
		 	array(
					'header'=>'Adjustment',
					'value'=>'$data->getAdjustmentQuantity()',
					'htmlOptions'=>array('class'=>'item_qty_field'),
						
			),
			array(
					'header'=>'Purchase/Added',
					'value'=>'$data->getAddedQuantity()',
					'htmlOptions'=>array('class'=>'item_qty_field'),
			
			), 
			array(
					'header'=>'Purchase Return',
					'value'=>'$data->getReturnQuantity()',
					'htmlOptions'=>array('class'=>'item_qty_field'),
						
			),
			array(
					'header'=>'Sale/Order',
					'value'=>'$data->getSoldQuantity()',
					'htmlOptions'=>array('class'=>'item_qty_field'),
			
			),
			array(
					'header'=>'Sale/Order Refund',
					'value'=>'$data->getRefundQuantity()',
					'htmlOptions'=>array('class'=>'item_qty_field'),
			
			),
			array(
					'header'=>'Expiry',
					'value'=>'$data->getExpiryQuantity()',
					'htmlOptions'=>array('class'=>'item_qty_field'),
						
			),
			array(
						'header'=>'Total Remain Qty',
					'value'=>'$data->getStockRemainingQuantity()',
					'htmlOptions'=>array('class'=>'item_qty_field'),
						
			),
		/*	*/
		
			/* array(
				'visible'=>'$data->checkPermission ("itemDetail/admin")=="true"',
					'header'=>'Vendor',
				'name'=>'vendor_id',
					'value'=>'$data->getLatestVendorName()',
				'filter'=>GxHtml::listDataEx(Vendor::model()->findAllAttributes(null, true)),
						
			),  */
			
			/* array(
					'name'=>'max_qty',
					'value'=>'$data->max_qty',
					'filterHtmlOptions'=>array('class'=>'item_max_qty_field'),
			
			), */
			
			/* array(
					'name'=>'min_qty',
					'value'=>'$data->min_qty',
					'filterHtmlOptions'=>array('class'=>'item_max_qty_field'),
			
			), */
			
			/* array(
					'name'=>'reorder_qty',
					'value'=>'$data->reorder_qty',
					'filterHtmlOptions'=>array('class'=>'item_max_qty_field'),
			
			), */
			
		/* 	array(
					'name' => 'status',
					'value'=>'$data->getStatusOptions($data->status)',
					'filter'=>Item::getStatusOptions(),
					'filterHtmlOptions'=>array('class'=>'item_status_field'),
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