<?php

$this->breadcrumbs = array(
	$model->label(2) => array('index'),
	GxHtml::valueEx($model),
);


?>
<section class="content">
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
'title',
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
array(
			'name' => 'itemCategory',
			'type' => 'raw',
			'value' => $model->itemCategory !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->itemCategory)), array('itemCategory/view', 'id' => GxActiveRecord::extractPkValue($model->itemCategory, true))) : null,
			),
array(
			'name' => 'itemCompany',
			'type' => 'raw',
			'value' => $model->itemCompany !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->itemCompany)), array('itemCompany/view', 'id' => GxActiveRecord::extractPkValue($model->itemCompany, true))) : null,
			),
'qty',
'stock_qty',
/* array(
				'name' => 'type_id',
				'type' => 'raw',
				'value'=>$model->getTypeOptions($model->type_id),
				),
array(
				'name' => 'status',
				'type' => 'raw',
				'value'=>$model->getStatusOptions($model->status),
				), */
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
	),
)); ?>


</section>