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
	$.fn.yiiGridView.update('item-category-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content-header">

<h1><?php echo Yii::t('app', 'Manage') . ' : ' . GxHtml::encode($model->label(2)); ?></h1>
<?php //echo CHtml::link('Delete',array('user/empty'));?>
<?php $this->widget('bootstrap.widgets.TbButtonGroup', array(
	'buttons'=>$this->menu,
	'type'=>'success',
	'htmlOptions'=>array('class'=> 'pull-right'),
));
?>


<?php    $this->widget('bootstrap.widgets.TbMenu', array(
       'type' => 'pills',
       'stacked' => false,
       'items' => array(
        		array('label' => 'Export',
        				'url' => array('itemCategory/admin' ,'exportCSV'=>'1',
        						
        		
        		),
       		
       		),
       ),
   ));  ?>

<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
         <div class="box-header"><h3 class="box-title"><?php echo  GxHtml::encode($model->label(2));?></h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">


<?php $this->widget ( 'bootstrap.widgets.TbExtendedGridView', array (
	'id' => 'item-category-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'pager' => true,
	'filter' => $model,
	'columns' => array(
			'id',
		'title',
			array(
					'header' => 'Parent',
					'value'=>'$data->getParentValue($data->parent_id)',
						
			),
			array(
					'name' => 'status',
					'value'=>'$data->getStatusOptions($data->status)',
					'filter'=>ItemCategory::getStatusOptions(),
			),
		/*
		'gender_id',
		'religion',
		array(
			'name'=>'class_id',
			'value'=>'GxHtml::valueEx($data->class)',
			'filter'=>GxHtml::listDataEx(Classes::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'section_id',
			'value'=>'GxHtml::valueEx($data->section)',
			'filter'=>GxHtml::listDataEx(Section::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'school_id',
			'value'=>'GxHtml::valueEx($data->school)',
			'filter'=>GxHtml::listDataEx(School::model()->findAllAttributes(null, true)),
			),
		'parent_id',
		'roll_no',
		'image_file',
		array(
				'name' => 'state_id',
				'value'=>'$data->getStatusOptions($data->state_id)',
				'filter'=>Student::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>Student::getTypeOptions(),
				),
		'update_time',
		*/
			array(
						
					'header'=>'<a>Status</a>',
					'class'=>'FaButtonColumn',
					'template' => '{view}{update}', //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
							'view'=>array(
									'visible'=>'$data->checkPermission ("itemCategory/view")=="true"',
									'url' =>'Yii::app()->controller->createUrl("itemCategory/view", array("id" => $data->id))',
									'label'=>'View',
									'options'=>array('class'=>'view'),
			
							),
							'update'=>array(
									'visible'=>'$data->checkPermission ("itemCategory/update")=="true"',
									'url' =>'Yii::app()->controller->createUrl("itemCategory/update", array("id" => $data->id))',
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