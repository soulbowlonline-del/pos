<?php
/**
 * Ported from protected/views/advanceLogs/admin.php.
 */

use app\components\Gx;
use app\models\AdvanceLogs;
use app\models\AdvancePayment;
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
	$.fn.yiiGridView.update('advance-logs-grid', {
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
	'id' => 'advance-logs-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => [
		'id',
		'amount',
		[
			'attribute' =>'advance_payment_id',
			'value' => function ($data) { return Gx::str($data->advancePayment); },
			'filter'=>Gx::listData(AdvancePayment::class),
			],
		[
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>AdvanceLogs::getTypeOptions(),
				],
		[
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>AdvanceLogs::getStatusOptions(),
				],
		'update_time',
		[
			'class' => ActionColumn::class,
			'htmlOptions' => ['nowrap'=>'nowrap'],
		],
	],
]); ?>