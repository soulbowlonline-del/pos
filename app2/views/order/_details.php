<?php
/**
 * Ported from protected/views/order/_details.php.
 */

use app\models\OrderItem;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'order-item-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		//'id',
			[
					'header' => 'Barcode',
					'value' => function ($data) { return isset($data->itemDetail)?$data->itemDetail->bar_code:""; }
					
			],
			[
					'header' => 'Item',
					'value' => function ($data) { return $data->getItemName(); }
			
			],
			[
					'header' => 'Quantity',
					'value' => function ($data) { return $data->getItemQuantity(); }
		
			],
			//'qty',
		'price',
		'discount_amt',
			'tax_amount',
		/*
		'discount_amt',
		'tax_id',
		'tax_amount',
		array(
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>OrderItem::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>OrderItem::getTypeOptions(),
				),
		'update_time',
		'updated_by',
		*/
	/* 	array(
			'class' => ActionColumn::class,
		), */
	],
]); ?>