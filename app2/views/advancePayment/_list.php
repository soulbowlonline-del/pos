<?php
/**
 * Ported from protected/views/advancePayment/_list.php.
 */

use app\components\Gx;
use app\models\AdvancePayment;
use app\models\User;
use app\models\Vendor;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'advance-payment-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		'payment_date',
		'payment',
		[
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>AdvancePayment::getTypeOptions(),
				],
		[
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>AdvancePayment::getStatusOptions(),
				],
		'update_time',
		/*
		array(
			'attribute' =>'vendor_id',
			'value' => function ($data) { return Gx::str($data->vendor); },
			'filter'=>Gx::listData(Vendor::class),
			),
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