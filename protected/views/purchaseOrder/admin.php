<?php

$this->breadcrumbs = array(
	$model->label(2) => array('index'),
	Yii::t('app', 'Manage'),
);


Yii::app()->clientScript->registerScript('search', "
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
	<h1><?php echo Yii::t('app', 'Manage') . ' : ' . GxHtml::encode($model->label(2)); ?></h1>
<div class="clearfix"></div>
</div>
<div class="table-responsive customgridwidth">


<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'purchase-order-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => array(
		'id',
		'code',
		'start_date',
		/* 'end_date',
		'receiving_date', */
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>PurchaseOrder::getStatusOptions(),
				),
			array(
					'name'=>'outlet_id',
					'value'=>'GxHtml::valueEx($data->outlet)',
					'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
			array(
					'name'=>'vendor_id',
					'value'=>'GxHtml::valueEx($data->vendor)',
					'filter'=>GxHtml::listDataEx(Vendor::model()->findAllAttributes(null, true)),
			),
			/* array(
					'name'=>'mrn_id',
					'value'=>'GxHtml::valueEx($data->mrn)',
					'filter'=>GxHtml::listDataEx(Mrn::model()->findAllAttributes(null, true)),
			), */
		/*
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>PurchaseOrder::getTypeOptions(),
				),
		array(
				'name' => 'is_open_po',
				'value' => '($data->is_open_po === 0) ? Yii::t(\'app\', \'No\') : Yii::t(\'app\', \'Yes\')',
				'filter' => array('0' => Yii::t('app', 'No'), '1' => Yii::t('app', 'Yes')),
				),
		array(
				'name' => 'is_po_received',
				'value' => '($data->is_po_received === 0) ? Yii::t(\'app\', \'No\') : Yii::t(\'app\', \'Yes\')',
				'filter' => array('0' => Yii::t('app', 'No'), '1' => Yii::t('app', 'Yes')),
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
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'outlet_id',
			'value'=>'GxHtml::valueEx($data->outlet)',
			'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'vendor_id',
			'value'=>'GxHtml::valueEx($data->vendor)',
			'filter'=>GxHtml::listDataEx(Vendor::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'mrn_id',
			'value'=>'GxHtml::valueEx($data->mrn)',
			'filter'=>GxHtml::listDataEx(Mrn::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'organization_id',
			'value'=>'GxHtml::valueEx($data->organization)',
			'filter'=>GxHtml::listDataEx(Organization::model()->findAllAttributes(null, true)),
			),
		*/
				array(
				
						'header'=>'<a>Status</a>',
						'class'=>'FaButtonColumn',
						'template' => '{view}', //include the standard buttons plus the new status button
						'htmlOptions'=> array('style'=>'width:80px'),
						'buttons'=>array(
								'view'=>array(
										'visible'=>'$data->checkPermission ("purchaseOrder/view")=="true"',
										'url' =>'Yii::app()->controller->createUrl("purchaseOrder/view", array("id" => $data->id))',
										'label'=>'View',
										'options'=>array('class'=>'view'),
											
								)
						)
				),
	),
)); ?>
					</div>
</section>