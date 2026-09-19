<?php
/**
 * Ported from protected/views/paymentReport/_list.php.
 */

use app\components\Gx;
use app\models\PaymentReport;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'payment-report-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		'doc_no',
		'chq_no',
		'comp_code',
		'house_bank',
		'hb_acct',
		/*
		'ben_acc_no',
		'ref_no',
		'amount',
		'vendor_id',
		'run_date',
		'inst_date',
		'value_date',
		array(
				'attribute' => 'pay_type',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->pay_type); },
				'filter'=>PaymentReport::getTypeOptions(),
				),
		array(
				'attribute' => 'pay_status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->pay_status); },
				'filter'=>PaymentReport::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>PaymentReport::getTypeOptions(),
				),
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>PaymentReport::getStatusOptions(),
				),
		'update_time',
		array(
			'attribute' =>'updated_by',
			'value' => function ($data, $key, $index) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
		[
			'class' => ActionColumn::class,
		],
	],
]); ?>