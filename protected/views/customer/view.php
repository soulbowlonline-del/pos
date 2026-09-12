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
'email',
'fax',
'address:html',
			array(
					'name' => 'city_id',
			      'value'=>isset($model->city)?$model->city:'',
			),
array(
				'name' => 'state_id',
				'value'=>isset($model->state)?$model->state:'',
				),
			array(
					'name' => 'country_id',
					'value'=>isset($model->country)?$model->country:'',
			),

'zip_code',
'opening_balance',
'credit_limit',
'payment_days',
'contact_no',
array(
				'name' => 'status',
				'type' => 'raw',
				'value'=>$model->getStatusOptions($model->status),
				),
/* array(
				'name' => 'type_id',
				'type' => 'raw',
				'value'=>$model->getTypeOptions($model->type_id),
				), */
'create_time',
'update_time',
/* array(
			'name' => 'createUser',
			'type' => 'raw',
			'value' => $model->createUser !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->createUser)), array('user/view', 'id' => GxActiveRecord::extractPkValue($model->createUser, true))) : null,
			),
array(
			'name' => 'updatedBy',
			'type' => 'raw',
			'value' => $model->updatedBy !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->updatedBy)), array('user/view', 'id' => GxActiveRecord::extractPkValue($model->updatedBy, true))) : null,
			), */
	),
)); ?>

<?php
 $this->StartPanel(); ?>
<?php  $this->AddPanel($model->getRelationLabel('orders'), $model->getRelatedDataProvider('orders'),	'orders','order','_list');?>
<?php  //$this->AddNewPanel($model->getRelationLabel('Items'), $model->getRelatedDataProvider('customerorders'),	'customerorders','order','_details',$model);?>

<?php // $this->AddPanel($model->getRelationLabel('orderHolds'), $model->getRelatedDataProvider('orderHolds'),	'orderHolds','orderHold');?>
<?php // $this->AddPanel($model->getRelationLabel('orderRefunds'), $model->getRelatedDataProvider('orderRefunds'),	'orderRefunds','orderRefund');?>
<?php  $this->EndPanel(); ?>

</section>