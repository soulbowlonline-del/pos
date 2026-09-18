<?php
/**
 * Ported from protected/views/advancePayment/view.php.
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
'payment_date',
'payment',
			'balance_amt',
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
			'attribute' => 'vendor',
			'format' => 'raw',
			'value' => $model->vendor !== null ? Html::a(Html::encode(Gx::str($model->vendor)), Gx::url(['vendor/view', 'id' => Gx::pk($model->vendor)])) : null,
			],
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
<?php  //$this->context->AddPanel($model->getRelationLabel('purchaseBills'), $model->getRelatedDataProvider('purchaseBills'),	'purchaseBills','purchaseBill');?>
<?php  $this->context->AddPanel($model->getRelationLabel('advanceLogs'), $model->getRelatedDataProvider('advancelogs'),	'advanceLogs','advanceLogs');?>
<?php  $this->context->EndPanel(); ?>
</section>