<?php

$this->breadcrumbs = array(
	$model->label(2) => array('index'),
	GxHtml::valueEx($model),
);


?>

<section class="content">
<div class="page-header">
<h1 class="pull-left"><?php echo GxHtml::encode(GxHtml::valueEx($model)); ?></h1>


<?php   $this->widget('bootstrap.widgets.TbButtonGroup', array(
	'buttons'=>$this->menu,
	'type'=>'success',
	'htmlOptions'=>array('class'=> 'pull-right'),
	));

	?>
<div class="clearfix"></div>


</div>

<?php $this->widget('bootstrap.widgets.TbDetailView', array(
	'data' => $model,
	'attributes' => array(
'id',
	'gross_amt',
			'tax_amt',
		'discount_amt',
//'other_charge',
'total_amt',
array(
			'name' => 'outlet_id',
			'type' => 'raw',
			'value' => $model->outlet !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->outlet)), array('outlet/view', 'id' => GxActiveRecord::extractPkValue($model->outlet, true))) : null,
			),
array(
			'name' => 'vendor_id',
			'type' => 'raw',
			'value' => $model->vendor !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->vendor)), array('vendor/view', 'id' => GxActiveRecord::extractPkValue($model->vendor, true))) : null,
			),
			// array(
			// 		'name' => 'credit_note_id',
			// 		'type' => 'raw',
			// 		'value' => $model->creditNote !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->creditNote)), array('creditNote/view', 'id' => GxActiveRecord::extractPkValue($model->creditNote, true))) : null,
			// ),
array(
				'name' => 'status',
				'type' => 'raw',
				'value'=>$model->getStatusOptions($model->status),
				),
/* array(
				'name' => 'type_id',
				'type' => 'raw',
				'value'=>$model->getTypeOptions($model->type_id),
				), */
'create_time',
'credit_note_no',
'credit_note_date',
'create_user_id',
'updated_by',
	),
)); ?>
 <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'item-return-item-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $itemReturnItem->search(),
	'filter' => $itemReturnItem,
	'columns' => array(
		//'id',
			array(
					'name'=>'item_id',
					'value'=>'GxHtml::valueEx($data->item)',
					'filter'=>GxHtml::listDataEx(Item::model()->findAllAttributes(null, true)),
			),
			array(
					'name'=>'item_detail_id',
					'value'=>'GxHtml::valueEx($data->itemDetail)',
					'filter'=>GxHtml::listDataEx(ItemDetail::model()->findAllAttributes(null, true)),
			),
			array(
			'name'=>'type_id',
				'header' => 'Sent In Api',
				'value'=>'$data->getAPIOptions($data->type_id)',
				'filter'=>ItemReturnItem::getAPIOptions(),
				),
			'mrp',
			'price',
			'sale_rate',
				
		
		//	 'free',
			'qty',
			'discount',
			'discount_amt',
			'discount1',
			'discount_amt1',
			array(
					'header'=>'Tax',
					'value'=>'GxHtml::valueEx($data->tax)',
			
			),
			array(
    				'visible'=>$itemReturnItem->getGSTTrue($itemReturnItem->vendor_id,$itemReturnItem->outlet_id) == true,
    				'name'=>'cgst_per',
    				'value'=>'$data->cgst_per',
    					
    		),
			array(
					'visible'=>$itemReturnItem->getGSTTrue($itemReturnItem->vendor_id,$itemReturnItem->outlet_id) == true,
					'name'=>'sgst_per',
					'value'=>'$data->sgst_per',
						
			),
			array(
					'visible'=>$itemReturnItem->getGSTTrue($itemReturnItem->vendor_id,$itemReturnItem->outlet_id) == true,
					'name'=>'cess_per',
					'value'=>'$data->cess_per',
			
			),
			
			array(
					'visible'=>$itemReturnItem->getGSTTrue($itemReturnItem->vendor_id,$itemReturnItem->outlet_id) == true,
					'name'=>'cgst_amt',
					'value'=>'$data->cgst_amt',
			
			),
			array(
					'visible'=>$itemReturnItem->getGSTTrue($itemReturnItem->vendor_id,$itemReturnItem->outlet_id) == true,
					'name'=>'sgst_amt',
					'value'=>'$data->sgst_amt',
						
			),
			array(
					'visible'=>$itemReturnItem->getGSTTrue($itemReturnItem->vendor_id,$itemReturnItem->outlet_id) == true,
					'name'=>'cess_amt',
					'value'=>'$data->cess_amt',
			
			),
			array(
					'visible'=>$itemReturnItem->getGSTTrue($itemReturnItem->vendor_id,$itemReturnItem->outlet_id) == false,
					'name'=>'igst_per',
					'value'=>'$data->igst_per',
			
			),
			array(
					'visible'=>$itemReturnItem->getGSTTrue($itemReturnItem->vendor_id,$itemReturnItem->outlet_id) == false,
					'name'=>'igst_amt',
					'value'=>'$data->igst_amt',
						
			),
			
			
		//	'other_charge',
			'total_amt',
		//	'vendor_id',
		//	'outlet_id',
		/*
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>ItemReturn::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>ItemReturn::getTypeOptions(),
				),
		'credit_note_id',
		'updated_by',
		*/
			
	),
)); ?>
</div>
</div>
</div>
</section>