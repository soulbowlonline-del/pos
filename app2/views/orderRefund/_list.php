<?php
/**
 * Ported from protected/views/orderRefund/_list.php.
 */

use app\models\OrderRefund;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'order-refund-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		'qty',
		'discount',
		'discount_amt',
		'total_amt',
		'paid_amt',
		/*
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>OrderRefund::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>OrderRefund::getTypeOptions(),
				),
		'city_id',
		array(
				'attribute' => 'state_id',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->state_id); },
				'filter'=>OrderRefund::getStatusOptions(),
				),
		'country_id',
		'address:html',
		'note:html',
		'update_time',
		'order_id',
		'customer_id',
		'updated_by',
		*/
		[
			'class' => ActionColumn::class,
		],
	],
]); ?>