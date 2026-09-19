<?php
/**
 * Ported from protected/views/advanceLogs/_list.php.
 */

use app\components\Gx;
use app\models\AdvanceLogs;
use app\models\AdvancePayment;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'advance-logs-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		//'id',
		'amount',
		[
			'attribute' =>'advance_payment_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->advancePayment); },
			'filter'=>Gx::listData(AdvancePayment::class),
			],
		[
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>AdvanceLogs::getTypeOptions(),
				],
	/* 	array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>AdvanceLogs::getStatusOptions(),
				), */
		'create_time',
		/* array(
			'class' => ActionColumn::class,
		), */
	],
]); ?>