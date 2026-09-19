<?php
/**
 * Ported from protected/views/purchaseOrderDetail/_list.php.
 */

use app\components\Gx;
use app\models\Item;
use app\models\ItemDetail;
use app\models\Outlet;
use app\models\PurchaseOrder;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'purchase-order-detail-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
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
		'req_qty',
		'bal_qty',
			
			
		//'charge_amount',
		/*
		'extra_charges',
		'remarks:html',
		'update_time',
		array(
			'attribute' =>'updated_by',
			'value' => function ($data, $key, $index) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		array(
			'attribute' =>'item_detail_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->itemDetail); },
			'filter'=>Gx::listData(ItemDetail::class),
			),
		'item_id',
		array(
			'attribute' =>'purchase_order_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->purchaseOrder); },
			'filter'=>Gx::listData(PurchaseOrder::class),
			),
		array(
			'attribute' =>'outlet_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->outlet); },
			'filter'=>Gx::listData(Outlet::class),
			),
		*/
		/* array(
			'class' => ActionColumn::class,
		), */
	],
]); ?>