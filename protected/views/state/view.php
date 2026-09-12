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
array(
				'name' => 'status',
				'type' => 'raw',
				'value'=>$model->getStatusOptions($model->status),
				),

array(
			'name' => 'country',
			'type' => 'raw',
			'value' => $model->country !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->country)), array('country/view', 'id' => GxActiveRecord::extractPkValue($model->country, true))) : null,
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
<?php  //$this->AddPanel($model->getRelationLabel('cities'), $model->getRelatedDataProvider('cities'),	'cities','city');?>
<?php  //$this->AddPanel($model->getRelationLabel('orders'), $model->getRelatedDataProvider('orders'),	'orders','order');?>
<?php  //$this->AddPanel($model->getRelationLabel('orderHolds'), $model->getRelatedDataProvider('orderHolds'),	'orderHolds','orderHold');?>
<?php  //$this->AddPanel($model->getRelationLabel('orderRefunds'), $model->getRelatedDataProvider('orderRefunds'),	'orderRefunds','orderRefund');?>
<?php  //$this->AddPanel($model->getRelationLabel('organizations'), $model->getRelatedDataProvider('organizations'),	'organizations','organization');?>
<?php  //$this->AddPanel($model->getRelationLabel('outlets'), $model->getRelatedDataProvider('outlets'),	'outlets','outlet');?>
<?php  //$this->AddPanel($model->getRelationLabel('vendors'), $model->getRelatedDataProvider('vendors'),	'vendors','vendor');?>
<?php  $this->EndPanel(); ?>

<?php  /*  $this->widget('CommentPortlet', array(
	'model' => $model,
)); */
?>
</section>