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
'email',
'contact_no',
'secondary_contact_no',
'address:html',
'tax_no:html',
array(
				'name' => 'status',
				'type' => 'raw',
				'value'=>$model->getStatusOptions($model->status),
				),

'create_time',
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
			'name' => 'organization',
			'type' => 'raw',
			'value' => $model->organization !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->organization)), array('organization/view', 'id' => GxActiveRecord::extractPkValue($model->organization, true))) : null,
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
<?php  //$this->AddPanel($model->getRelationLabel('mrns'), $model->getRelatedDataProvider('mrns'),	'mrns','mrn');?>
<?php  //$this->AddPanel($model->getRelationLabel('mrnDetails'), $model->getRelatedDataProvider('mrnDetails'),	'mrnDetails','mrnDetail');?>
<?php  //$this->AddPanel($model->getRelationLabel('mrs'), $model->getRelatedDataProvider('mrs'),	'mrs','mrs');?>
<?php  //$this->AddPanel($model->getRelationLabel('mrsDetails'), $model->getRelatedDataProvider('mrsDetails'),	'mrsDetails','mrsDetail');?>
<?php  //$this->AddPanel($model->getRelationLabel('purchaseBills'), $model->getRelatedDataProvider('purchaseBills'),	'purchaseBills','purchaseBill');?>
<?php  //$this->AddPanel($model->getRelationLabel('purchaseBillDetails'), $model->getRelatedDataProvider('purchaseBillDetails'),	'purchaseBillDetails','purchaseBillDetail');?>
<?php  //$this->AddPanel($model->getRelationLabel('purchaseOrders'), $model->getRelatedDataProvider('purchaseOrders'),	'purchaseOrders','purchaseOrder');?>
<?php  //$this->AddPanel($model->getRelationLabel('purchaseOrderDetails'), $model->getRelatedDataProvider('purchaseOrderDetails'),	'purchaseOrderDetails','purchaseOrderDetail');?>
<?php  //$this->AddPanel($model->getRelationLabel('vendors'), $model->getRelatedDataProvider('vendors'),	'vendors','vendor');?>
<?php  $this->EndPanel(); ?>

<?php   $this->widget('CommentPortlet', array(
	'model' => $model,
));
?>
</section>