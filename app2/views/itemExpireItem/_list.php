<?php
/**
 * Ported from protected/views/itemExpireItem/_list.php.
 */

use app\components\Gx;
use app\models\Item;
use app\models\ItemDetail;
use app\models\ItemExpire;
use app\models\ItemExpireItem;
use app\models\Outlet;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'item-expire-item-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		[
			'attribute' =>'item_id',
			'value' => function ($data) { return Gx::str($data->item); },
			'filter'=>Gx::listData(Item::class),
			],
		[
			'attribute' =>'item_detail_id',
			'value' => function ($data) { return Gx::str($data->itemDetail); },
			'filter'=>Gx::listData(ItemDetail::class),
			],
		'mrp',
		'sale_rate',
		'free',
		/*
		'qty',
		'total_amt',
		'vendor_id',
		array(
			'attribute' =>'outlet_id',
			'value' => function ($data) { return Gx::str($data->outlet); },
			'filter'=>Gx::listData(Outlet::class),
			),
		array(
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>ItemExpireItem::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>ItemExpireItem::getTypeOptions(),
				),
		array(
			'attribute' =>'item_expire_id',
			'value' => function ($data) { return Gx::str($data->itemExpire); },
			'filter'=>Gx::listData(ItemExpire::class),
			),
		array(
			'attribute' =>'updated_by',
			'value' => function ($data) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
		[
			'class' => ActionColumn::class,
		],
	],
]); ?>