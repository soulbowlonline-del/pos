<div class="table-responsive">
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'purchase-bill-detail-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
			
			array(
					'name'=>'item_id',
					'value'=>'GxHtml::valueEx($data->item)',
					'filter'=>GxHtml::listDataEx(Item::model()->findAllAttributes(null, true)),
			),
			array(
					'name'=>'item_detail_id',
					'value'=>'GxHtml::valueEx($data->itemDetail)',
					'filter'=>GxHtml::listDataEx(ItemDetail::model()->findAllAttributes(null, true)),
			),
		'req_qty',
		//'bal_qty',
		'approved_qty',
// 			array(
// 					'name' => 'status',
// 					'value'=>'$data->getStatusOptions($data->status)',
// 					'filter'=>PurchaseBillDetail::getStatusOptions(),
// 			),
		'mrp',
		'price',
			'sale_rate',
			'discount',
			'discount_amt',
			'discount1',
			'discount_amt1',
			array(
					'name' => 'tax_id',
					'value'=>'isset($data->tax)?$data->tax:""',
					
			),
			
array(
    				
    				'name'=>'cgst_per',
    				'value'=>'$data->cgst_per',
	             	'type'=>'raw',
		          'visible'=>'$data->purchase_bill_id== 0',
    					
    		),
    		array(
    				'visible'=>'$data->getGSTTrue($data->purchase_bill_id)== true',
    				'name'=>'sgst_per',
    				'value'=>'$data->sgst_per',
    					
    		),
    		array(
    				'visible'=>'$data->getGSTTrue($data->purchase_bill_id)== true',
    				'name'=>'sgst_per',
    				'value'=>'$data->cess_per',
    					
    		),
    		array(
    				'visible'=>'$data->getGSTTrue($data->purchase_bill_id)== false',
    				'name'=>'igst_per',
    				'value'=>'$data->igst_per',
    					
    		),
    		array(
    				'visible'=>'$data->getGSTTrue($data->purchase_bill_id)== true',
    				'name'=>'sgst_per',
    				'value'=>'$data->cgst_amt',
    					
    		),
    		array(
    				'visible'=>'$data->getGSTTrue($data->purchase_bill_id)== true',
    				'name'=>'sgst_amt',
    				'value'=>'$data->sgst_amt',
    					
    		),
    		array(
    				'visible'=>'$data->getGSTTrue($data->purchase_bill_id)== true',
    				'name'=>'cess_amt',
    				'value'=>'$data->cess_amt',
    					
    		),
    		array(
    				'visible'=>'$data->getGSTTrue($data->purchase_bill_id)== false',
    				'name'=>'igst_amt',
    				'value'=>'$data->igst_amt',
    					
    		),
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
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>PurchaseBillDetail::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>PurchaseBillDetail::getTypeOptions(),
				),
		'charge_amount',
		'extra_charges',
		'remarks:html',
		'update_time',
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'item_detail_id',
			'value'=>'GxHtml::valueEx($data->itemDetail)',
			'filter'=>GxHtml::listDataEx(ItemDetail::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'purchase_bill_id',
			'value'=>'GxHtml::valueEx($data->purchaseBill)',
			'filter'=>GxHtml::listDataEx(PurchaseBill::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'outlet_id',
			'value'=>'GxHtml::valueEx($data->outlet)',
			'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
		*/
		/* array(
			'class' => 'CxButtonColumn',
		), */
	),
)); ?>
</div>