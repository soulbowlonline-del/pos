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
	$.fn.yiiGridView.update('user-role-grid', {
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
         <div class="box-header"><h3 class="box-title">User Roles</h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
  
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'user-role-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => array(
		'id',
		'title',
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>UserRole::getStatusOptions(),
				),
		
			array(
			
					'header'=>'<a>Status</a>',
					'class'=>'CButtonColumn',
					'template' => '{view}{update}', //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
							'view'=>array(
									'visible'=>'$data->checkPermission ("userRole/view")=="true"',
									'url' =>'Yii::app()->controller->createUrl("userRole/view", array("id" => $data->id))',
									'label'=>'View',
									'options'=>array('class'=>'view'),
										
							),
							'update'=>array(
									'visible'=>'$data->checkPermission ("userRole/update")=="true"',
									'url' =>'Yii::app()->controller->createUrl("userRole/update", array("id" => $data->id))',
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