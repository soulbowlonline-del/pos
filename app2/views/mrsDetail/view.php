<?php
/**
 * Ported from protected/views/mrsDetail/view.php.
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
'req_qty',
'approved_qty',
'bal_qty',
[
				'attribute' => 'status',
				'format' => 'raw',
				'value'=>$model->getStatusOptions($model->status),
				],
[
				'attribute' => 'type_id',
				'format' => 'raw',
				'value'=>$model->getTypeOptions($model->type_id),
				],
'remarks:html',
'create_time',
'update_time',
[
			'attribute' => 'createUser',
			'format' => 'raw',
			'value' => $model->createUser !== null ? Html::a(Html::encode(Gx::str($model->createUser)), Gx::url(['user/view', 'id' => Gx::pk($model->createUser)])) : null,
			],
[
			'attribute' => 'updatedBy',
			'format' => 'raw',
			'value' => $model->updatedBy !== null ? Html::a(Html::encode(Gx::str($model->updatedBy)), Gx::url(['user/view', 'id' => Gx::pk($model->updatedBy)])) : null,
			],
[
			'attribute' => 'itemDetail',
			'format' => 'raw',
			'value' => $model->itemDetail !== null ? Html::a(Html::encode(Gx::str($model->itemDetail)), Gx::url(['itemDetail/view', 'id' => Gx::pk($model->itemDetail)])) : null,
			],
[
			'attribute' => 'mrs',
			'format' => 'raw',
			'value' => $model->mrs !== null ? Html::a(Html::encode(Gx::str($model->mrs)), Gx::url(['mrs/view', 'id' => Gx::pk($model->mrs)])) : null,
			],
[
			'attribute' => 'outlet',
			'format' => 'raw',
			'value' => $model->outlet !== null ? Html::a(Html::encode(Gx::str($model->outlet)), Gx::url(['outlet/view', 'id' => Gx::pk($model->outlet)])) : null,
			],
	],
]); ?>


<?php   echo CommentPortlet::widget([
	'model' => $model,
]);
?>