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
	$.fn.yiiGridView.update('role-permission-grid', {
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


<?php if(Yii::app()->user->hasFlash('success')){ ?>

<div class="alert alert-success"><?php echo Yii::app()->user->getFlash('success'); ?>
</div>
<?php } ?>
<?php if(Yii::app()->user->hasFlash('error')){ ?>

<div class="alert alert-danger"><?php echo Yii::app()->user->getFlash('error'); ?>
</div>
<?php } ?>




<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
        <div class="box-header"><h3 class="box-title">Role Permissions</h3></div>
           <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
  


<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'role-permission-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'pager'=>true,
	'filter' => $model,
	'columns' => array(
		'id',
		array(
			'name'=>'role_id',
			'value'=>'GxHtml::valueEx($data->role)',
			'filter'=>GxHtml::listDataEx(UserRole::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'permission_id',
			'value'=>'GxHtml::valueEx($data->permission)',
			'filter'=>GxHtml::listDataEx(Permission::model()->findAllAttributes(null, true)),
			),
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>RolePermission::getStatusOptions(),
				),
		
			/* array(
					'header'=>'Actions',
					'class' => 'CButtonColumn',
					'template' => '{view}{update}{delete}'
			
			), */
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