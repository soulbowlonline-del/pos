<?php
/**
 * Ported from protected/views/emp/_list.php.
 */

use app\components\Gx;
use app\models\Designation;
use app\models\Emp;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'emp-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		'code',
		'name',
		'email',
		'contact_no',
		'gender_id',
		/*
		'date_of_birth',
		'date_of_joining',
		'permanent_address:html',
		'temp_address:html',
		array(
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>Emp::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>Emp::getTypeOptions(),
				),
		array(
			'attribute' =>'designation_id',
			'value' => function ($data) { return Gx::str($data->designation); },
			'filter'=>Gx::listData(Designation::class),
			),
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