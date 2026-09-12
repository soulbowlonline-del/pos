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
'req_qty',
'bal_qty',
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
'charge_amount',
'extra_charges',
'remarks:html',
'create_time',
'update_time',
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
array(
			'name' => 'itemDetail',
			'type' => 'raw',
			'value' => $model->itemDetail !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->itemDetail)), array('itemDetail/view', 'id' => GxActiveRecord::extractPkValue($model->itemDetail, true))) : null,
			),
'item_id',
array(
			'name' => 'purchaseOrder',
			'type' => 'raw',
			'value' => $model->purchaseOrder !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->purchaseOrder)), array('purchaseOrder/view', 'id' => GxActiveRecord::extractPkValue($model->purchaseOrder, true))) : null,
			),
array(
			'name' => 'outlet',
			'type' => 'raw',
			'value' => $model->outlet !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->outlet)), array('outlet/view', 'id' => GxActiveRecord::extractPkValue($model->outlet, true))) : null,
			),
	),
)); ?>


<?php   $this->widget('CommentPortlet', array(
	'model' => $model,
));
?>