<?php
/**
 * Ported from protected/views/item/view.php.
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
'item_code',
			'hsn_code',
'description:html',
'image_file:html',
			'min_qty',
			'max_qty',
			'reorder_qty',
[
				'attribute' => 'item_type',
				'format' => 'raw',
				'value'=>$model->getTypeOptions($model->item_type),
				],
[
				'attribute' => 'status',
				'format' => 'raw',
				'value'=>$model->getStatusOptions($model->status),
				],

'is_tax:boolean',
'is_discount:boolean',
[
			'attribute' => 'category',
			'format' => 'raw',
			'value' => $model->category !== null ? Html::a(Html::encode(Gx::str($model->category)), Gx::url(['itemCategory/view', 'id' => Gx::pk($model->category)])) : null,
			],
[
			'attribute' => 'subCompany',
			'format' => 'raw',
			'value' => $model->subCompany !== null ? Html::a(Html::encode(Gx::str($model->subCompany)), Gx::url(['itemCompanyCategory/view', 'id' => Gx::pk($model->subCompany)])) : null,
			],
[
			'attribute' => 'company',
			'format' => 'raw',
			'value' => $model->company !== null ? Html::a(Html::encode(Gx::str($model->company)), Gx::url(['itemCompany/view', 'id' => Gx::pk($model->company)])) : null,
			],


	],
]); ?>

<?php
 $this->context->StartPanel(); ?>
<?php  $this->context->AddPanel($model->getRelationLabel('itemDetails'), $model->getRelatedDataProvider('itemDetails'),	'itemDetails','itemDetail');?>
<?php  $this->context->AddPanel($model->getRelationLabel('itemVendors'), $model->getRelatedDataProvider('itemVendors'),	'itemVendors','itemVendor');?>

<?php  $this->context->EndPanel(); ?>

<?php   echo CommentPortlet::widget([
	'model' => $model,
]);
?>