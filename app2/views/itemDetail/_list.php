<?php
/**
 * Ported from protected/views/itemDetail/_list.php.
 */

use app\components\Gx;
use app\models\Item;
use app\models\ItemDetail;
use app\models\Tax;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'item-detail-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		[
			'attribute' =>'item_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->item); },
			'filter'=>Gx::listData(Item::class),
			],
		'bar_code',
		'open_stock_qty',
	//	'reorder_qty',
		[
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>ItemDetail::getStatusOptions(),
				],
		/*
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>ItemDetail::getTypeOptions(),
				),
		array(
			'attribute' =>'tax_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->tax); },
			'filter'=>Gx::listData(Tax::class),
			),
		array(
			'attribute' =>'updated_by',
			'value' => function ($data, $key, $index) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
		[
			'header'=>'Action',
			'class' => ActionColumn::class,
			'template'=>'{view}{update}'
		],
	],
]); ?>