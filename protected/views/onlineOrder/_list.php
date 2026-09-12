
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'online-order-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
		'order_id',
		'item_count',
		'grand_total',
		'first_name',
		'last_name',
		/*
		'street',
		'city',
		'telephone',
		'zip_code',
		'country',
		'delivery_slot',
		'ship_name',
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>OnlineOrder::getTypeOptions(),
				),
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>OnlineOrder::getStatusOptions(),
				),
		'update_time',
		'updated_by',
		*/
		array(
			'class' => 'CxButtonColumn',
		),
	),
)); ?>