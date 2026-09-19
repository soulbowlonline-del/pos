<?php
/**
 * Ported from protected/views/purchaseOrder/_list.php.
 */

use app\components\Gx;
use app\models\Mrn;
use app\models\Organization;
use app\models\Outlet;
use app\models\PurchaseOrder;
use app\models\User;
use app\models\Vendor;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'purchase-order-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		'code',
		'start_date',
		'end_date',
		'receiving_date',
		[
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>PurchaseOrder::getStatusOptions(),
				],
		/*
		array(
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>PurchaseOrder::getTypeOptions(),
				),
		array(
				'attribute' => 'is_open_po',
				'value' => function ($data) { return ($data->is_open_po === 0) ? Yii::t('app', 'No') : Yii::t('app', 'Yes'); },
				'filter' => array('0' => 'No', '1' => 'Yes'),
				),
		array(
				'attribute' => 'is_po_received',
				'value' => function ($data) { return ($data->is_po_received === 0) ? Yii::t('app', 'No') : Yii::t('app', 'Yes'); },
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
			'value' => function ($data) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		array(
			'attribute' =>'outlet_id',
			'value' => function ($data) { return Gx::str($data->outlet); },
			'filter'=>Gx::listData(Outlet::class),
			),
		array(
			'attribute' =>'vendor_id',
			'value' => function ($data) { return Gx::str($data->vendor); },
			'filter'=>Gx::listData(Vendor::class),
			),
		array(
			'attribute' =>'mrn_id',
			'value' => function ($data) { return Gx::str($data->mrn); },
			'filter'=>Gx::listData(Mrn::class),
			),
		array(
			'attribute' =>'organization_id',
			'value' => function ($data) { return Gx::str($data->organization); },
			'filter'=>Gx::listData(Organization::class),
			),
		*/
		[
			'class' => ActionColumn::class,
		],
	],
]); ?>