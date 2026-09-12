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
			'name' => 'itemDetail',
			'type' => 'raw',
			'value' => $model->itemDetail !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->itemDetail)), array('itemDetail/view', 'id' => GxActiveRecord::extractPkValue($model->itemDetail, true))) : null,
			),
array(
			'name' => 'item',
			'type' => 'raw',
			'value' => $model->item !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->item)), array('item/view', 'id' => GxActiveRecord::extractPkValue($model->item, true))) : null,
			),
'batch_no',
'Qty',
array(
			'name' => 'outlet',
			'type' => 'raw',
			'value' => $model->outlet !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->outlet)), array('outlet/view', 'id' => GxActiveRecord::extractPkValue($model->outlet, true))) : null,
			),
array(
			'name' => 'vendor',
			'type' => 'raw',
			'value' => $model->vendor !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->vendor)), array('vendor/view', 'id' => GxActiveRecord::extractPkValue($model->vendor, true))) : null,
			),
array(
				'name' => 'type_id',
				'type' => 'raw',
				'value'=>$model->getTypeOptions($model->type_id),
				),
array(
				'name' => 'status',
				'type' => 'raw',
				'value'=>$model->getStatusOptions($model->status),
				),
'create_time',
'update_time',
	),
)); ?>


<?php   $this->widget('CommentPortlet', array(
	'model' => $model,
));
?>