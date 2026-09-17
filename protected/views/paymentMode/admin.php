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
	$.fn.yiiGridView.update('payment-mode-grid', {
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
</section>

<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
         <div class="box-header"><h3 class="box-title"><?php echo  GxHtml::encode($model->label(2));?></h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">

<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'payment-mode-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => array(
		'id',
		'title',
		 array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>PaymentMode::getTypeOptions(),
				),
	/*	array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>PaymentMode::getStatusOptions(),
				),
		'update_time',
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			), */
			array(
			
					'header'=>'<a>Actions</a>',
					'class'=>'FaButtonColumn',
					'template' => '{view}{update}', //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
							'view'=>array(
									'visible'=>'$data->checkPermission ("paymentMode/view")=="true"',
									'url' =>'Yii::app()->controller->createUrl("paymentMode/view", array("id" => $data->id))',
									'label'=>'View',
									'options'=>array('class'=>'view'),
										
							),
							'update'=>array(
									'visible'=>'$data->checkPermission ("paymentMode/update")=="true"',
									'url' =>'Yii::app()->controller->createUrl("paymentMode/update", array("id" => $data->id))',
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