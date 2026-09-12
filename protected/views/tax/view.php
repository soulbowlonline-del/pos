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
			'hrn_code',
'tax_val1',
'tax_val2',
			'tax_val3',
			'tax_val4',
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
<?php  //$this->AddPanel($model->getRelationLabel('itemDetails'), $model->getRelatedDataProvider('itemDetails'),	'itemDetails','itemDetail');?>
<?php  //$this->AddPanel($model->getRelationLabel('itemStocks'), $model->getRelatedDataProvider('itemStocks'),	'itemStocks','itemStock');?>
<?php  //$this->AddPanel($model->getRelationLabel('itemTaxes'), $model->getRelatedDataProvider('itemTaxes'),	'itemTaxes','itemTax');?>
<?php  $this->EndPanel(); ?>
</section>
