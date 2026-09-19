<?php
/**
 * Ported from protected/views/order/_list.php.
 */

use app\models\Order;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'order-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
	//	'id',
		'bill_no',
		'bill_date',
			[
					'header' => '<a>Customer</a>',
					'value' => function ($data, $key, $index) { return isset($data->customer)?$data->customer:""; },
					//'filter'=>Order::getStatusOptions(),
			],
			'total_amt',
			'discount_amt',
			'paid_amt',
		[
					'attribute' => 'mode_of_payment',
					'value' => function ($data, $key, $index) { return $data->getPaymentTypeOptions($data->mode_of_payment); },
					'filter'=>Order::getPaymentTypeOptions(),
			],
			/* array(
					'attribute' => 'mode_of_delivery',
					'value' => function ($data, $key, $index) { return $data->getDeliveryTypeOptions($data->mode_of_delivery); },
					'filter'=>Order::getDeliveryTypeOptions(),
			), */
			[
					'attribute' => 'type_id',
					'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
					'filter'=>Order::getTypeOptions(),
			],
			[
					'header' => '<a>Outlet</a>',
					'value' => function ($data, $key, $index) { return isset($data->outlet)?$data->outlet:""; },
					//'filter'=>Order::getStatusOptions(),
			],
		
		/*
		'discount_amt',
		'total_amt',
		'paid_amt',
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>Order::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>Order::getTypeOptions(),
				),
		'city_id',
		array(
				'attribute' => 'state_id',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->state_id); },
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
		[
			'header'=>'Actions',
			'class' => ActionColumn::class,
				'template' => '{view}'
		],
	],
]); ?>