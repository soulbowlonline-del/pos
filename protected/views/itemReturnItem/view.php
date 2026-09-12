<?php

$this->breadcrumbs = array(
	$model->label(2) => array('index'),
	GxHtml::valueEx($model),
);


?>

<div class="page-header">
<h1><?php echo GxHtml::encode(GxHtml::valueEx($model)); ?></h1>
</div>

<?php   $this->widget('bootstrap.widgets.TbButtonGroup', array(
	'buttons'=>$this->actions,
	'type'=>'success',
	'htmlOptions'=>array('class'=> 'pull-right'),
	));
?>

<?php $this->widget('bootstrap.widgets.TbDetailView', array(
	'data' => $model,
	'attributes' => array(
'id',
array(
			'name' => 'item',
			'type' => 'raw',
			'value' => $model->item !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->item)), array('item/view', 'id' => GxActiveRecord::extractPkValue($model->item, true))) : null,
			),
array(
			'name' => 'itemDetail',
			'type' => 'raw',
			'value' => $model->itemDetail !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->itemDetail)), array('itemDetail/view', 'id' => GxActiveRecord::extractPkValue($model->itemDetail, true))) : null,
			),
'mrp',
'price',
'sale_rate',
'free',
'qty',
'discount',
'discount_amt',
'discount1',
'discount_amt1',
'cgst_per',
'sgst_per',
'cess_per',
'cgst_amt',
'sgst_amt',
'cess_amt',
'igst_per',
'igst_amt',
'tax_id',
'other_charge',
'total_amt',
'vendor_id',
'outlet_id',
array(
				'name' => 'status',
				'type' => 'raw',
				'value'=>$model->getStatusOptions($model->status),
				),
array(
				'name' => 'type_id',
				'type' => 'raw',
				'value'=>$model->getTypeOptions($model->type_id),
				),
'return_id',
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


<?php   $this->widget('CommentPortlet', array(
	'model' => $model,
));
?>