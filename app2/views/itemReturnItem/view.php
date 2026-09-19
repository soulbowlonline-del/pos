<?php
/**
 * Ported from protected/views/itemReturnItem/view.php.
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
'mrp',
'price',
'sale_rate',
'free',
'qty',
'discount',
'discount_amt',
'discount1',
'discount_amt1',
'cgst_per',
'sgst_per',
'cess_per',
'cgst_amt',
'sgst_amt',
'cess_amt',
'igst_per',
'igst_amt',
'tax_id',
'other_charge',
'total_amt',
'vendor_id',
'outlet_id',
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
'return_id',
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


<?php   echo CommentPortlet::widget([
	'model' => $model,
]);
?>