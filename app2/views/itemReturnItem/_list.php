<?php
/**
 * Ported from protected/views/itemReturnItem/_list.php.
 */

use app\components\Gx;
use app\models\Item;
use app\models\ItemDetail;
use app\models\ItemReturnItem;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'item-return-item-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		[
			'attribute' =>'item_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->item); },
			'filter'=>Gx::listData(Item::class),
			],
		[
			'attribute' =>'item_detail_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->itemDetail); },
			'filter'=>Gx::listData(ItemDetail::class),
			],
		'mrp',
		'price',
		'sale_rate',
			
		/*
		'free',
		'qty',
		'discount',
		'discount_amt',
		'discount1',
		'discount_amt1',
		'cgst_per',
		'sgst_per',
		'cess_per',
		'cgst_amt',
		'sgst_amt',
		'cess_amt',
		'igst_per',
		'igst_amt',
		'tax_id',
		'other_charge',
		'total_amt',
		'vendor_id',
		'outlet_id',
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>ItemReturnItem::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>ItemReturnItem::getTypeOptions(),
				),
		'return_id',
		array(
			'attribute' =>'updated_by',
			'value' => function ($data, $key, $index) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
		/* array(
			'class' => ActionColumn::class,
		), */
	],
]); ?>