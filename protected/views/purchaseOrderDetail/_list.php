
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'purchase-order-detail-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
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
		'req_qty',
		'bal_qty',
			
			
		//'charge_amount',
		/*
		'extra_charges',
		'remarks:html',
		'update_time',
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'item_detail_id',
			'value'=>'GxHtml::valueEx($data->itemDetail)',
			'filter'=>GxHtml::listDataEx(ItemDetail::model()->findAllAttributes(null, true)),
			),
		'item_id',
		array(
			'name'=>'purchase_order_id',
			'value'=>'GxHtml::valueEx($data->purchaseOrder)',
			'filter'=>GxHtml::listDataEx(PurchaseOrder::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'outlet_id',
			'value'=>'GxHtml::valueEx($data->outlet)',
			'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
		*/
		/* array(
			'class' => 'CxButtonColumn',
		), */
	),
)); ?>