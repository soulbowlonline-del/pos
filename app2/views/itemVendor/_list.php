<?php
/**
 * Ported from protected/views/itemVendor/_list.php.
 */

use app\components\Gx;
use app\models\Item;
use app\models\ItemVendor;
use app\models\User;
use app\models\Vendor;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'item-vendor-grid',
	'type'=>'bordered', // 'condensed','striped',
	'pager'=>true,
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		 [
			'attribute' =>'item_detail_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->itemDetail); },
			'filter'=>Gx::listData(Item::class),
			], 
		[
			'attribute' =>'vendor_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->vendor); },
			'filter'=>Gx::listData(Vendor::class),
			],
			'item_code',
			'vendor_price',
		/* array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>ItemVendor::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>ItemVendor::getTypeOptions(),
				),
		array(
			'attribute' =>'updated_by',
			'value' => function ($data, $key, $index) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			), */
		[
			'header' =>'Action',
			'class' => ActionColumn::class,
			'template'=>'{delete}'
		],
	],
]); ?>