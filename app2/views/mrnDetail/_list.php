<?php
/**
 * Ported from protected/views/mrnDetail/_list.php.
 */

use app\components\Gx;
use app\models\Item;
use app\models\ItemDetail;
use app\models\Mrn;
use app\models\MrnDetail;
use app\models\Outlet;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'mrn-detail-grid',
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
		'req_qty',
		'approved_qty',
		'bal_qty',
			
			
			
		/* array(
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>MrnDetail::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>MrnDetail::getTypeOptions(),
				), */
		/*
		'remarks:html',
		'update_time',
		array(
			'attribute' =>'updated_by',
			'value' => function ($data) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		array(
			'attribute' =>'item_detail_id',
			'value' => function ($data) { return Gx::str($data->itemDetail); },
			'filter'=>Gx::listData(ItemDetail::class),
			),
		array(
			'attribute' =>'mrn_id',
			'value' => function ($data) { return Gx::str($data->mrn); },
			'filter'=>Gx::listData(Mrn::class),
			),
		array(
			'attribute' =>'outlet_id',
			'value' => function ($data) { return Gx::str($data->outlet); },
			'filter'=>Gx::listData(Outlet::class),
			),
		*/
	/* 	array(
			'class' => ActionColumn::class,
		), */
	],
]); ?>