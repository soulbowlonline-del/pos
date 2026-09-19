<?php
/**
 * Ported from protected/views/rolePermission/_list.php.
 */

use app\components\Gx;
use app\models\Permission;
use app\models\RolePermission;
use app\models\User;
use app\models\UserRole;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'role-permission-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		[
			'attribute' =>'role_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->role); },
			'filter'=>Gx::listData(UserRole::class),
			],
		[
			'attribute' =>'permission_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->permission); },
			'filter'=>Gx::listData(Permission::class),
			],
		[
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>RolePermission::getStatusOptions(),
				],
		[
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>RolePermission::getTypeOptions(),
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