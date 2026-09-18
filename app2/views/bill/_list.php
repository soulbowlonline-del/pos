<?php
/**
 * Ported from protected/views/bill/_list.php.
 */

use app\components\Gx;
use app\models\Bill;
use app\models\PurchaseOrder;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'bill-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		'bill_no',
		'image_file1:html',
		'image_file2:html',
		'image_file3:html',
		[
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>Bill::getTypeOptions(),
				],
		/*
		array(
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>Bill::getStatusOptions(),
				),
		array(
			'attribute' =>'po_id',
			'value' => function ($data) { return Gx::str($data->po); },
			'filter'=>Gx::listData(PurchaseOrder::class),
			),
		*/
		[
			'class' => ActionColumn::class,
		],
	],
]); ?>