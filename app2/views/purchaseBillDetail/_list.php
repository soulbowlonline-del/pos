<?php
/**
 * Ported from protected/views/purchaseBillDetail/_list.php.
 */

use app\components\Gx;
use app\models\Item;
use app\models\ItemDetail;
use app\models\Outlet;
use app\models\PurchaseBill;
use app\models\PurchaseBillDetail;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<div class="table-responsive">
<?php echo GridView::widget([
	'id' => 'purchase-bill-detail-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
			
			[
					'attribute' =>'item_id',
					'value' => function ($data) { return Gx::str($data->item); },
					'filter'=>Gx::listData(Item::class),
			],
			[
					'attribute' =>'item_detail_id',
					'value' => function ($data) { return Gx::str($data->itemDetail); },
					'filter'=>Gx::listData(ItemDetail::class),
			],
		'req_qty',
		//'bal_qty',
		'approved_qty',
// 			array(
// 					'attribute' => 'status',
// 					'value' => function ($data) { return $data->getStatusOptions($data->status); },
// 					'filter'=>PurchaseBillDetail::getStatusOptions(),
// 			),
		'mrp',
		'price',
			'sale_rate',
			'discount',
			'discount_amt',
			'discount1',
			'discount_amt1',
			[
					'attribute' => 'tax_id',
					'value' => function ($data) { return isset($data->tax)?$data->tax:""; },
					
			],
			
[
    				
    				'attribute' =>'cgst_per',
    				'value' => function ($data) { return $data->cgst_per; },
	             	'format' => 'raw',
		          'visible'=>'$data->purchase_bill_id== 0',
    					
    		],
    		[
    				'visible'=>'$data->getGSTTrue($data->purchase_bill_id)== true',
    				'attribute' =>'sgst_per',
    				'value' => function ($data) { return $data->sgst_per; },
    					
    		],
    		[
    				'visible'=>'$data->getGSTTrue($data->purchase_bill_id)== true',
    				'attribute' =>'sgst_per',
    				'value' => function ($data) { return $data->cess_per; },
    					
    		],
    		[
    				'visible'=>'$data->getGSTTrue($data->purchase_bill_id)== false',
    				'attribute' =>'igst_per',
    				'value' => function ($data) { return $data->igst_per; },
    					
    		],
    		[
    				'visible'=>'$data->getGSTTrue($data->purchase_bill_id)== true',
    				'attribute' =>'sgst_per',
    				'value' => function ($data) { return $data->cgst_amt; },
    					
    		],
    		[
    				'visible'=>'$data->getGSTTrue($data->purchase_bill_id)== true',
    				'attribute' =>'sgst_amt',
    				'value' => function ($data) { return $data->sgst_amt; },
    					
    		],
    		[
    				'visible'=>'$data->getGSTTrue($data->purchase_bill_id)== true',
    				'attribute' =>'cess_amt',
    				'value' => function ($data) { return $data->cess_amt; },
    					
    		],
    		[
    				'visible'=>'$data->getGSTTrue($data->purchase_bill_id)== false',
    				'attribute' =>'igst_amt',
    				'value' => function ($data) { return $data->igst_amt; },
    					
    		],
			'other_charge',
			'amount',
			
		
		/*
		'discount',
		'discount_amt',
		'vat',
		'other_charge',
		'amount',
		'sale_rate',
		array(
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>PurchaseBillDetail::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>PurchaseBillDetail::getTypeOptions(),
				),
		'charge_amount',
		'extra_charges',
		'remarks:html',
		'update_time',
		array(
			'attribute' =>'updated_by',
			'value' => function ($data) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		array(
			'attribute' =>'item_detail_id',
			'value' => function ($data) { return Gx::str($data->itemDetail); },
			'filter'=>Gx::listData(ItemDetail::class),
			),
		array(
			'attribute' =>'purchase_bill_id',
			'value' => function ($data) { return Gx::str($data->purchaseBill); },
			'filter'=>Gx::listData(PurchaseBill::class),
			),
		array(
			'attribute' =>'outlet_id',
			'value' => function ($data) { return Gx::str($data->outlet); },
			'filter'=>Gx::listData(Outlet::class),
			),
		*/
		/* array(
			'class' => ActionColumn::class,
		), */
	],
]); ?>
</div>