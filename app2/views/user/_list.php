<?php
/**
 * Ported from protected/views/user/_list.php.
 */

use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'user-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
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
				'attribute' => 'state',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->state); },
				'filter'=>User::getStatusOptions(),
				),
		'lang',
		'image_file',
		'is_passenger',
		'is_dispatcher',
		'is_driver',
		'role_id',
		array(
				'attribute' => 'state_id',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->state_id); },
				'filter'=>User::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>User::getTypeOptions(),
				),
		'last_visit_time',
		'last_action_time',
		'last_password_change',
		array(
				'attribute' => 'is_active',
				'value' => function ($data, $key, $index) { return ($data->is_active === 0) ? Yii::t('app', 'No') : Yii::t('app', 'Yes'); },
				'filter' => array('0' => 'No', '1' => 'Yes'),
				),
		'login_error_count',
		*/
		[
			'class' => ActionColumn::class,
		'template' => '{view}{update}'
		],
	],
]); ?>