<?php
/**
 * Ported from protected/views/city/_list.php.
 */

use app\components\Gx;
use app\models\City;
use app\models\State;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'city-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		'title',
		[
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>City::getTypeOptions(),
				],
		[
			'attribute' =>'state_id',
			'value' => function ($data) { return Gx::str($data->state); },
			'filter'=>Gx::listData(State::class),
			],
		[
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>City::getStatusOptions(),
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