<?php
/**
 * Ported from protected/views/advancePayment/admin.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\AdvancePayment;
use app\models\User;
use app\models\Vendor;
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
	$.fn.yiiGridView.update('advance-payment-grid', {
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
	'htmlOptions'=>['class'=> 'pull-right margin10'],
]);
?>
</section>

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
	'id' => 'advance-payment-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => [
	//	'id',
		'payment_date',
		'payment',
			'balance_amt',
			[
					'attribute' =>'vendor_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->vendor); },
					'filter'=>Gx::listData(Vendor::class),
			],
		/* array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>AdvancePayment::getStatusOptions(),
				),
		'update_time', */
		/*
		array(
			'attribute' =>'vendor_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->vendor); },
			'filter'=>Gx::listData(Vendor::class),
			),
		array(
			'attribute' =>'updated_by',
			'value' => function ($data, $key, $index) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
			[
			
					'header'=>'<a>Actions</a>',
					'class' => ActionColumn::class,
					'template' => '{view}{update}', //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					'buttons'=>[
							'view'=>[
									'visible' => function ($data) { return $data->checkPermission ("advancePayment/view")=="true"; },
									'url' => function ($data) { return Ui::to("advancePayment/view", ["id" => $data->id]); },
									'label'=>'View',
									'options'=>['class'=>'view'],
										
							],
							'update'=>[
									'visible' => function ($data) { return $data->checkPermission ("advancePayment/update")=="true"; },
									'url' => function ($data) { return Ui::to("advancePayment/update", ["id" => $data->id]); },
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