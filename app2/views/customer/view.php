<?php
/**
 * Ported from protected/views/customer/view.php.
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
'name',
'email',
'fax',
'address:html',
			[
					'attribute' => 'city_id',
			      'value'=>isset($model->city)?$model->city:'',
			],
[
				'attribute' => 'state_id',
				'value'=>isset($model->state)?$model->state:'',
				],
			[
					'attribute' => 'country_id',
					'value'=>isset($model->country)?$model->country:'',
			],

'zip_code',
'opening_balance',
'credit_limit',
'payment_days',
'contact_no',
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
'update_time',
/* array(
			'attribute' => 'createUser',
			'format' => 'raw',
			'value' => $model->createUser !== null ? Html::a(Html::encode(Gx::str($model->createUser)), array('user/view', 'id' => Gx::pk($model->createUser))) : null,
			),
array(
			'attribute' => 'updatedBy',
			'format' => 'raw',
			'value' => $model->updatedBy !== null ? Html::a(Html::encode(Gx::str($model->updatedBy)), array('user/view', 'id' => Gx::pk($model->updatedBy))) : null,
			), */
	],
]); ?>

<?php
 $this->context->StartPanel(); ?>
<?php  $this->context->AddPanel($model->getRelationLabel('orders'), $model->getRelatedDataProvider('orders'),	'orders','order','_list');?>
<?php  //$this->context->AddNewPanel($model->getRelationLabel('Items'), $model->getRelatedDataProvider('customerorders'),	'customerorders','order','_details',$model);?>

<?php // $this->context->AddPanel($model->getRelationLabel('orderHolds'), $model->getRelatedDataProvider('orderHolds'),	'orderHolds','orderHold');?>
<?php // $this->context->AddPanel($model->getRelationLabel('orderRefunds'), $model->getRelatedDataProvider('orderRefunds'),	'orderRefunds','orderRefund');?>
<?php  $this->context->EndPanel(); ?>

</section>