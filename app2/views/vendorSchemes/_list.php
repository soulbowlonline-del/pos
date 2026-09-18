<?php
/**
 * Ported from protected/views/vendorSchemes/_list.php.
 */

use app\components\Gx;
use app\models\Item;
use app\models\User;
use app\models\Vendor;
use app\models\VendorSchemes;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'vendor-schemes-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		[
			'attribute' =>'vendor_id',
			'value' => function ($data) { return Gx::str($data->vendor); },
			'filter'=>Gx::listData(Vendor::class),
			],
		[
			'attribute' =>'item_id',
			'value' => function ($data) { return Gx::str($data->item); },
			'filter'=>Gx::listData(Item::class),
			],
		'total_sale',
		'start_date',
		'end_date',
		/*
		'discount',
		array(
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>VendorSchemes::getTypeOptions(),
				),
		array(
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>VendorSchemes::getStatusOptions(),
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