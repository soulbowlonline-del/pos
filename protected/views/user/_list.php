
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'user-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
		'full_name',
		'email',
		'lat',
		'long',
		'contact_no',
		/*
		'date_of_birth',
		'about_me:html',
		'address',
		'postal_code',
		'country',
		'city',
		array(
				'name' => 'state',
				'value'=>'$data->getStatusOptions($data->state)',
				'filter'=>User::getStatusOptions(),
				),
		'lang',
		'image_file',
		'is_passenger',
		'is_dispatcher',
		'is_driver',
		'role_id',
		array(
				'name' => 'state_id',
				'value'=>'$data->getStatusOptions($data->state_id)',
				'filter'=>User::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>User::getTypeOptions(),
				),
		'last_visit_time',
		'last_action_time',
		'last_password_change',
		array(
				'name' => 'is_active',
				'value' => '($data->is_active === 0) ? Yii::t(\'app\', \'No\') : Yii::t(\'app\', \'Yes\')',
				'filter' => array('0' => Yii::t('app', 'No'), '1' => Yii::t('app', 'Yes')),
				),
		'login_error_count',
		*/
		array(
			'class' => 'CButtonColumn',
		'template' => '{view}{update}'
		),
	),
)); ?>