<?php
/**
 * Ported from protected/views/userRole/view.php.
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
/* array(
				'attribute' => 'type_id',
				'format' => 'raw',
				'value'=>$model->getTypeOptions($model->type_id),
				), */
'create_time',
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
<?php  //$this->context->AddPanel($model->getRelationLabel('rolePermissions'), $model->getRelatedDataProvider('rolePermissions'),	'rolePermissions','rolePermission');?>
<?php  $this->context->EndPanel(); ?>

<?php  /*  echo CommentPortlet::widget(array(
	'model' => $model,
)); */
?>
</section>