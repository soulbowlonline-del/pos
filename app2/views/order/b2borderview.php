<?php
/**
 * Ported from protected/views/order/b2borderview.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Item;
use app\models\ItemDetail;
use app\models\Outlet;
use app\models\Vendor;
use app\widgets\ButtonGroup;
use app\widgets\CheckboxColumn;
use app\widgets\DetailView;
use app\widgets\GridView;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	Gx::str($model),
];


?>
<section class="content-header">
  
 
 <a href="<?php echo Ui::to('B2bpurchaseBill/printInvoivePdf',array('id'=>$id));?>" class="btn btn-info export-btn" target="_blank">Print Pdf</a>

</section>
<section class="content">
<div class="page-header">
<h1 class="pull-left"><?php echo isset($model->vendor)?$model->vendor:""; ?></h1>


<?php   echo ButtonGroup::widget([
	'buttons'=>$this->context->menu,
	'type'=>'success',
	'htmlOptions'=>['class'=> 'pull-right'],
	]);

	?>
<div class="clearfix"></div>


</div>
<?php echo DetailView::widget([
	'data' => $model,
	'attributes' => [
'id',
//'code',
			'grn_refrence_no',
			'bill_no',
'start_date',
'end_date',
			'gross_amt',
			'total_discount',
			'tax_amount',
			'bill_amount',
			'bill_other_discount',
			'net_bill_amount',
			[
					'attribute' => 'status',
					'format' => 'raw',
					'value'=>$model->getStatusOptions($model->status),
			],
//'receiving_date',
/* array(
				'attribute' => 'status',
				'format' => 'raw',
				'value'=>$model->getStatusOptions($model->status),
				), */
/* array(
				'attribute' => 'type_id',
				'format' => 'raw',
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
					'attribute' => 'discount_amount',
					'value' => $model->getBillDiscountAmount()
					
			),
			array(
					'attribute' => 'total_amount',
					'value' => $model->getTotalAmount()
						
			), */
//'frieght_charges',
//'extra_charges',
//'total_amount',
'create_time',
'update_time',
[
			'attribute' => 'createUser',
			'format' => 'raw',
			'value' => $model->createUser !== null ? Html::a(Html::encode(Gx::str($model->createUser)), Gx::url(['user/view', 'id' => Gx::pk($model->createUser)])) : null,
			],
[
			'attribute' => 'updatedBy',
			'format' => 'raw',
			'value' => $model->updatedBy !== null ? Html::a(Html::encode(Gx::str($model->updatedBy)), Gx::url(['user/view', 'id' => Gx::pk($model->updatedBy)])) : null,
			],
[
			'attribute' => 'outlet',
			'format' => 'raw',
			'value' => $model->outlet !== null ? Html::a(Html::encode(Gx::str($model->outlet)), Gx::url(['outlet/view', 'id' => Gx::pk($model->outlet)])) : null,
			],
[
			'attribute' => 'vendor',
			'format' => 'raw',
			'value' => $model->vendor !== null ? Html::a(Html::encode(Gx::str($model->vendor)), Gx::url(['vendor/view', 'id' => Gx::pk($model->vendor)])) : null,
			],
// array(
// 			'attribute' => 'purchaseOrder',
// 			'format' => 'raw',
// 			'value' => $model->purchaseOrder !== null ? Html::a(Html::encode(Gx::str($model->purchaseOrder)), array('purchaseOrder/view', 'id' => Gx::pk($model->purchaseOrder))) : null,
// 			),
[
			'attribute' => 'organization',
			'format' => 'raw',
			'value' => $model->organization !== null ? Html::a(Html::encode(Gx::str($model->organization)), Gx::url(['organization/view', 'id' => Gx::pk($model->organization)])) : null,
			],
	],
]); ?>
<br>

<h1 class="pull-left">Items</h1>
<div class="clearfix"></div>
 
<div class="table-responsive customgridwidth">

<?php 
    echo GridView::widget([
    'id'=>'purchase-order-detail-grid',
    'dataProvider'=>$billDetail->purchasesearch(),
    'filter'=>$billDetail,
    'columns'=>[
        // array(
            // 'id'=>'mrsId',
            // 'class' => CheckboxColumn::class,
            // 'selectableRows' => '50',   
        // ),

    	
        [
			'attribute' =>'item_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->item); },
		//	'filter'=>Gx::listData(Item::class),
	],
			[
					'header'=>'<a>Bar Code</a>',
					'attribute' =>'item_detail_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->itemDetail); },
				//	'filter'=>Gx::listData(ItemDetail::class),
			],
			
    	
			[
					'attribute' =>'outlet_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->outlet); },
					'filter'=>Gx::listData(Outlet::class),
			],
			[
					'header'=>'vendor',
					//'attribute' =>'vendor_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->purchaseBill->vendor); },
					//'filter'=>Gx::listData(Vendor::class),
					
			],
			
    		'hsn_code',
    		'approved_qty',
    		'mrp',
    		'price',
    		'sale_rate',
    		
    		'discount_amt',
    		
    	//	'vat',
    		[
    				'header'=>'Tax',
    				'value' => function ($data, $key, $index) { return Gx::str($data->tax); },
    					
    		],
    		
    		[
    				'visible'=>$billDetail->getGSTTrue($model->id) == true,
    				'attribute' =>'cgst_per',
    				'value' => function ($data, $key, $index) { return $data->cgst_per; },
    					
    		],
    		[
    				'visible'=>$billDetail->getGSTTrue($model->id) == true,
    				'attribute' =>'sgst_per',
    				'value' => function ($data, $key, $index) { return $data->sgst_per; },
    					
    		],
    		[
    				'visible'=>$billDetail->getGSTTrue($model->id) == true,
    				'attribute' =>'sgst_per',
    				'value' => function ($data, $key, $index) { return $data->cess_per; },
    					
    		],
    		[
    				'visible'=>$billDetail->getGSTTrue($model->id) == false,
    				'attribute' =>'igst_per',
    				'value' => function ($data, $key, $index) { return $data->igst_per; },
    					
    		],
    		[
    				'visible'=>$billDetail->getGSTTrue($model->id) == true,
    				'attribute' =>'sgst_per',
    				'value' => function ($data, $key, $index) { return $data->cgst_amt; },
    					
    		],
    		[
    				'visible'=>$billDetail->getGSTTrue($model->id) == true,
    				'attribute' =>'sgst_amt',
    				'value' => function ($data, $key, $index) { return $data->sgst_amt; },
    					
    		],
    		[
    				'visible'=>$billDetail->getGSTTrue($model->id) == true,
    				'attribute' =>'cess_amt',
    				'value' => function ($data, $key, $index) { return $data->cess_amt; },
    					
    		],
    		[
    				'visible'=>$billDetail->getGSTTrue($model->id) == false,
    				'attribute' =>'igst_amt',
    				'value' => function ($data, $key, $index) { return $data->igst_amt; },
    					
    		],
    		
    		'other_charge',
    		'amount'
    		
        
    ],
]); ?>

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
var url = "<?php echo Ui::to('purchaseBill/printBarcode') ?>";
jQuery.ajax({
    'type': 'POST',
    'url': '<?php echo Ui::to('purchaseBill/print') ?>',
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