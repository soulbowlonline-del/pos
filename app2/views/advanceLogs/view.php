<?php
/**
 * Ported from protected/views/advanceLogs/view.php.
 */

use app\components\Gx;
use app\widgets\ButtonGroup;
use app\widgets\CommentPortlet;
use app\widgets\DetailView;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	Gx::str($model),
];


?>

<div class="page-header">
<h1><?php echo Html::encode(Gx::str($model)); ?></h1>
</div>

<?php   echo ButtonGroup::widget([
	'buttons'=>$this->context->actions,
	'type'=>'success',
	'htmlOptions'=>['class'=> 'pull-right'],
	]);
?>

<?php echo DetailView::widget([
	'data' => $model,
	'attributes' => [
'id',
'amount',
[
			'attribute' => 'advancePayment',
			'format' => 'raw',
			'value' => $model->advancePayment !== null ? Html::a(Html::encode(Gx::str($model->advancePayment)), Gx::url(['advancePayment/view', 'id' => Gx::pk($model->advancePayment)])) : null,
			],
[
				'attribute' => 'type_id',
				'format' => 'raw',
				'value'=>$model->getTypeOptions($model->type_id),
				],
[
				'attribute' => 'status',
				'format' => 'raw',
				'value'=>$model->getStatusOptions($model->status),
				],
'create_time',
'update_time',
	],
]); ?>


<?php   echo CommentPortlet::widget([
	'model' => $model,
]);
?>