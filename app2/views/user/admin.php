<?php
/**
 * Ported from protected/views/user/admin.php.
 */

use app\components\Access;
use app\components\Ui;
use app\models\User;
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
	$.fn.yiiGridView.update('user-grid', {
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



<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
         <div class="box-header"><h3 class="box-title">Users</h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
 
<?php  echo GridView::widget([
	//echo GridView::widget(array(

'id' => 'user-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'pager' => [
	'class' => \app\widgets\LinkPager::class,
'htmlOptions' => ['class' => 'pager'
],
],
	'columns' => [
		'id',
		'full_name',
		'username',
		'email',
		//'lat',
		//'long',
		'contact_no',
			[
					'attribute' => 'role_id',
					
					'value' => function ($data, $key, $index) { return isset($data->role)?$data->role:""; },
					'filter'=>User::getAllRoleOptions(),
			],
			[
					
					'header'=>'<a>Status</a>',
					'class' => ActionColumn::class,
					'template' => '{inactivate}{activate}',  //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					'buttons'=>[
							'activate'=>[
									'visible'=>'$data->state_id=='.User::STATUS_INACTIVE,
									'url' => function ($data) { return Ui::to("user/toggle", ["id" => $data->id]); },
									'label'=>'activate',
									'options'=>['class'=>'update'],
										
							],
							'inactivate'=>[
									'visible'=>'$data->state_id=='.User::STATUS_ACTIVE,
									'url' => function ($data) { return Ui::to("user/toggle", ["id" => $data->id]); },
									'label'=>'inactivate',
									'options'=>['class'=>'update'],
			
							]
					]
			],
/*
 'date_of_birth',
 'about_me:html',
 'address',
 'postal_code',
 'country',
 'city',
 array(
 'attribute' => 'state',
 'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->state); },
 'filter'=>User::getStatusOptions(),
 ),
 'lang',
 'image_file',
 'is_passenger',
 'is_dispatcher',
 'is_driver',
 'role_id',
 array(
 'attribute' => 'state_id',
 'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->state_id); },
 'filter'=>User::getStatusOptions(),
 ),
 array(
 'attribute' => 'type_id',
 'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
 'filter'=>User::getTypeOptions(),
 ),
 'last_visit_time',
 'last_action_time',
 'last_password_change',
 array(
 'attribute' => 'is_active',
 'value' => function ($data, $key, $index) { return ($data->is_active === 0) ? Yii::t('app', 'No') : Yii::t('app', 'Yes'); },
 'filter' => array('0' => 'No', '1' => 'Yes'),
 ),
 'login_error_count',
 */
			[
			
					'header'=>'<a>Status</a>',
					'class' => ActionColumn::class,
					'template' => '{view}{update}', //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					'buttons'=>[
							'view'=>[
									'visible' => Access::check('user/view'),
									'url' => function ($data) { return Ui::to("user/view", ["id" => $data->id]); },
									'label'=>'View',
									'options'=>['class'=>'view'],
										
							],
							'update'=>[
									'visible' => Access::check('user/update'),
									'url' => function ($data) { return Ui::to("user/update", ["id" => $data->id]); },
									'label'=>'Update',
									'options'=>['class'=>'update'],
			
							]
					]
			],
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