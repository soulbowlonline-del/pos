
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'order-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
	//	'id',
		'bill_no',
		'bill_date',
			array(
					'header' => '<a>Customer</a>',
					'value'=>'isset($data->customer)?$data->customer:""',
					//'filter'=>Order::getStatusOptions(),
			),
			'total_amt',
			'discount_amt',
			'paid_amt',
		array(
					'name' => 'mode_of_payment',
					'value'=>'$data->getPaymentTypeOptions($data->mode_of_payment)',
					'filter'=>Order::getPaymentTypeOptions(),
			),
			/* array(
					'name' => 'mode_of_delivery',
					'value'=>'$data->getDeliveryTypeOptions($data->mode_of_delivery)',
					'filter'=>Order::getDeliveryTypeOptions(),
			), */
			array(
					'name' => 'type_id',
					'value'=>'$data->getTypeOptions($data->type_id)',
					'filter'=>Order::getTypeOptions(),
			),
			array(
					'header' => '<a>Outlet</a>',
					'value'=>'isset($data->outlet)?$data->outlet:""',
					//'filter'=>Order::getStatusOptions(),
			),
		
		/*
		'discount_amt',
		'total_amt',
		'paid_amt',
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>Order::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>Order::getTypeOptions(),
				),
		'city_id',
		array(
				'name' => 'state_id',
				'value'=>'$data->getStatusOptions($data->state_id)',
				'filter'=>Order::getStatusOptions(),
				),
		'country_id',
		'outlet_id',
		'address:html',
		'note:html',
		'update_time',
		'customer_id',
		'updated_by',
		*/
		array(
			'header'=>'Actions',
			'class' => 'CxButtonColumn',
				'template' => '{view}'
		),
	),
)); ?>