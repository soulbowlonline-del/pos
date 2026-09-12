
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'item-vendor-grid',
	'type'=>'bordered', // 'condensed','striped',
	'pager'=>true,
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
		 array(
			'name'=>'item_detail_id',
			'value'=>'GxHtml::valueEx($data->itemDetail)',
			'filter'=>GxHtml::listDataEx(Item::model()->findAllAttributes(null, true)),
			), 
		array(
			'name'=>'vendor_id',
			'value'=>'GxHtml::valueEx($data->vendor)',
			'filter'=>GxHtml::listDataEx(Vendor::model()->findAllAttributes(null, true)),
			),
			'item_code',
			'vendor_price',
		/* array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>ItemVendor::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>ItemVendor::getTypeOptions(),
				),
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			), */
		array(
			'header' =>'Action',
			'class' => 'CxButtonColumn',
			'template'=>'{delete}'
		),
	),
)); ?>