
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'item-return-item-grid',
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
		'price',
		'sale_rate',
			
		/*
		'free',
		'qty',
		'discount',
		'discount_amt',
		'discount1',
		'discount_amt1',
		'cgst_per',
		'sgst_per',
		'cess_per',
		'cgst_amt',
		'sgst_amt',
		'cess_amt',
		'igst_per',
		'igst_amt',
		'tax_id',
		'other_charge',
		'total_amt',
		'vendor_id',
		'outlet_id',
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>ItemReturnItem::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>ItemReturnItem::getTypeOptions(),
				),
		'return_id',
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			),
		*/
		/* array(
			'class' => 'CxButtonColumn',
		), */
	),
)); ?>