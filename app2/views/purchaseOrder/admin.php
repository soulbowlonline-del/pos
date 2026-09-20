<?php
/**
 * Ported from protected/views/purchaseOrder/admin.php.
 */

use app\components\Access;
use app\components\Gx;
use app\components\Ui;
use app\models\Mrn;
use app\models\Organization;
use app\models\Outlet;
use app\models\PurchaseOrder;
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
	$.fn.yiiGridView.update('purchase-order-grid', {
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
<div class="table-responsive customgridwidth">


<?php echo GridView::widget([
	'id' => 'purchase-order-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => [
		'id',
		'code',
		'start_date',
		/* 'end_date',
		'receiving_date', */
		[
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>PurchaseOrder::getStatusOptions(),
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
			/* array(
					'attribute' =>'mrn_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->mrn); },
					'filter'=>Gx::listData(Mrn::class),
			), */
		/*
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>PurchaseOrder::getTypeOptions(),
				),
		array(
				'attribute' => 'is_open_po',
				'value' => function ($data, $key, $index) { return ($data->is_open_po === 0) ? Yii::t('app', 'No') : Yii::t('app', 'Yes'); },
				'filter' => array('0' => 'No', '1' => 'Yes'),
				),
		array(
				'attribute' => 'is_po_received',
				'value' => function ($data, $key, $index) { return ($data->is_po_received === 0) ? Yii::t('app', 'No') : Yii::t('app', 'Yes'); },
				'filter' => array('0' => 'No', '1' => 'Yes'),
				),
		'remarks:html',
		'payment_terms:html',
		'transport_mode',
		'purchase_order_amount',
		'charges_total_amount',
		'discount_amount',
		'frieght_charges',
		'extra_charges',
		'total_amount',
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
			'attribute' =>'vendor_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->vendor); },
			'filter'=>Gx::listData(Vendor::class),
			),
		array(
			'attribute' =>'mrn_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->mrn); },
			'filter'=>Gx::listData(Mrn::class),
			),
		array(
			'attribute' =>'organization_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->organization); },
			'filter'=>Gx::listData(Organization::class),
			),
		*/
				[
				
						'header'=>'<a>Status</a>',
						'class' => ActionColumn::class,
						'template' => '{view}', //include the standard buttons plus the new status button
						'htmlOptions'=> ['style'=>'width:80px'],
						'buttons'=>[
								'view'=>[
										'visible' => function ($data) { return Access::check("purchaseOrder/view")=="true"; },
										'url' => function ($data) { return Ui::to("purchaseOrder/view", ["id" => $data->id]); },
										'label'=>'View',
										'options'=>['class'=>'view'],
											
								]
						]
				],
	],
]); ?>
					</div>
</section>