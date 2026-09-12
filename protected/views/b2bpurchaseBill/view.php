<?php

$this->breadcrumbs = array(
	$model->label(2) => array('index'),
	GxHtml::valueEx($model),
);


?>
<section class="content-header">
  
 
 <a href="<?php echo Yii::app()->createUrl('B2bpurchaseBill/printInvoivePdf',array('id'=>$id));?>" class="btn btn-info export-btn" target="_blank">Print Pdf</a>

</section>
<section class="content">
<div class="page-header">
<h1 class="pull-left"><?php echo isset($model->vendor)?$model->vendor:""; ?></h1>


<?php   $this->widget('bootstrap.widgets.TbButtonGroup', array(
	'buttons'=>$this->menu,
	'type'=>'success',
	'htmlOptions'=>array('class'=> 'pull-right'),
	));

	?>
<div class="clearfix"></div>


</div>
<?php $this->widget('bootstrap.widgets.TbDetailView', array(
	'data' => $model,
	'attributes' => array(
'id',
//'code',
			'grn_refrence_no',
			// 'bill_no',
			'start_date',
			'end_date',
			'gross_amt',
			'total_discount',
			'tax_amount',
			'bill_amount',
			'bill_other_discount',
			'net_bill_amount',
			array(
					'name' => 'status',
					'type' => 'raw',
					'value'=>$model->getStatusOptions($model->status),
			),
//'receiving_date',
/* array(
				'name' => 'status',
				'type' => 'raw',
				'value'=>$model->getStatusOptions($model->status),
				), */
/* array(
				'name' => 'type_id',
				'type' => 'raw',
				'value'=>$model->getTypeOptions($model->type_id),
				), */
//'is_open_po:boolean',
//'is_po_received:boolean',
'remarks:html',
//'payment_terms:html',
//'transport_mode',
//'purchase_order_amount',
//'charges_total_amount',
			/* array(
					'name' => 'discount_amount',
					'value' => $model->getBillDiscountAmount()
					
			),
			array(
					'name' => 'total_amount',
					'value' => $model->getTotalAmount()
						
			), */
//'frieght_charges',
//'extra_charges',
//'total_amount',
'create_time',
'update_time',
array(
			'name' => 'createUser',
			'type' => 'raw',
			'value' => $model->createUser !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->createUser)), array('user/view', 'id' => GxActiveRecord::extractPkValue($model->createUser, true))) : null,
			),
array(
			'name' => 'updatedBy',
			'type' => 'raw',
			'value' => $model->updatedBy !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->updatedBy)), array('user/view', 'id' => GxActiveRecord::extractPkValue($model->updatedBy, true))) : null,
			),
array(
			'name' => 'outlet',
			'type' => 'raw',
			'value' => $model->outlet !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->outlet)), array('outlet/view', 'id' => GxActiveRecord::extractPkValue($model->outlet, true))) : null,
			),
array(
			'name' => 'vendor',
			'type' => 'raw',
			'value' => $model->vendor !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->vendor)), array('vendor/view', 'id' => GxActiveRecord::extractPkValue($model->vendor, true))) : null,
			),
// array(
// 			'name' => 'purchaseOrder',
// 			'type' => 'raw',
// 			'value' => $model->purchaseOrder !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->purchaseOrder)), array('purchaseOrder/view', 'id' => GxActiveRecord::extractPkValue($model->purchaseOrder, true))) : null,
// 			),
array(
			'name' => 'organization',
			'type' => 'raw',
			'value' => $model->organization !== null ? GxHtml::link(GxHtml::encode(GxHtml::valueEx($model->organization)), array('organization/view', 'id' => GxActiveRecord::extractPkValue($model->organization, true))) : null,
			),
	),
)); ?>
<br>
<h1 class="pull-left">Bills</h1>
<div class="clearfix"></div>
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'bill-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $pobill->search(),
	'filter' => $pobill,
	'columns' => array(
		//'id',
		'bill_no',
	
			array(
					'name'=>'image_file1',
					'value'=>'$data->getNewImage($data->image_file1)',
					'type'=>'raw',
			),
			array(
					'name'=>'image_file2',
					'value'=>'$data->getNewImage($data->image_file2)',
					'type'=>'raw',
			),
			//	'image_file1:html',
			
			
		//'image_file3:html',
		/* array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>Bill::getTypeOptions(),
				), */
		/*
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>Bill::getStatusOptions(),
				),
		array(
			'name'=>'po_id',
			'value'=>'GxHtml::valueEx($data->po)',
			'filter'=>GxHtml::listDataEx(PurchaseOrder::model()->findAllAttributes(null, true)),
			),
		*/
			
	),
)); ?>
<br>
<h1 class="pull-left">Items</h1>
<div class="clearfix"></div>
<input type="button" value="Print Barcode" onclick="act();" />
<br>
 <select id="check_expiry">
  <option value="2">Without Expiry</option>  
  <option value="1">With Expiry</option>
 
</select> 
<div class="table-responsive customgridwidth">

<?php 
    $this->widget('bootstrap.widgets.TbGridView', array(
    'id'=>'purchase-order-detail-grid',
    'dataProvider'=>$billDetail->purchasesearch(),
    'filter'=>$billDetail,
    'columns'=>array(
        // array(
            // 'id'=>'mrsId',
            // 'class'=>'CCheckBoxColumn',
            // 'selectableRows' => '50',   
        // ),

    		array(
    				'class'           => 'CCheckBoxColumn',
    				'selectableRows'  => 100,
    				'value'           => '$data["id"]',
    				'checkBoxHtmlOptions' => array("name" =>"idList[]"),
    					
    		),
    			
        array(
			'name'=>'item_id',
			'value'=>'GxHtml::valueEx($data->item)',
		//	'filter'=>GxHtml::listDataEx(Item::model()->findAllAttributes(null, true)),
	),
			array(
					'header'=>'<a>Bar Code</a>',
					'name'=>'item_detail_id',
					'value'=>'GxHtml::valueEx($data->itemDetail)',
				//	'filter'=>GxHtml::listDataEx(ItemDetail::model()->findAllAttributes(null, true)),
			),
				'hsn_code',
    	//	'order',
    		array(
    				'class' => 'bootstrap.widgets.TbEditableColumn',
    				'name' => 'expiry_date',
    				//  'data_demanded_quantity' => '$data->demanded_quantity',
    				//  'data-state_id' => '$data->state_id',
    				//  'visible'=>'$data->getStateValue('.$model->id.') == 0',
    				'value' => "date('Y-m-d')",
    				'headerHtmlOptions' => array('style' => 'width: 110px'),
    				'editable' => array(
    						//'url'     => $this->createUrl('demandVoucherItem/updated'),
    						'placement'  => 'left',
    						'inputclass' => 'span3',
    						'attribute' =>'expiry_date',
    						'type'     => 'text',
    						//  'apply' => '$data->getStateValue('.$model->id.') == "0"'
    							
    						/*        'validate' => 'js: function(value) {
    						 if($.trim(value) > "$data->demanded_quantity") return "Approved Quantity can not be greater than Demanded Quantity";
    		}' */
    				)
    					
    		),
    		array(
    				'class' => 'bootstrap.widgets.TbEditableColumn',
    				'name' => 'packing_date',
    				//  'data_demanded_quantity' => '$data->demanded_quantity',
    				//  'data-state_id' => '$data->state_id',
    				//  'visible'=>'$data->getStateValue('.$model->id.') == 0',
    				'value' => "",
    				'headerHtmlOptions' => array('style' => 'width: 110px'),
    				'editable' => array(
    						//'url'     => $this->createUrl('demandVoucherItem/updated'),
    						'placement'  => 'left',
    						'inputclass' => 'span3',
    							
    						'attribute' =>'packing_date',
    						'type'     => 'text',
    						//  'apply' => '$data->getStateValue('.$model->id.') == "0"'
    		
    						/*        'validate' => 'js: function(value) {
    						 if($.trim(value) > "$data->demanded_quantity") return "Approved Quantity can not be greater than Demanded Quantity";
    		}' */
    				)
    		
    		),
			array(
					'name'=>'outlet_id',
					'value'=>'GxHtml::valueEx($data->outlet)',
					'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
			array(
					'header'=>'vendor',
					//'name'=>'vendor_id',
					'value'=>'GxHtml::valueEx($data->purchaseBill->vendor)',
					//'filter'=>GxHtml::listDataEx(Vendor::model()->findAllAttributes(null, true)),
					
			),
			array(
				'name'=>'req_qty',
				'value'=>'$data->req_qty',
				'filter'=>false,
			),
    		'approved_qty',
    		'mrp',
    		'price',
    		'sale_rate',
    		'discount',
    		'discount_amt',
    		'discount1',
    		'discount_amt1',
    	//	'vat',
    		array(
    				'header'=>'Tax',
    				'value'=>'GxHtml::valueEx($data->tax)',
    					
    		),
    		
    		array(
    				'visible'=>$billDetail->getGSTTrue($model->id) == true,
    				'name'=>'cgst_per',
    				'value'=>'$data->cgst_per',
    					
    		),
    		array(
    				'visible'=>$billDetail->getGSTTrue($model->id) == true,
    				'name'=>'sgst_per',
    				'value'=>'$data->sgst_per',
    					
    		),
    		array(
    				'visible'=>$billDetail->getGSTTrue($model->id) == true,
    				'name'=>'sgst_per',
    				'value'=>'$data->cess_per',
    					
    		),
    		array(
    				'visible'=>$billDetail->getGSTTrue($model->id) == false,
    				'name'=>'igst_per',
    				'value'=>'$data->igst_per',
    					
    		),
    		array(
    				'visible'=>$billDetail->getGSTTrue($model->id) == true,
    				'name'=>'sgst_per',
    				'value'=>'$data->cgst_amt',
    					
    		),
    		array(
    				'visible'=>$billDetail->getGSTTrue($model->id) == true,
    				'name'=>'sgst_amt',
    				'value'=>'$data->sgst_amt',
    					
    		),
    		array(
    				'visible'=>$billDetail->getGSTTrue($model->id) == true,
    				'name'=>'cess_amt',
    				'value'=>'$data->cess_amt',
    					
    		),
    		array(
    				'visible'=>$billDetail->getGSTTrue($model->id) == false,
    				'name'=>'igst_amt',
    				'value'=>'$data->igst_amt',
    					
    		),
    		
    		'other_charge',
    		'amount'
    		
        
    ),
)); ?>

</div>
</section>
<script>
function act()
{
	
	var idList = [];
	var date_list = [];
	var packing_date_list = [];
	var expiry_val = 1;
	$('input[type=checkbox]:checked').each(function() {
		idList.push(this.value); 
		date_list.push($('#PurchaseBillDetail_expiry_date_'+this.value).text()); 
		packing_date_list.push($('#PurchaseBillDetail_packing_date_'+this.value).text()); 
		
	});
	

        if($('#purchase-order-detail-grid_c0_all').prop("checked") == true){

            var all_check = $('#purchase-order-detail-grid_c0_all').val();
            var all_check_arr = jQuery.makeArray( all_check );
            var idList = $(idList).not(all_check_arr).get();
            var all_checkk = $('#PurchaseBillDetail_expiry_date_'+all_check).text();
            var all_checkk_arr = jQuery.makeArray( all_checkk );
            var date_list = $(date_list).not(all_checkk_arr).get();
            var all_checkkk = $('#PurchaseBillDetail_packing_date_'+all_check).text();
            var all_checkkk_arr = jQuery.makeArray( all_checkkk );
            var packing_date_list = $(packing_date_list).not(all_checkkk_arr).get();
        }

        expiry_val = $('#check_expiry').val();
        console.log(idList);
    	console.log(date_list);
    	console.log(packing_date_list);
	//var selected = item-detail-grid_c0_all
//var idList    = $("input[type=checkbox]:checked").serialize();
var url = "<?php echo CController::createUrl('purchaseBill/printBarcode') ?>";
jQuery.ajax({
    'type': 'POST',
    'url': '<?php echo CController::createUrl('purchaseBill/print') ?>',
  //  'dataType':"json",
    'data': {'billidList': idList,'bill_date_list':date_list,'bill_expiry_val':expiry_val,'packing_date_list':packing_date_list},
    'success': function (data) {
    	   console.log(data);
           console.log(url);
        if(data == 'success'){
        	window.open(url,'_blank');
     
        }else{
        	alert('Select Items');
        }
  
    },
    'cache': false
 }
 );	
console.log(idList);
}
</script>