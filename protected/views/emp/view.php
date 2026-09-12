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
'code',
'name',
'email',
'contact_no',
array(
				'name' => 'gender_id',
				'type' => 'raw',
				'value'=>isset($model->gender_id)?$model->getGenderOptions($model->gender_id):'',
				),
			array(
					'name' => 'role_id',
					
					'value'=>$model->getRoleValues(),
			),
'date_of_birth',
'date_of_joining',
'permanent_address:html',
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
'temp_address:html',
			array(
					'name' => 'temp_city_id',
					'value'=>isset($model->tempcity)?$model->tempcity:'',
			),
			array(
					'name' => 'temp_state_id',
					'value'=>isset($model->tempstate)?$model->tempstate:'',
			),
			array(
					'name' => 'temp_country_id',
					'value'=>isset($model->tempcountry)?$model->tempcountry:'',
			),
			array(
					'name' => 'outlet_id',
					'value'=>isset($model->outlet)?$model->outlet:'',
			),
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
array(
			'name' => 'designation',
			'type' => 'raw',
			'value' => $model->designation !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->designation)), array('designation/view', 'id' => GxActiveRecord::extractPkValue($model->designation, true))) : null,
			),
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
<?php  $this->AddPanel($model->getRelationLabel('empShifts'), $model->getRelatedDataProvider('empShifts'),	'empShifts','empShift');?>
<?php  //$this->AddPanel($model->getRelationLabel('users'), $model->getRelatedDataProvider('users'),	'users','user');?>
<?php  $this->EndPanel(); ?>

</section>