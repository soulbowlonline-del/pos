<?php
/**
 * Ported from protected/views/organization/admin.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\City;
use app\models\Country;
use app\models\Organization;
use app\models\State;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\ButtonGroup;
use app\widgets\GridView;
use app\widgets\Menu;
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
	$.fn.yiiGridView.update('organization-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content-header">
<h1><?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?></h1>
<?php //echo Html::a('Delete',array('user/empty'));?>
<?php echo ButtonGroup::widget([
	'buttons'=>$this->context->menu,
	'type'=>'success',
	'htmlOptions'=>['class'=> 'pull-right'],
]);
?>
</section>
<?php    echo Menu::widget([
       'type' => 'pills',
       'stacked' => false,
       'items' => [
        		['label' => 'Export',
        				'url' => ['organization/admin' ,'exportCSV'=>'1',
        						
        		
        		],
       		
       		],
       ],
   ]);  ?>
<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
         <div class="box-header"><h3 class="box-title"><?php echo  Html::encode($model->label(2));?></h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
  


<?php echo GridView::widget([
	'id' => 'organization-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => [
		'id',
		'title',
		'link',
		'email',
		'contact_no',
		'address:html',
		/*
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>Organization::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>Organization::getTypeOptions(),
				),
		array(
			'attribute' =>'city_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->city); },
			'filter'=>Gx::listData(City::class),
			),
		array(
			'attribute' =>'state_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->state); },
			'filter'=>Gx::listData(State::class),
			),
		array(
			'attribute' =>'country_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->country); },
			'filter'=>Gx::listData(Country::class),
			),
		array(
			'attribute' =>'updated_by',
			'value' => function ($data, $key, $index) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
[

		'header'=>'<a>Status</a>',
		'class' => ActionColumn::class,
		'template' => '{view}{update}', //include the standard buttons plus the new status button
		'htmlOptions'=> ['style'=>'width:80px'],
		'buttons'=>[
				'view'=>[
						'visible' => function ($data) { return $data->checkPermission ("organization/view")=="true"; },
						'url' => function ($data) { return Ui::to("organization/view", ["id" => $data->id]); },
						'label'=>'View',
						'options'=>['class'=>'view'],
							
				],
				'update'=>[
						'visible' => function ($data) { return $data->checkPermission ("organization/update")=="true"; },
						'url' => function ($data) { return Ui::to("organization/update", ["id" => $data->id]); },
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