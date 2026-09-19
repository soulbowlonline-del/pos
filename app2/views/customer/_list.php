<?php
/**
 * Ported from protected/views/customer/_list.php.
 */

use app\components\Gx;
use app\models\Customer;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'customer-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		'name',
		'email',
		'fax',
		'address:html',
		'city_id',
		/*
		array(
				'attribute' => 'state_id',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->state_id); },
				'filter'=>Customer::getStatusOptions(),
				),
		'country_id',
		'zip_code',
		'opening_balance',
		'credit_limit',
		'payment_days',
		'contact_no',
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>Customer::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>Customer::getTypeOptions(),
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