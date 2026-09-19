<?php
/**
 * Ported from protected/views/user/view.php.
 */

use app\components\Gx;
use app\models\User;
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
'full_name',
'email',
[
				'attribute' => 'gender',
				'format' => 'raw',
				'value'=>isset($model->gender)?$model->getGenderOptions($model->gender):'',
				],
'contact_no',
			[
					'attribute' => 'role_id',
					'format' => 'raw',
					'value'=>isset($model->role)?$model->role:'',
			],
			
		/* 	array(
					'visible'=>$model->role_id == User::ROLE_MERCHANT,
					'attribute' => 'store',
					'format' => 'raw',
					'value'=>$model->getStoreName(),
			), */
/*'date_of_birth',
'about_me:html',
'address',
'postal_code',
'country',
'city',
array(
				'attribute' => 'state',
				'format' => 'raw',
				'value'=>$model->getStatusOptions($model->state),
				),*/

//'image_file',
'is_active:boolean',

	
	],
]); ?>

</section>



