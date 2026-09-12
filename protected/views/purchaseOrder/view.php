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
	'buttons'=>$this->actions,
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
'code',
'start_date',
'end_date',
'receiving_date',
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
'is_open_po:boolean',
'is_po_received:boolean',
'remarks:html',
'payment_terms:html',
'transport_mode',
'purchase_order_amount',
'charges_total_amount',
'discount_amount',
'frieght_charges',
'extra_charges',
'total_amount',
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
			'name' => 'mrn',
			'type' => 'raw',
			'value' => $model->mrn !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->mrn)), array('mrn/view', 'id' => GxActiveRecord::extractPkValue($model->mrn, true))) : null,
			),
array(
			'name' => 'organization',
			'type' => 'raw',
			'value' => $model->organization !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->organization)), array('organization/view', 'id' => GxActiveRecord::extractPkValue($model->organization, true))) : null,
			),
	),
)); ?>

<?php
 $this->StartPanel(); ?>
<?php  //$this->AddPanel($model->getRelationLabel('purchaseBills'), $model->getRelatedDataProvider('purchaseBills'),	'purchaseBills','purchaseBill');?>
<?php  $this->AddPanel($model->getRelationLabel('purchaseOrderDetails'), $model->getRelatedDataProvider('purchaseOrderDetails'),	'purchaseOrderDetails','purchaseOrderDetail');?>
<?php  $this->EndPanel(); ?>

<?php /*   $this->widget('CommentPortlet', array(
	'model' => $model,
)); */
?>