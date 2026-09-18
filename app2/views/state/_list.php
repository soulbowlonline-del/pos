<?php
/**
 * Ported from protected/views/state/_list.php.
 */

use app\components\Gx;
use app\models\Country;
use app\models\State;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'state-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		'title',
		[
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>State::getStatusOptions(),
				],
		[
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>State::getTypeOptions(),
				],
		[
			'attribute' =>'country_id',
			'value' => function ($data) { return Gx::str($data->country); },
			'filter'=>Gx::listData(Country::class),
			],
		'update_time',
		/*
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