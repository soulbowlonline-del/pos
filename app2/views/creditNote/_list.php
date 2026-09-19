<?php
/**
 * Ported from protected/views/creditNote/_list.php.
 */

use app\models\CreditNote;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'credit-note-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		'credit_number',
		'amt',
		'amt_used',
		[
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>CreditNote::getTypeOptions(),
				],
		[
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>CreditNote::getStatusOptions(),
				],
		/*
		'update_time',
		*/
		[
			'class' => ActionColumn::class,
		],
	],
]); ?>