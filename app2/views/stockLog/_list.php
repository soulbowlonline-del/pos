<?php
/**
 * Ported from protected/views/stockLog/_list.php.
 */

use app\components\Gx;
use app\models\Item;
use app\models\ItemDetail;
use app\models\Outlet;
use app\models\StockLog;
use app\models\Vendor;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'stock-log-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		[
			'attribute' =>'item_detail_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->itemDetail); },
			'filter'=>Gx::listData(ItemDetail::class),
			],
		[
			'attribute' =>'item_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->item); },
			'filter'=>Gx::listData(Item::class),
			],
		'batch_no',
		'Qty',
		[
			'attribute' =>'outlet_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->outlet); },
			'filter'=>Gx::listData(Outlet::class),
			],
		/*
		array(
			'attribute' =>'vendor_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->vendor); },
			'filter'=>Gx::listData(Vendor::class),
			),
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>StockLog::getTypeOptions(),
				),
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>StockLog::getStatusOptions(),
				),
		'update_time',
		*/
		[
			'class' => ActionColumn::class,
		],
	],
]); ?>