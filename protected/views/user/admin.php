<?php

$this->breadcrumbs = array(
$model->label(2) => array('index'),
Yii::t('app', 'Manage'),
);


Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$.fn.yiiGridView.update('user-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<section class="content-header">
  <h1> <?php echo Yii::t('app', 'Manage') . ' : ' . GxHtml::encode($model->label(2)); ?> </h1>
  <?php //echo CHtml::link('Delete',array('user/empty'));?>
<?php $this->widget('bootstrap.widgets.TbButtonGroup', array(
	'buttons'=>$this->menu,
	'type'=>'success',
	'htmlOptions'=>array('class'=> 'pull-right bttn-box'),
));
?>
</section>



<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
         <div class="box-header"><h3 class="box-title">Users</h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
 
<?php  $this->widget('bootstrap.widgets.TbGridView', array(
	//$this->widget('zii.widgets.grid.CGridView', array(

'id' => 'user-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'pager' => array(
	'class' => 'CLinkPager',
'htmlOptions' => array('class' => 'pager'
),
),
	'columns' => array(
		'id',
		'full_name',
		'username',
		'email',
		//'lat',
		//'long',
		'contact_no',
			array(
					'name' => 'role_id',
					
					'value'=>'isset($data->role)?$data->role:""',
					'filter'=>User::getAllRoleOptions(),
			),
			array(
					
					'header'=>'<a>Status</a>',
					'class'=>'FaButtonColumn',
					'template' => '{inactivate}{activate}',  //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
							'activate'=>array(
									'visible'=>'$data->state_id=='.User::STATUS_INACTIVE,
									'url' =>'Yii::app()->controller->createUrl("user/toggle", array("id" => $data->id))',
									'label'=>'activate',
									'options'=>array('class'=>'update'),
										
							),
							'inactivate'=>array(
									'visible'=>'$data->state_id=='.User::STATUS_ACTIVE,
									'url' =>'Yii::app()->controller->createUrl("user/toggle", array("id" => $data->id))',
									'label'=>'inactivate',
									'options'=>array('class'=>'update'),
			
							)
					)
			),
/*
 'date_of_birth',
 'about_me:html',
 'address',
 'postal_code',
 'country',
 'city',
 array(
 'name' => 'state',
 'value'=>'$data->getStatusOptions($data->state)',
 'filter'=>User::getStatusOptions(),
 ),
 'lang',
 'image_file',
 'is_passenger',
 'is_dispatcher',
 'is_driver',
 'role_id',
 array(
 'name' => 'state_id',
 'value'=>'$data->getStatusOptions($data->state_id)',
 'filter'=>User::getStatusOptions(),
 ),
 array(
 'name' => 'type_id',
 'value'=>'$data->getTypeOptions($data->type_id)',
 'filter'=>User::getTypeOptions(),
 ),
 'last_visit_time',
 'last_action_time',
 'last_password_change',
 array(
 'name' => 'is_active',
 'value' => '($data->is_active === 0) ? Yii::t(\'app\', \'No\') : Yii::t(\'app\', \'Yes\')',
 'filter' => array('0' => Yii::t('app', 'No'), '1' => Yii::t('app', 'Yes')),
 ),
 'login_error_count',
 */
			array(
			
					'header'=>'<a>Status</a>',
					'class'=>'FaButtonColumn',
					'template' => '{view}{update}', //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
							'view'=>array(
									'visible'=>'$data->checkPermission ("user/view")=="true"',
									'url' =>'Yii::app()->controller->createUrl("user/view", array("id" => $data->id))',
									'label'=>'View',
									'options'=>array('class'=>'view'),
										
							),
							'update'=>array(
									'visible'=>'$data->checkPermission ("user/update")=="true"',
									'url' =>'Yii::app()->controller->createUrl("user/update", array("id" => $data->id))',
									'label'=>'Update',
									'options'=>array('class'=>'update'),
			
							)
					)
			),
),
)); ?>

</div>
</div>
</div>
</div>
</div>
</div>
</div>
</section>