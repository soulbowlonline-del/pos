<?php
/**
 * Ported from protected/views/stockAdjustLog/view.php.
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
'date',
[
			'attribute' => 'itemDetail',
			'format' => 'raw',
			'value' => $model->itemDetail !== null ? Html::a(Html::encode(Gx::str($model->itemDetail)), Gx::url(['itemDetail/view', 'id' => Gx::pk($model->itemDetail)])) : null,
			],
[
			'attribute' => 'item',
			'format' => 'raw',
			'value' => $model->item !== null ? Html::a(Html::encode(Gx::str($model->item)), Gx::url(['item/view', 'id' => Gx::pk($model->item)])) : null,
			],
'mrp',
'current_stock',
'actual_stock',
'adjusted',
[
			'attribute' => 'outlet',
			'format' => 'raw',
			'value' => $model->outlet !== null ? Html::a(Html::encode(Gx::str($model->outlet)), Gx::url(['outlet/view', 'id' => Gx::pk($model->outlet)])) : null,
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