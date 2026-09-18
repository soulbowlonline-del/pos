<?php
/**
 * Ported from protected/views/freeItem/_list.php.
 */

use app\components\Gx;
use app\models\FreeItem;
use app\models\Item;
use app\models\ItemCategory;
use app\models\ItemCompany;
use app\models\ItemDetail;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'free-item-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		'title',
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
		[
			'attribute' =>'item_category_id',
			'value' => function ($data) { return Gx::str($data->itemCategory); },
			'filter'=>Gx::listData(ItemCategory::class),
			],
		[
			'attribute' =>'item_company_id',
			'value' => function ($data) { return Gx::str($data->itemCompany); },
			'filter'=>Gx::listData(ItemCompany::class),
			],
		/*
		'qty',
		'stock_qty',
		array(
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>FreeItem::getTypeOptions(),
				),
		array(
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>FreeItem::getStatusOptions(),
				),
		'update_time',
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