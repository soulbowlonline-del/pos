<?php
/**
 * Ported from protected/views/orderItem/_list.php.
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
		'id',
			[
					'header' => '<a>Barcode</a>',
					'value' => function ($data, $key, $index) { return isset($data->itemDetail)?$data->itemDetail->bar_code:""; }
					
			],
			[
					'header' => '<a>Item</a>',
					'value' => function ($data, $key, $index) { return $data->getItemName(); }
			
			],
			'qty',
		[
					'attribute' => 'price',
					'value' => function ($data, $key, $index) { return $data->price; }
		
			],
		'discount_amt',
			[
					'header' => '<a>Tax</a>',
					'value' => function ($data, $key, $index) { return isset($data->tax)?$data->tax->title:""; },
			],
			[
					'header' => '<a>HSN Code</a>',
					'value' => function ($data, $key, $index) { return isset($data->item)?$data->item->hsn_code:""; },
			],
			[
					'header' => '<a>CGST(%age)</a>',
					'value' => function ($data, $key, $index) { return isset($data->tax)?$data->tax->tax_val1:""; },
			],
			[
					'header' => '<a>SGST(%age)</a>',
					'value' => function ($data, $key, $index) { return isset($data->tax)?$data->tax->tax_val2:""; },
			],
			[
					'header' => '<a>CESS(%age)</a>',
					'value' => function ($data, $key, $index) { return isset($data->tax)?$data->tax->tax_val3:""; },
			],
			[
					'attribute' => 'tax_amount',
					'value' => function ($data, $key, $index) { return $data->tax_amount; }
			
			],
			
		/*
		'discount_amt',
		'tax_id',
		'tax_amount',
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>OrderItem::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
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