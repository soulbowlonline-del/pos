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
'qty',
'discount',
'discount_amt',
'total_amt',
'paid_amt',
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
'city_id',
array(
				'name' => 'state_id',
				'type' => 'raw',
				'value'=>$model->getStatusOptions($model->state_id),
				),
'country_id',
'address:html',
'note:html',
'create_time',
'update_time',
'order_id',
'customer_id',
'updated_by',
	),
)); ?>

<?php
 $this->StartPanel(); ?>
<?php  $this->AddPanel($model->getRelationLabel('orderRefundItems'), $model->getRelatedDataProvider('orderRefundItems'),	'orderRefundItems','orderRefundItem');?>
<?php  $this->EndPanel(); ?>

<?php   $this->widget('CommentPortlet', array(
	'model' => $model,
));
?>