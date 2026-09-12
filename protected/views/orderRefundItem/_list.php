
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'order-refund-item-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
		array(
			'name'=>'order_refund_id',
			'value'=>'GxHtml::valueEx($data->orderRefund)',
			'filter'=>GxHtml::listDataEx(OrderRefund::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'item_detail_id',
			'value'=>'GxHtml::valueEx($data->itemDetail)',
			'filter'=>GxHtml::listDataEx(ItemDetail::model()->findAllAttributes(null, true)),
			),
		'qty',
		'price',
		array(
			'name'=>'discount_id',
			'value'=>'GxHtml::valueEx($data->discount)',
			'filter'=>GxHtml::listDataEx(Discount::model()->findAllAttributes(null, true)),
			),
		/*
		'discount_amt',
		'tax_id',
		'tax_amt',
		'order_discount',
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>OrderRefundItem::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>OrderRefundItem::getTypeOptions(),
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