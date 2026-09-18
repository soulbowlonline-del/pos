<?php
/**
 * Ported from protected/views/empShift/_list.php.
 */

use app\components\Gx;
use app\models\Emp;
use app\models\EmpShift;
use app\models\Shift;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'emp-shift-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		//'id',
		[
			'attribute' =>'emp_id',
			'value' => function ($data) { return Gx::str($data->emp); },
			'filter'=>Gx::listData(Emp::class),
			],
		[
			'attribute' =>'shift_id',
			'value' => function ($data) { return Gx::str($data->shift); },
			'filter'=>Gx::listData(Shift::class),
			],
		/* array(
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>EmpShift::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>EmpShift::getTypeOptions(),
				),
		array(
			'attribute' =>'updated_by',
			'value' => function ($data) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			), */
		/* array(
			'class' => ActionColumn::class,
		), */
	],
]); ?>