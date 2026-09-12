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
	$.fn.yiiGridView.update('item-company-category-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content">
<div class="page-header">
<h1 class="pull-left"><?php echo Yii::t('app', 'Manage') . ' : ' . GxHtml::encode($model->label(2)); ?></h1>
<?php //echo CHtml::link('Delete',array('user/empty'));?>
<?php $this->widget('bootstrap.widgets.TbButtonGroup', array(
	'buttons'=>$this->menu,
	'type'=>'success',
	'htmlOptions'=>array('class'=> 'pull-right'),
));
?>
<div class="clearfix"></div>
</div>
<div class="table-responsive customgridwidth">


<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'item-company-category-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => array(
		'id',
		'title',
		/* array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>ItemCompanyCategory::getTypeOptions(),
				), */
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>ItemCompanyCategory::getStatusOptions(),
				),
		//'update_time',
		/* array(
			'name'=>'company_id',
			'value'=>'GxHtml::valueEx($data->company)',
			'filter'=>GxHtml::listDataEx(ItemCompany::model()->findAllAttributes(null, true)),
			), */
		/*
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			),
		*/
			array(
			
					'header'=>'<a>Actions</a>',
					'class'=>'CButtonColumn',
					'template' => '{view}{update}', //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
							 	'view'=>array(
							 'visible'=>'$data->checkPermission ("itemCompanyCategory/view")=="true"',
									'url' =>'Yii::app()->controller->createUrl("itemCompanyCategory/view", array("id" => $data->id))',
									'label'=>'View',
									'options'=>array('class'=>'view'),
										
							), 
							'update'=>array(
									'visible'=>'$data->checkPermission ("itemCompanyCategory/update")=="true"',
									'url' =>'Yii::app()->controller->createUrl("itemCompanyCategory/update", array("id" => $data->id))',
									'label'=>'Update',
									'options'=>array('class'=>'update'),
			
							)
					)
			),
	),
)); ?>
</div>
</section>