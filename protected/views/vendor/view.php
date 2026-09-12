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
'name',
			'contact_email',
'description:html',
'contact_person',
'person_designation',
'contact_no',
'secondary_contact_no',
'whatsapp_no',
'primary_address:html',
'secondary_address:html',
'tax_no',
'is_local_vendor:boolean',
array(
				'name' => 'status',
				'type' => 'raw',
				'value'=>$model->getStatusOptions($model->status),
				),


array(
			'name' => 'city',
			'type' => 'raw',
			'value' => $model->city !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->city)), array('city/view', 'id' => GxActiveRecord::extractPkValue($model->city, true))) : null,
			),
array(
			'name' => 'state',
			'type' => 'raw',
			'value' => $model->state !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->state)), array('state/view', 'id' => GxActiveRecord::extractPkValue($model->state, true))) : null,
			),
array(
			'name' => 'country',
			'type' => 'raw',
			'value' => $model->country !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->country)), array('country/view', 'id' => GxActiveRecord::extractPkValue($model->country, true))) : null,
			),
array(
			'name' => 'outlet',
			'type' => 'raw',
			'value' => $model->outlet !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->outlet)), array('outlet/view', 'id' => GxActiveRecord::extractPkValue($model->outlet, true))) : null,
			),
array(
			'name' => 'createUser',
			'type' => 'raw',
			'value' => $model->createUser !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->createUser)), array('user/view', 'id' => GxActiveRecord::extractPkValue($model->createUser, true))) : null,
			),

	),
)); ?>

<?php
 $this->StartPanel(); ?>
<?php  //$this->AddPanel($model->getRelationLabel('itemVendors'), $model->getRelatedDataProvider('itemVendors'),	'itemVendors','itemVendor');?>
<?php // $this->AddPanel($model->getRelationLabel('purchaseBills'), $model->getRelatedDataProvider('purchaseBills'),	'purchaseBills','purchaseBill');?>
<?php // $this->AddPanel($model->getRelationLabel('purchaseOrders'), $model->getRelatedDataProvider('purchaseOrders'),	'purchaseOrders','purchaseOrder');?>
<?php  $this->EndPanel(); ?>

<?php   $this->widget('CommentPortlet', array(
	'model' => $model,
));
?>
</section>