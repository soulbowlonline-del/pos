<?php
/**
 * Ported from protected/views/onlineOrder/view.php.
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
'order_id',
'item_count',
'grand_total',

[
				'attribute' => 'first_name',
				'format' => 'raw',
				'value'=>$model->getCustomerName(),
				],
'street',
'city',
			'mobile',
'telephone',
'zip_code',
'country',
'delivery_slot',
'ship_name',
			'delivery_boy',
			'delivery_telephone',
			'payment_method',
			'delivery_method',
/* array(
				'attribute' => 'type_id',
				'format' => 'raw',
				'value'=>$model->getTypeOptions($model->type_id),
				), */
[
				'attribute' => 'status',
				'format' => 'raw',
				'value'=>$model->getStatusOptions($model->status),
				],
'create_time',
'order_from',
'comment'

	],
]); ?>

<?php
 $this->context->StartPanel(); ?>
<?php  $this->context->AddPanel($model->getRelationLabel('Items'), $model->getRelatedDataProvider('onlineOrderItems'),	'onlineOrderItems','onlineOrderItem');?>
<?php // $this->context->AddPanel($model->getRelationLabel('purchaseOrders'), $model->getRelatedDataProvider('purchaseOrders'),	'purchaseOrders','purchaseOrder');?>
<?php  $this->context->EndPanel(); ?>
</section>