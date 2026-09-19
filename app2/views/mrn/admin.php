<?php
/**
 * Ported from protected/views/mrn/admin.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Mrn;
use app\models\Mrs;
use app\models\Organization;
use app\models\Outlet;
use app\models\User;
use app\models\Vendor;
use app\widgets\ActionColumn;
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
	$.fn.yiiGridView.update('mrn-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content">
<div class="page-header">
	<h1><?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?></h1>
<div class="clearfix"></div>
</div>
<div class="table-responsive">


<?php echo GridView::widget([
	'id' => 'mrn-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => [
		//'id',
		'code',
		//'mrs_date',
	//	'mrs_update_date',
		'mrs_req_date',
		[
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>Mrn::getStatusOptions(),
				],
			[
					'attribute' =>'outlet_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->outlet); },
					'filter'=>Gx::listData(Outlet::class),
			],
			[
					'attribute' =>'vendor_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->vendor); },
					'filter'=>Gx::listData(Vendor::class),
			],
		/*
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>Mrn::getTypeOptions(),
				),
		'remarks:html',
		'update_time',
		array(
			'attribute' =>'updated_by',
			'value' => function ($data, $key, $index) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		array(
			'attribute' =>'outlet_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->outlet); },
			'filter'=>Gx::listData(Outlet::class),
			),
		array(
			'attribute' =>'mrs_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->mrs); },
			'filter'=>Gx::listData(Mrs::class),
			),
		array(
			'attribute' =>'organization_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->organization); },
			'filter'=>Gx::listData(Organization::class),
			),
		*/[
				
						'header'=>'<a>Change Status</a>',
						'class' => ActionColumn::class,
						'template' => '{approve}', //include the standard buttons plus the new status button
						'htmlOptions'=> ['style'=>'width:80px'],
						'buttons'=>[
								'approve'=>[
										'visible' => function ($data) { return $data->status==Mrn::STATUS_UNAPPROVED; },
										'url' => function ($data) { return Ui::to("mrn/approve", ["id" => $data->id]); },
										'label'=>'Approve',
										'options'=>['class'=>'approve'],
											
								]
						]
				],
				[
				
						'header'=>'<a>Status</a>',
						'class' => ActionColumn::class,
						'template' => '{view}', //include the standard buttons plus the new status button
						'htmlOptions'=> ['style'=>'width:80px'],
						'buttons'=>[
								'view'=>[
										'visible' => function ($data) { return $data->checkPermission ("mrn/view")=="true"; },
										'url' => function ($data) { return Ui::to("mrn/view", ["id" => $data->id]); },
										'label'=>'View',
										'options'=>['class'=>'view'],
											
								]
						]
				],
	],
]); ?>
				</div>
</section>