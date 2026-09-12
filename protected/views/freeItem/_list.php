
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'free-item-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
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
			'class' => 'CxButtonColumn',
		),
	),
)); ?>