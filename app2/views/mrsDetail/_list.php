<?php
/**
 * Ported from protected/views/mrsDetail/_list.php.
 */

use app\components\Gx;
use app\models\Item;
use app\models\ItemDetail;
use app\models\Mrs;
use app\models\MrsDetail;
use app\models\Outlet;
use app\models\User;
use app\widgets\GridView;
?>
			<div class="col-md-12 item-list-table">
<div class="table-responsive customsmallgridwidth">
<?php echo GridView::widget([
	'id' => 'mrs-detail-grid',
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
			[
					'header'=>'Ttl Rmn Qty',
					'value' => function ($data, $key, $index) { return isset($data->item)?$data->item->getTotalRemainingQuantity():""; },
					'htmlOptions'=>['class'=>'item_qty_field'],
			
			],
		'req_qty',
		'approved_qty',
			'mrp',
			'sale_rate',
			'price',
			'margin',
			'discount',
			'discount_amt',
			'discount1',
			'discount_amt1',
			[
					'attribute' =>'tax_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->tax); },
					
			],
			'amount'
		/* 'bal_qty',
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>MrsDetail::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>MrsDetail::getTypeOptions(),
				), */
		/*
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
		array(
			'attribute' =>'mrs_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->mrs); },
			'filter'=>Gx::listData(Mrs::class),
			),
		array(
			'attribute' =>'outlet_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->outlet); },
			'filter'=>Gx::listData(Outlet::class),
			),
		*/
		
	],
]); ?>
</div>
</div>