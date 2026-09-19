<?php
/**
 * Ported from protected/views/itemStock/_list.php.
 */

use app\components\Gx;
use app\models\ItemDetail;
use app\models\ItemStock;
use app\models\Tax;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'item-stock-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		[
			'attribute' =>'item_detail_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->itemDetail); },
			'filter'=>Gx::listData(ItemDetail::class),
			],
		'item_id',
		'batch_number',
		'qty',
		'base_price',
		/*
		'mrp',
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>ItemStock::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>ItemStock::getTypeOptions(),
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
			'class' => ActionColumn::class,
		],
	],
]); ?>