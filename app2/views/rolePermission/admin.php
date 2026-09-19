<?php
/**
 * Ported from protected/views/rolePermission/admin.php.
 */

use app\components\Gx;
use app\models\Permission;
use app\models\RolePermission;
use app\models\UserRole;
use app\widgets\ActionColumn;
use app\widgets\ButtonGroup;
use app\widgets\GridView;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	'Manage',
];


$this->registerJs("
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$.fn.yiiGridView.update('role-permission-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<section class="content-header">
  <h1> <?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?> </h1>
<?php //echo Html::a('Delete',array('user/empty'));?>
<?php echo ButtonGroup::widget([
	'buttons'=>$this->context->menu,
	'type'=>'success',
	'htmlOptions'=>['class'=> 'pull-right bttn-box'],
]);
?>
</section>


<?php if(Yii::$app->user->hasFlash('success')){ ?>

<div class="alert alert-success"><?php echo Yii::$app->user->getFlash('success'); ?>
</div>
<?php } ?>
<?php if(Yii::$app->user->hasFlash('error')){ ?>

<div class="alert alert-danger"><?php echo Yii::$app->user->getFlash('error'); ?>
</div>
<?php } ?>




<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
        <div class="box-header"><h3 class="box-title">Role Permissions</h3></div>
           <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
  


<?php echo GridView::widget([
	'id' => 'role-permission-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'pager'=>true,
	'filter' => $model,
	'columns' => [
		'id',
		[
			'attribute' =>'role_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->role); },
			'filter'=>Gx::listData(UserRole::class),
			],
		[
			'attribute' =>'permission_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->permission); },
			'filter'=>Gx::listData(Permission::class),
			],
		[
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>RolePermission::getStatusOptions(),
				],
		
			/* array(
					'header'=>'Actions',
					'class' => ActionColumn::class,
					'template' => '{view}{update}{delete}'
			
			), */
	],
]); ?>

</div>
</div>
</div>
</div>
</div>
</div>
</div>
</section>