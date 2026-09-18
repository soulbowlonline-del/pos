<?php
/**
 * Ported from protected/views/mrn/view.php.
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
	'buttons'=>$this->context->actions,
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
'code',
'mrs_date',
'mrs_update_date',
'mrs_req_date',
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
			'attribute' => 'outlet',
			'format' => 'raw',
			'value' => $model->outlet !== null ? Html::a(Html::encode(Gx::str($model->outlet)), Gx::url(['outlet/view', 'id' => Gx::pk($model->outlet)])) : null,
			],
[
			'attribute' => 'mrs',
			'format' => 'raw',
			'value' => $model->mrs !== null ? Html::a(Html::encode(Gx::str($model->mrs)), Gx::url(['mrs/view', 'id' => Gx::pk($model->mrs)])) : null,
			],
[
			'attribute' => 'organization',
			'format' => 'raw',
			'value' => $model->organization !== null ? Html::a(Html::encode(Gx::str($model->organization)), Gx::url(['organization/view', 'id' => Gx::pk($model->organization)])) : null,
			],
	],
]); ?>

<?php
 $this->context->StartPanel(); ?>
<?php  $this->context->AddPanel($model->getRelationLabel('mrnDetails'), $model->getRelatedDataProvider('mrnDetails'),	'mrnDetails','mrnDetail');?>
<?php // $this->context->AddPanel($model->getRelationLabel('purchaseOrders'), $model->getRelatedDataProvider('purchaseOrders'),	'purchaseOrders','purchaseOrder');?>
<?php  $this->context->EndPanel(); ?>

<?php  /*  echo CommentPortlet::widget(array(
	'model' => $model,
)); */
?>