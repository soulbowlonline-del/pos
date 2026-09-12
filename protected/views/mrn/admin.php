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
	$.fn.yiiGridView.update('mrn-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content">
<div class="page-header">
	<h1><?php echo Yii::t('app', 'Manage') . ' : ' . GxHtml::encode($model->label(2)); ?></h1>
<div class="clearfix"></div>
</div>
<div class="table-responsive">


<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'mrn-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => array(
		//'id',
		'code',
		//'mrs_date',
	//	'mrs_update_date',
		'mrs_req_date',
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>Mrn::getStatusOptions(),
				),
			array(
					'name'=>'outlet_id',
					'value'=>'GxHtml::valueEx($data->outlet)',
					'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
			array(
					'name'=>'vendor_id',
					'value'=>'GxHtml::valueEx($data->vendor)',
					'filter'=>GxHtml::listDataEx(Vendor::model()->findAllAttributes(null, true)),
			),
		/*
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>Mrn::getTypeOptions(),
				),
		'remarks:html',
		'update_time',
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'outlet_id',
			'value'=>'GxHtml::valueEx($data->outlet)',
			'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'mrs_id',
			'value'=>'GxHtml::valueEx($data->mrs)',
			'filter'=>GxHtml::listDataEx(Mrs::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'organization_id',
			'value'=>'GxHtml::valueEx($data->organization)',
			'filter'=>GxHtml::listDataEx(Organization::model()->findAllAttributes(null, true)),
			),
		*/array(
				
						'header'=>'<a>Change Status</a>',
						'class'=>'CButtonColumn',
						'template' => '{approve}', //include the standard buttons plus the new status button
						'htmlOptions'=> array('style'=>'width:80px'),
						'buttons'=>array(
								'approve'=>array(
										'visible'=>'$data->status=='.Mrn::STATUS_UNAPPROVED,
										'url' =>'Yii::app()->controller->createUrl("mrn/approve", array("id" => $data->id))',
										'label'=>'Approve',
										'options'=>array('class'=>'approve'),
											
								)
						)
				),
				array(
				
						'header'=>'<a>Status</a>',
						'class'=>'CButtonColumn',
						'template' => '{view}', //include the standard buttons plus the new status button
						'htmlOptions'=> array('style'=>'width:80px'),
						'buttons'=>array(
								'view'=>array(
										'visible'=>'$data->checkPermission ("mrn/view")=="true"',
										'url' =>'Yii::app()->controller->createUrl("mrn/view", array("id" => $data->id))',
										'label'=>'View',
										'options'=>array('class'=>'view'),
											
								)
						)
				),
	),
)); ?>
				</div>
</section>