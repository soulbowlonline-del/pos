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
'item_code',
			'hsn_code',
'description:html',
'image_file:html',
			'min_qty',
			'max_qty',
			'reorder_qty',
array(
				'name' => 'item_type',
				'type' => 'raw',
				'value'=>$model->getTypeOptions($model->item_type),
				),
array(
				'name' => 'status',
				'type' => 'raw',
				'value'=>$model->getStatusOptions($model->status),
				),

'is_tax:boolean',
'is_discount:boolean',
array(
			'name' => 'category',
			'type' => 'raw',
			'value' => $model->category !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->category)), array('itemCategory/view', 'id' => GxActiveRecord::extractPkValue($model->category, true))) : null,
			),
array(
			'name' => 'subCompany',
			'type' => 'raw',
			'value' => $model->subCompany !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->subCompany)), array('itemCompanyCategory/view', 'id' => GxActiveRecord::extractPkValue($model->subCompany, true))) : null,
			),
array(
			'name' => 'company',
			'type' => 'raw',
			'value' => $model->company !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->company)), array('itemCompany/view', 'id' => GxActiveRecord::extractPkValue($model->company, true))) : null,
			),


	),
)); ?>

<?php
 $this->StartPanel(); ?>
<?php  $this->AddPanel($model->getRelationLabel('itemDetails'), $model->getRelatedDataProvider('itemDetails'),	'itemDetails','itemDetail');?>
<?php  $this->AddPanel($model->getRelationLabel('itemVendors'), $model->getRelatedDataProvider('itemVendors'),	'itemVendors','itemVendor');?>

<?php  $this->EndPanel(); ?>

<?php   $this->widget('CommentPortlet', array(
	'model' => $model,
));
?>