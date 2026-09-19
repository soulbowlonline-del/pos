<?php
/**
 * Ported from protected/views/userRole/admin.php.
 */

use app\components\Ui;
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
	$.fn.yiiGridView.update('user-role-grid', {
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
         <div class="box-header"><h3 class="box-title">User Roles</h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
  
<?php echo GridView::widget([
	'id' => 'user-role-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => [
		'id',
		'title',
		[
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>UserRole::getStatusOptions(),
				],
		
			[
			
					'header'=>'<a>Status</a>',
					'class' => ActionColumn::class,
					'template' => '{view}{update}', //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					'buttons'=>[
							'view'=>[
									'visible' => function ($data) { return $data->checkPermission ("userRole/view")=="true"; },
									'url' => function ($data) { return Ui::to("userRole/view", ["id" => $data->id]); },
									'label'=>'View',
									'options'=>['class'=>'view'],
										
							],
							'update'=>[
									'visible' => function ($data) { return $data->checkPermission ("userRole/update")=="true"; },
									'url' => function ($data) { return Ui::to("userRole/update", ["id" => $data->id]); },
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