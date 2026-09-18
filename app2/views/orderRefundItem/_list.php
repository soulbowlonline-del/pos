<?php
/**
 * Ported from protected/views/orderRefundItem/_list.php.
 */

use app\components\Gx;
use app\models\Discount;
use app\models\ItemDetail;
use app\models\OrderRefund;
use app\models\OrderRefundItem;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'order-refund-item-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		[
			'attribute' =>'order_refund_id',
			'value' => function ($data) { return Gx::str($data->orderRefund); },
			'filter'=>Gx::listData(OrderRefund::class),
			],
		[
			'attribute' =>'item_detail_id',
			'value' => function ($data) { return Gx::str($data->itemDetail); },
			'filter'=>Gx::listData(ItemDetail::class),
			],
		'qty',
		'price',
		[
			'attribute' =>'discount_id',
			'value' => function ($data) { return Gx::str($data->discount); },
			'filter'=>Gx::listData(Discount::class),
			],
		/*
		'discount_amt',
		'tax_id',
		'tax_amt',
		'order_discount',
		array(
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>OrderRefundItem::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>OrderRefundItem::getTypeOptions(),
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