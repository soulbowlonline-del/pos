<?php
/**
 * Ported from protected/views/userRole/_list.php.
 */

use app\components\Gx;
use app\models\User;
use app\models\UserRole;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'user-role-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		'title',
		[
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>UserRole::getStatusOptions(),
				],
		[
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>UserRole::getTypeOptions(),
				],
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