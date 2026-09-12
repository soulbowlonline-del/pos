
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'role-permission-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
		array(
			'name'=>'role_id',
			'value'=>'GxHtml::valueEx($data->role)',
			'filter'=>GxHtml::listDataEx(UserRole::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'permission_id',
			'value'=>'GxHtml::valueEx($data->permission)',
			'filter'=>GxHtml::listDataEx(Permission::model()->findAllAttributes(null, true)),
			),
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>RolePermission::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>RolePermission::getTypeOptions(),
				),
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			),
		array(
			'class' => 'CxButtonColumn',
		),
	),
)); ?>