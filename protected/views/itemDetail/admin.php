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
	$.fn.yiiGridView.update('item-detail-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content-header">

<h1><?php echo Yii::t('app', 'Manage SubItems'); ?></h1>
<?php //echo CHtml::link('Delete',array('user/empty'));?>
<?php $this->widget('bootstrap.widgets.TbButtonGroup', array(
	'buttons'=>$this->menu,
	'type'=>'success',
	'htmlOptions'=>array('class'=> 'pull-right'),
));
?>
<br>



<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
         <div class="box-header"><h3 class="box-title"><?php echo 'Create SubItem';?></h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'item-detail-grid',
	'type'=>'striped bordered condensed',
		'pager'=>true,
	'dataProvider' => $model->adminsearch(),
	'filter' => $model,
	'columns' => array(
	//	'id',
	/* 	array(
			'name'=>'item_id',
			'value'=>'GxHtml::valueEx($data->item)',
			'filter'=>GxHtml::listDataEx(Item::model()->findAllAttributes(null, true)),
			), */
		'bar_code',
			'mrp',
		'open_stock_qty',
		
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>ItemDetail::getStatusOptions(),
				),
			array(
					'name'=>'tax_id',
					'value'=>'GxHtml::valueEx($data->tax)',
					'filter'=>GxHtml::listDataEx(Tax::model()->findAllAttributes(null, true)),
			),
			array(
					'name'=>'item_id',
					'value'=>'GxHtml::valueEx($data->item)',
					//'filter'=>$model->getItemOptions(),
			),
		/*'reorder_qty',
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>ItemDetail::getTypeOptions(),
				),
		array(
			'name'=>'tax_id',
			'value'=>'GxHtml::valueEx($data->tax)',
			'filter'=>GxHtml::listDataEx(Tax::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			),
		*/
			array(
			
					'header'=>'<a>Status</a>',
					'class'=>'FaButtonColumn',
					'template' => '{view}{update}{delete}', //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
							'view'=>array(
									'visible'=>'$data->checkPermission ("itemDetail/view")=="true"',
									'url' =>'Yii::app()->controller->createUrl("itemDetail/view", array("id" => $data->id))',
									'label'=>'View',
									'options'=>array('class'=>'view'),
										
							),
							'update'=>array(
									'visible'=>'$data->checkPermission ("itemDetail/update")=="true"',
									'url' =>'Yii::app()->controller->createUrl("itemDetail/update", array("id" => $data->id))',
									'label'=>'Update',
									'options'=>array('class'=>'update'),
			
							),
							'delete'=>array(
									'visible'=>'$data->checkPermission ("itemDetail/delete")=="true"',
									'url' =>'Yii::app()->controller->createUrl("itemDetail/delete", array("id" => $data->id))',
									'label'=>'Delete',
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