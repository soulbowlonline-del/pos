
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'item-detail-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
		array(
			'name'=>'item_id',
			'value'=>'GxHtml::valueEx($data->item)',
			'filter'=>GxHtml::listDataEx(Item::model()->findAllAttributes(null, true)),
			),
		'bar_code',
		'open_stock_qty',
	//	'reorder_qty',
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>ItemDetail::getStatusOptions(),
				),
		/*
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
			'header'=>'Action',
			'class' => 'CxButtonColumn',
			'template'=>'{view}{update}'
		),
	),
)); ?>