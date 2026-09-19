<?php
/**
 * Ported from protected/views/empShift/admin.php.
 */

use app\components\Gx;
use app\models\Emp;
use app\models\EmpShift;
use app\models\Shift;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	'Manage',
];


$this->registerJs("
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$.fn.yiiGridView.update('emp-shift-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<div class="page-header">
	<h1><?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?></h1>
</div>
<p>
You may optionally enter a comparison operator (&lt;, &lt;=, &gt;, &gt;=, &lt;&gt; or =) at the beginning of each of your search values to specify how the comparison should be done.
</p>


<?php echo GridView::widget([
	'id' => 'emp-shift-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => [
		'id',
		[
			'attribute' =>'emp_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->emp); },
			'filter'=>Gx::listData(Emp::class),
			],
		[
			'attribute' =>'shift_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->shift); },
			'filter'=>Gx::listData(Shift::class),
			],
		[
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>EmpShift::getStatusOptions(),
				],
		[
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>EmpShift::getTypeOptions(),
				],
		[
			'attribute' =>'updated_by',
			'value' => function ($data, $key, $index) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			],
		[
			'class' => ActionColumn::class,
			'htmlOptions' => ['nowrap'=>'nowrap'],
		],
	],
]); ?>