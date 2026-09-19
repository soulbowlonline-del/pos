<?php
/**
 * Ported from protected/views/order/view.php.
 */

use app\components\Gx;
use app\models\Order;
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
'bill_no',
'bill_date',
'mode_of_payment',
'mode_of_delivery',
'qty',
'discount_amt',
'total_amt',
'paid_amt',
/* array(
				'attribute' => 'status',
				'format' => 'raw',
				'value'=>$model->getStatusOptions($model->status),
				),
array(
				'attribute' => 'type_id',
				'format' => 'raw',
				'value'=>$model->getTypeOptions($model->type_id),
				), */
//'city_id',
/* array(
				'attribute' => 'state_id',
				'format' => 'raw',
				'value'=>$model->getStatusOptions($model->state_id),
				), */
//'country_id',
[
					'attribute' => 'Outlet',
					'value'=>isset($model->outlet)?$model->outlet:"",
					//'filter'=>Order::getStatusOptions(),
			],
			
'address:html',
'note:html',
'create_time',
'update_time',
//'customer_id',
[
		'attribute' => 'Customer',
		'value'=>isset($model->customer)?$model->customer:"",
		//'filter'=>Order::getStatusOptions(),
]

//'updated_by',
			
	],
]); ?>

<?php
 $this->context->StartPanel(); ?>
<?php  $this->context->AddPanel($model->getRelationLabel('orderItems'), $model->getRelatedDataProvider('orderItems'),	'orderItems','orderItem');?>
<?php  //$this->context->AddPanel($model->getRelationLabel('orderRefunds'), $model->getRelatedDataProvider('orderRefunds'),	'orderRefunds','orderRefund');?>
<?php  $this->context->EndPanel(); ?>

<?php   echo CommentPortlet::widget([
	'model' => $model,
]);
?>
</section>