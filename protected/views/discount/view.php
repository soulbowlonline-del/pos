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
'title',
			'applicable_amt',
'amount',
'start_date',
'end_date',
			'start_time',
			'end_time',
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

<?php
 $this->StartPanel(); ?>
<?php  //$this->AddPanel($model->getRelationLabel('itemDiscounts'), $model->getRelatedDataProvider('itemDiscounts'),	'itemDiscounts','itemDiscount');?>
<?php // $this->AddPanel($model->getRelationLabel('orderHoldItems'), $model->getRelatedDataProvider('orderHoldItems'),	'orderHoldItems','orderHoldItem');?>
<?php // $this->AddPanel($model->getRelationLabel('orderItems'), $model->getRelatedDataProvider('orderItems'),	'orderItems','orderItem');?>
<?php // $this->AddPanel($model->getRelationLabel('orderRefundItems'), $model->getRelatedDataProvider('orderRefundItems'),	'orderRefundItems','orderRefundItem');?>
<?php  $this->EndPanel(); ?>

<?php /*   $this->widget('CommentPortlet', array(
	'model' => $model,
)); */
?>
</section>