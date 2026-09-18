<?php
/**
 * Ported from protected/views/freeItem/view.php.
 */

use app\components\Gx;
use app\widgets\ButtonGroup;
use app\widgets\DetailView;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	Gx::str($model),
];


?>
<section class="content">
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
'title',
[
			'attribute' => 'item',
			'format' => 'raw',
			'value' => $model->item !== null ? Html::a(Html::encode(Gx::str($model->item)), Gx::url(['item/view', 'id' => Gx::pk($model->item)])) : null,
			],
[
			'attribute' => 'itemDetail',
			'format' => 'raw',
			'value' => $model->itemDetail !== null ? Html::a(Html::encode(Gx::str($model->itemDetail)), Gx::url(['itemDetail/view', 'id' => Gx::pk($model->itemDetail)])) : null,
			],
[
			'attribute' => 'itemCategory',
			'format' => 'raw',
			'value' => $model->itemCategory !== null ? Html::a(Html::encode(Gx::str($model->itemCategory)), Gx::url(['itemCategory/view', 'id' => Gx::pk($model->itemCategory)])) : null,
			],
[
			'attribute' => 'itemCompany',
			'format' => 'raw',
			'value' => $model->itemCompany !== null ? Html::a(Html::encode(Gx::str($model->itemCompany)), Gx::url(['itemCompany/view', 'id' => Gx::pk($model->itemCompany)])) : null,
			],
'qty',
'stock_qty',
/* array(
				'attribute' => 'type_id',
				'format' => 'raw',
				'value'=>$model->getTypeOptions($model->type_id),
				),
array(
				'attribute' => 'status',
				'format' => 'raw',
				'value'=>$model->getStatusOptions($model->status),
				), */
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
	],
]); ?>


</section>