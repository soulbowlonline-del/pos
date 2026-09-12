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
	$.fn.yiiGridView.update('bill-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content-header">
  <h1><?php echo Yii::t('app', 'Manage') . ' : ' . GxHtml::encode($model->label(2)); ?></h1>
 
</section>



<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
         <div class="box-header"><h3 class="box-title"><?php echo GxHtml::encode($model->label(2)); ?></h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">



<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'bill-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => array(
		//'id',
		'bill_no',
			array(
					'name'=>'image_file1',
					'value'=>'$data->getNewImage($data->image_file1)',
					'type'=>'raw',
			),
			array(
					'name'=>'image_file2',
					'value'=>'$data->getNewImage($data->image_file2)',
					'type'=>'raw',
			),
			//	'image_file1:html',
			
			array(
					'name'=>'po_id',
					'value'=>'isset($data->po)?$data->po->id:""',
					//'filter'=>GxHtml::listDataEx(PurchaseOrder::model()->findAllAttributes(null, true)),
			),
		//'image_file3:html',
		/* array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>Bill::getTypeOptions(),
				), */
		/*
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>Bill::getStatusOptions(),
				),
		array(
			'name'=>'po_id',
			'value'=>'GxHtml::valueEx($data->po)',
			'filter'=>GxHtml::listDataEx(PurchaseOrder::model()->findAllAttributes(null, true)),
			),
		*/
			array(
						
					'header'=>'<a>Status</a>',
					'class'=>'CButtonColumn',
					'template' => '{update}{delete}', //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					
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