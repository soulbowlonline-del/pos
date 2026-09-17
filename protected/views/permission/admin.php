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
	$.fn.yiiGridView.update('permission-grid', {
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
        <div class="box-header"><h3 class="box-title">Permissions</h3></div>
           <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
    

<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'permission-grid',
	'type'=>'striped bordered condensed',
		'pager'=>true,
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => array(
		'id',
		'title',
		'url',
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>Permission::getStatusOptions(),
				),
		/* array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>Permission::getTypeOptions(),
				),
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			), */
			array(
					'header'=>'Actions',
					'class'=>'FaButtonColumn',
					'template' => '{view}{update}{delete}'
		
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