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
'total_amt',

			array(
					'name' => 'vendor_id',
					'type' => 'raw',
					'value' => $model->vendor !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->vendor)), array('vendor/view', 'id' => GxActiveRecord::extractPkValue($model->vendor, true))) : null,
			),
array(
			'name' => 'outlet',
			'type' => 'raw',
			'value' => $model->outlet !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->outlet)), array('outlet/view', 'id' => GxActiveRecord::extractPkValue($model->outlet, true))) : null,
			),
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
array(
			'name' => 'createUser',
			'type' => 'raw',
			'value' => $model->createUser !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->createUser)), array('user/view', 'id' => GxActiveRecord::extractPkValue($model->createUser, true))) : null,
			),
array(
			'name' => 'updatedBy',
			'type' => 'raw',
			'value' => $model->updatedBy !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->updatedBy)), array('user/view', 'id' => GxActiveRecord::extractPkValue($model->updatedBy, true))) : null,
			),
	),
)); ?>
<div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'item-expire-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $itemExpireItem->search(),
	'filter' => $itemExpireItem,
		'pager'=>true,
		/* 'afterAjaxUpdate'=>"function(){
                                                       $.datepicker.setDefaults($.datepicker.regional['en']);
                                                        $('#Projects_projStart').datepicker({'dateFormat': 'yy-mm-dd'});
		
                                                }", */
	'columns' => array(
		//'id',
			array(
					'name'=>'item_id',
					'header'=>'Item',
					'value'=>'GxHtml::valueEx($data->item)',
					//	'filter'=>GxHtml::listDataEx(Item::model()->findAllAttributes(null, true)),
			),
			array(
					'name'=>'item_detail_id',
					'header'=>'Barcode',
					'value'=>'GxHtml::valueEx($data->itemDetail)',
					//	'filter'=>GxHtml::listDataEx(ItemDetail::model()->findAllAttributes(null, true)),
			),
			array(
					'name'=>'vendor_id',
					'value'=>'GxHtml::valueEx($data->vendor)',
					'filter'=>GxHtml::listDataEx(Vendor::model()->findAllAttributes(null, true)),
			),
			array(
					'name'=>'outlet_id',
					'value'=>'GxHtml::valueEx($data->outlet)',
					'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
			'qty',
		'mrp',
		'sale_rate',
	//	'free',
			array(
					'name'=>'total_amt',
					'value'=>'$data->total_amt',
					'footer'=>$model->getTotals($itemExpireItem->search()->getKeys(),'total_amt','tbl_item_expire_item'),
			),
			
// 			array(
// 					'header' => '<a>Create Time</a>',
// 					'name' => 'create_time',
// 					'value'=>'date("Y-m-d",strtotime($data->create_time))',
// 					'filter' => $this->widget('zii.widgets.jui.CJuiDatePicker',
// 							array(
// 									'model' => $model,
// 									'attribute' => 'create_time',
// 									'language' => 'en',
// 									'htmlOptions' => array(
// 											'id' => 'Projects_projStart',
// 											'dateFormat' => 'yy-mm-dd',
// 									),
// 									'options' => array(  // (#3)
// 											'showOn' => 'focus',
// 											'dateFormat' => 'yy-mm-dd',
// 											'showOtherMonths' => true,
// 											'selectOtherMonths' => false,
// 											'changeMonth' => false,
// 											'changeYear' => false,
// 									)
// 							),
// 							true),
			
// 			),
		/*
		'qty',
		'total_amt',
		'vendor_id',
		array(
			'name'=>'outlet_id',
			'value'=>'GxHtml::valueEx($data->outlet)',
			'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>ItemExpire::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>ItemExpire::getTypeOptions(),
				),
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			),
		*/
		/* array(
			'class'=>'bootstrap.widgets.TbButtonColumn',
			'htmlOptions' => array('nowrap'=>'nowrap'),
		), */
			/* array(
						
					'header'=>'<a>Action</a>',
					'class'=>'CButtonColumn',
					'template' => '{delete}', //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
								
							'delete'=>array(
			
									'url' =>'Yii::app()->controller->createUrl("itemExpireItem/delete", array("id" => $data->id))',
									'label'=>'Delete',
									'options'=>array('class'=>'update'),
										
							)
					)
			), */
	),
)); ?>
</div>
</div>
</div>
</section>