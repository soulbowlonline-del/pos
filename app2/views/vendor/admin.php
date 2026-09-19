<?php
/**
 * Ported from protected/views/vendor/admin.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\City;
use app\models\Country;
use app\models\Outlet;
use app\models\State;
use app\models\User;
use app\models\Vendor;
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
	$.fn.yiiGridView.update('vendor-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content-header">
<h1><?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?>
</h1>
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
        				'url' => ['vendor/admin' ,'exportCSV'=>'1',
        						
        		
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
	'id' => 'vendor-grid',
	'type'=>'striped bordered condensed',
		'pager'=>true,
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => [
		'id',
		'name',
		//'email',
		
		'contact_no',
			'secondary_contact_no',
			'primary_address:html',
		/*'secondary_contact_no',
		
		'primary_address:html',
		'secondary_address:html',
		'tax_no',
		array(
				'attribute' => 'is_local_vendor',
				'value' => function ($data, $key, $index) { return ($data->is_local_vendor === 0) ? Yii::t('app', 'No') : Yii::t('app', 'Yes'); },
				'filter' => array('0' => 'No', '1' => 'Yes'),
				),
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>Vendor::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>Vendor::getTypeOptions(),
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
			'attribute' =>'outlet_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->outlet); },
			'filter'=>Gx::listData(Outlet::class),
			),
		array(
			'attribute' =>'updated_by',
			'value' => function ($data, $key, $index) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
[

		'header'=>'<a>Mrs Details</a>',
		'class' => ActionColumn::class,
		'template' => '{Mrs Details}', //include the standard buttons plus the new status button
		'htmlOptions'=> ['style'=>'width:80px'],
		'buttons'=>[
				'Mrs Details'=>[
						//'visible' => function ($data) { return $data->checkPermission ("mrsDetail/admin")=="true"; },
						'url' => function ($data) { return Ui::to("mrsDetail/admin", ["id" => $data->id]); },
						'label'=>'Mrs Details',
						'options'=>['class'=>'view'],
							
				],
					
		]
],
[

		'header'=>'<a>Status</a>',
		'class' => ActionColumn::class,
		'template' => '{view}{update}', //include the standard buttons plus the new status button
		'htmlOptions'=> ['style'=>'width:80px'],
		'buttons'=>[
				'view'=>[
						'visible' => function ($data) { return $data->checkPermission ("vendor/view")=="true"; },
						'url' => function ($data) { return Ui::to("vendor/view", ["id" => $data->id]); },
						'label'=>'View',
						'options'=>['class'=>'view'],
							
				],
				'update'=>[
						'visible' => function ($data) { return $data->checkPermission ("vendor/create")=="true"; },
						'url' => function ($data) { return Ui::to("vendor/create", ["id" => $data->id]); },
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