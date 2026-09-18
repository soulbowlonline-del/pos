<?php
/**
 * Ported from protected/views/country/view.php.
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
<section class="content">
<div class="page-header">
<h1 class="pull-left"><?php echo Html::encode(Gx::str($model)); ?></h1>


<?php   echo ButtonGroup::widget([
	'buttons'=>$this->context->menu,
	'type'=>'success',
	'htmlOptions'=>['class'=> 'pull-right'],
	]);

	?>
<div class="clearfix"></div>


</div>
<?php echo DetailView::widget([
	'data' => $model,
	'attributes' => [
'id',
'title',
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

<?php
 $this->context->StartPanel(); ?>
<?php  //$this->context->AddPanel($model->getRelationLabel('orders'), $model->getRelatedDataProvider('orders'),	'orders','order');?>
<?php  //$this->context->AddPanel($model->getRelationLabel('orderHolds'), $model->getRelatedDataProvider('orderHolds'),	'orderHolds','orderHold');?>
<?php  //$this->context->AddPanel($model->getRelationLabel('orderRefunds'), $model->getRelatedDataProvider('orderRefunds'),	'orderRefunds','orderRefund');?>
<?php  //$this->context->AddPanel($model->getRelationLabel('organizations'), $model->getRelatedDataProvider('organizations'),	'organizations','organization');?>
<?php  //$this->context->AddPanel($model->getRelationLabel('outlets'), $model->getRelatedDataProvider('outlets'),	'outlets','outlet');?>
<?php // $this->context->AddPanel($model->getRelationLabel('states'), $model->getRelatedDataProvider('states'),	'states','state');?>
<?php  //$this->context->AddPanel($model->getRelationLabel('vendors'), $model->getRelatedDataProvider('vendors'),	'vendors','vendor');?>
<?php  $this->context->EndPanel(); ?>

<?php   echo CommentPortlet::widget([
	'model' => $model,
]);
?>
</section>