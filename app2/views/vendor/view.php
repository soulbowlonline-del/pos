<?php
/**
 * Ported from protected/views/vendor/view.php.
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
'name',
			'contact_email',
'description:html',
'contact_person',
'person_designation',
'contact_no',
'secondary_contact_no',
'whatsapp_no',
'primary_address:html',
'secondary_address:html',
'tax_no',
'is_local_vendor:boolean',
[
				'attribute' => 'status',
				'format' => 'raw',
				'value'=>$model->getStatusOptions($model->status),
				],


[
			'attribute' => 'city',
			'format' => 'raw',
			'value' => $model->city !== null ? Html::a(Html::encode(Gx::str($model->city)), Gx::url(['city/view', 'id' => Gx::pk($model->city)])) : null,
			],
[
			'attribute' => 'state',
			'format' => 'raw',
			'value' => $model->state !== null ? Html::a(Html::encode(Gx::str($model->state)), Gx::url(['state/view', 'id' => Gx::pk($model->state)])) : null,
			],
[
			'attribute' => 'country',
			'format' => 'raw',
			'value' => $model->country !== null ? Html::a(Html::encode(Gx::str($model->country)), Gx::url(['country/view', 'id' => Gx::pk($model->country)])) : null,
			],
[
			'attribute' => 'outlet',
			'format' => 'raw',
			'value' => $model->outlet !== null ? Html::a(Html::encode(Gx::str($model->outlet)), Gx::url(['outlet/view', 'id' => Gx::pk($model->outlet)])) : null,
			],
[
			'attribute' => 'createUser',
			'format' => 'raw',
			'value' => $model->createUser !== null ? Html::a(Html::encode(Gx::str($model->createUser)), Gx::url(['user/view', 'id' => Gx::pk($model->createUser)])) : null,
			],

	],
]); ?>

<?php
 $this->context->StartPanel(); ?>
<?php  //$this->context->AddPanel($model->getRelationLabel('itemVendors'), $model->getRelatedDataProvider('itemVendors'),	'itemVendors','itemVendor');?>
<?php // $this->context->AddPanel($model->getRelationLabel('purchaseBills'), $model->getRelatedDataProvider('purchaseBills'),	'purchaseBills','purchaseBill');?>
<?php // $this->context->AddPanel($model->getRelationLabel('purchaseOrders'), $model->getRelatedDataProvider('purchaseOrders'),	'purchaseOrders','purchaseOrder');?>
<?php  $this->context->EndPanel(); ?>

<?php   echo CommentPortlet::widget([
	'model' => $model,
]);
?>
</section>