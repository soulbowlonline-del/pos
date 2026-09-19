<?php
/**
 * Ported from protected/views/onlineOrder/_list.php.
 */

use app\models\OnlineOrder;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'online-order-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
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
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>OnlineOrder::getTypeOptions(),
				),
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>OnlineOrder::getStatusOptions(),
				),
		'update_time',
		'updated_by',
		*/
		[
			'class' => ActionColumn::class,
		],
	],
]); ?>