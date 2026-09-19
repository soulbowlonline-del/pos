<?php
/**
 * Ported from protected/views/country/_list.php.
 */

use app\components\Gx;
use app\models\Country;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'country-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		'title',
		[
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>Country::getStatusOptions(),
				],
		[
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>Country::getTypeOptions(),
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