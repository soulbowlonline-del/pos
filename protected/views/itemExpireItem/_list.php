
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'item-expire-item-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
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
		'mrp',
		'sale_rate',
		'free',
		/*
		'qty',
		'total_amt',
		'vendor_id',
		array(
			'name'=>'outlet_id',
			'value'=>'GxHtml::valueEx($data->outlet)',
			'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>ItemExpireItem::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>ItemExpireItem::getTypeOptions(),
				),
		array(
			'name'=>'item_expire_id',
			'value'=>'GxHtml::valueEx($data->itemExpire)',
			'filter'=>GxHtml::listDataEx(ItemExpire::model()->findAllAttributes(null, true)),
			),
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