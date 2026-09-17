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
	$.fn.yiiGridView.update('free-item-grid', {
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
	'id' => 'free-item-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => array(
		'id',
		'title',
		array(
			'name'=>'item_id',
			'value'=>'GxHtml::valueEx($data->item)',
			'filter'=>GxHtml::listDataEx(Item::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'item_detail_id',
			'value'=>'GxHtml::valueEx($data->itemDetail)',
			'filter'=>GxHtml::listDataEx(ItemDetail::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'item_category_id',
			'value'=>'GxHtml::valueEx($data->itemCategory)',
			'filter'=>GxHtml::listDataEx(ItemCategory::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'item_company_id',
			'value'=>'GxHtml::valueEx($data->itemCompany)',
			'filter'=>GxHtml::listDataEx(ItemCompany::model()->findAllAttributes(null, true)),
			),
		/*
		'qty',
		'stock_qty',
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>FreeItem::getTypeOptions(),
				),
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>FreeItem::getStatusOptions(),
				),
		'update_time',
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			),
		*/
		array(
			'header'=>'Actions',
			'class'=>'FaButtonColumn',
			'template'=>'{view}'
				
		),
	),
)); ?>
</div>
</section>