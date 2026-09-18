<?php
/**
 * Ported from protected/views/emp/view.php.
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
'code',
'name',
'email',
'contact_no',
[
				'attribute' => 'gender_id',
				'format' => 'raw',
				'value'=>isset($model->gender_id)?$model->getGenderOptions($model->gender_id):'',
				],
			[
					'attribute' => 'role_id',
					
					'value'=>$model->getRoleValues(),
			],
'date_of_birth',
'date_of_joining',
'permanent_address:html',
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
'temp_address:html',
			[
					'attribute' => 'temp_city_id',
					'value'=>isset($model->tempcity)?$model->tempcity:'',
			],
			[
					'attribute' => 'temp_state_id',
					'value'=>isset($model->tempstate)?$model->tempstate:'',
			],
			[
					'attribute' => 'temp_country_id',
					'value'=>isset($model->tempcountry)?$model->tempcountry:'',
			],
			[
					'attribute' => 'outlet_id',
					'value'=>isset($model->outlet)?$model->outlet:'',
			],
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
			'attribute' => 'designation',
			'format' => 'raw',
			'value' => $model->designation !== null ? Html::a(Html::encode(Gx::str($model->designation)), Gx::url(['designation/view', 'id' => Gx::pk($model->designation)])) : null,
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
<?php  $this->context->AddPanel($model->getRelationLabel('empShifts'), $model->getRelatedDataProvider('empShifts'),	'empShifts','empShift');?>
<?php  //$this->context->AddPanel($model->getRelationLabel('users'), $model->getRelatedDataProvider('users'),	'users','user');?>
<?php  $this->context->EndPanel(); ?>

</section>