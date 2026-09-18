<?php
/**
 * Ported from protected/views/vendorSchemes/view.php.
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
[
			'attribute' => 'vendor',
			'format' => 'raw',
			'value' => $model->vendor !== null ? Html::a(Html::encode(Gx::str($model->vendor)), Gx::url(['vendor/view', 'id' => Gx::pk($model->vendor)])) : null,
			],
[
			'attribute' => 'item',
			'value' => $model->getItems(),
			//'value' => $model->item !== null ? Html::a(Html::encode(Gx::str($model->item)), array('item/view', 'id' => Gx::pk($model->item))) : null,
			],
'total_sale',
'start_date',
'end_date',
'discount',
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