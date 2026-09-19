<?php
/**
 * Ported from protected/views/paymentMode/_list.php.
 */

use app\components\Gx;
use app\models\PaymentMode;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'payment-mode-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		'title',
		[
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>PaymentMode::getTypeOptions(),
				],
		[
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>PaymentMode::getStatusOptions(),
				],
		'update_time',
		[
			'attribute' =>'updated_by',
			'value' => function ($data, $key, $index) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			],
		[
			'class' => ActionColumn::class,
		],
	],
]); ?>