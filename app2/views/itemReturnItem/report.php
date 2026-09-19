<?php
/**
 * Ported from protected/views/itemReturnItem/report.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Tax;
use app\models\Vendor;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\CheckboxColumn;
use app\widgets\GridView;
use app\widgets\Menu;
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
	$.fn.yiiGridView.update('mrs-detail-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<style>
.btn-info.export-btn {
	background-color: #00c0ef;
	border-color: #00acd6;
	margin-left: 16px;
	margin-top: 10px;
}
</style>
<?php 	if(empty($model->tally_start_date))
		{
			
				Yii::$app->session['tally_start_date'] ='';
		}
		if(empty($model->tally_end_date))
		{
				
			Yii::$app->session['tally_end_date'] ='';
		}
		?>
<section class="content-header">
  <h1> <?php echo 'Debit Return Report' ;?> </h1>
  
</section>
<?php    echo Menu::widget([
       'type' => 'pills',
       'stacked' => false,
       'items' => [
        		['label' => 'Export',
        				'url' => ['itemReturnItem/report' ,'exportCSV'=>'1',
        						
        		
        		],
       		
       		],
       ],
   ]);  ?>

<section class="content">

  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
        <div class="box-header"><h3 class="box-title">Debit Return Report</h3></div>
        <div class="box-body">
          
            <?php $form = ActiveForm::begin([
	'id' => 'stock-adjust-log-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>

<div class="col-md-6">

<?php echo $form->datepickerRow($model, 'tally_start_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
						'options'=>['dateFormat'=>'yy-mm-dd']])

; ?>
</div>
<div class="col-md-6">
<?php echo $form->datepickerRow($model, 'tally_end_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
								'options'=>['dateFormat'=>'yy-mm-dd']])

; ?>
</div>

	<div class="form-actions pull-left">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		]); ?>
	</div>

<?php ActiveForm::end(); ?>
</br>
          <div class="row">
            <div class="col-md-12">


<div class="clearfix"></div>
 <div class="table-responsive customgridwidth">
               

 
<?php 
    echo GridView::widget([
    'id'=>'purchase-order-detail-grid',
    'dataProvider'=>$model->reportsearch(),
    'pager'=>true,
    'filter'=>$model,
    		'afterAjaxUpdate'=>"function(){
                                                       $.datepicker.setDefaults($.datepicker.regional['en']);
                                                        $('#Projects_projStart').datepicker({'dateFormat': 'yy-mm-dd'});
    		
                                                }",
    'columns'=>[
        // array(
            // 'id'=>'mrsId',
            // 'class' => CheckboxColumn::class,
            // 'selectableRows' => '50',   
        // ),
     //   'purchase_bill_id',
    	//	'tax_id',
		'id',
    		[
    				'header'=>'Grn Date',
    				'value' => function ($data, $key, $index) { return $data->getGRNDateData(); },
					
    				],
    		[
    				'header'=>'Bill Date',
    				//'attribute' =>'start_date',
    				'value' => function ($data, $key, $index) { return $data->getBillDateData(); },
    				
    					
    		],
			
			/*array(
    				'header'=>'Date',
    				'attribute' =>'start_date',
    				'value' => function ($data, $key, $index) { return date("d/m/Y",strtotime($data->create_time)); },
    				
    					
    		),*/
			
    		[
    				'header'=>'Vendor',
    				'attribute' =>'vendor_id',
    				'value' => function ($data, $key, $index) { return $data->getVendorName(); },
    				'filter'=>Gx::listData(Vendor::class),
    					
    		],
    		[
    				'header'=>'HSN Code',
    				'attribute' =>'tax_id',
    				'value' => function ($data, $key, $index) { return isset($data->tax)?$data->tax->hrn_code:""; },
    				'filter'=>Tax::getHsnCodeList(),
    					
    		],
			[
    				'header'=>'Bill No.',
    				//'attribute' =>'purchase_bill_id',
    				'value' => function ($data, $key, $index) { return isset ( $data->itemReturn ) ? $data->itemReturn->bill_no: ""; },
    					
    		],
    		
    		[
    				'header'=>'GST NO',
    				'value' => function ($data, $key, $index) { return $data->getVendorTAXNO(); },
    					
    		],
			[
    				'header'=>'GST (%age)',
    				'value' => function ($data, $key, $index) { return $data->getTotalGstPer(); }
    					
    		],
    		[
    				'header'=>'CGST (%age)',
    				'value' => function ($data, $key, $index) { return $data->getTaxPercentage("cgst_per"); }
    					
    		],
    		[
    				'header'=>'SGST (%age)',
    				'value' => function ($data, $key, $index) { return $data->getTaxPercentage("sgst_per"); },
    					
    		],
    		[
    				'header'=>'CESS (%age)',
    				'value' => function ($data, $key, $index) { return $data->getTaxPercentage("cess_per"); },
    					
    		],
    		[
    				'header'=>'IGST (%age)',
    				'value' => function ($data, $key, $index) { return $data->igst_per; },
    					
    		],
			[
    				'header'=>'Credit Note No.',
    				//'attribute' =>'purchase_bill_id',
    				'value' => function ($data, $key, $index) { return isset ( $data->itemReturn ) ? $data->itemReturn->credit_note_no: ""; },
    					
    		],
			[
    				'header'=>'Credit Note Date',
    				//'attribute' =>'purchase_bill_id',
    				'value' => function ($data, $key, $index) { return isset ( $data->itemReturn ) ? $data->itemReturn->credit_note_date: ""; },
    					
    		],
			
			[
    				'header'=>'Debit Note Date',
    				'value' => function ($data, $key, $index) { return isset ( $data->itemReturn->grn_save_date ) ? date("Y-m-d",strtotime( $data->itemReturn->grn_save_date )) : ""; },
    					
    		],
			[
    				'header'=>'Supplier Invoice No.',
    				//'attribute' =>'purchase_bill_id',
    				'value' => function ($data, $key, $index) { return isset ( $data->itemReturn ) ? $data->itemReturn->invoice_no: ""; },
    					
    		],
			
    		[
    				'header'=>'Net Amount',
    			//	'attribute' =>'total_amt',
    				'value' => function ($data, $key, $index) { return isset ( $data->itemReturn ) ? $data->itemReturn->total_amt: ""; },
    					
    		],
    		[
    				'header'=>'Basic Value',
    			//	'attribute' =>'gross_amt',
    				'value' => function ($data, $key, $index) { return ($data->price * $data->qty) - $data->discount_amt; },
    					
    		],
    		[
    				'header'=>'Discount',
    				// 'value' => function ($data, $key, $index) { return isset ( $data->itemReturn ) ? $data->itemReturn->discount_amt: ""; },
    				'value' => function ($data, $key, $index) { return $data->discount_amt; },
    					
    		],
    		[
    				'header'=>'GST Amount',
    				'value' => function ($data, $key, $index) { return $data->cgst_amt + $data->sgst_amt + $data->cess_amt + $data->igst_amt; },
    					
    		],
    		[
    				'header'=>'CGST Amount',
    				'value' => function ($data, $key, $index) { return $data->cgst_amt; },
    					
    		],
    		[
    				'header'=>'SGST Amount',
    				'value' => function ($data, $key, $index) { return $data->sgst_amt; },
    					
    		],
    		[
    				'header'=>'CESS Amount',
    				'value' => function ($data, $key, $index) { return $data->cess_amt; },
    					
    		],
    		[
    				'header'=>'IGST Amount',
    				'value' => function ($data, $key, $index) { return $data->igst_amt; },
    					
    		],
    		[
    				'header'=>'GRN NUMBER',
    				//'attribute' =>'purchase_bill_id',
					
					'value' => function ($data, $key, $index) { return $data->getCustomGRNNumber(); },
					
    				//'value'=>"GR-" .'isset ( $data->itemReturn ) ?  $data->itemReturn->grn_no: ""',
    					
    		]
    		
        
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

<script>
$('#PurchaseOrderDetail_purchase_order_id').change(function(){
	
	var poid = $('#PurchaseOrderDetail_purchase_order_id').val();
	var url = '<?php echo Ui::to('purchaseOrderDetail/admin')?>/id/'+vendor_id+'/poid/'+poid;
	window.location.href = url;
});
$(function(){
    $('.modal-body').slimScroll({
        height: '350px'
    });
});
</script>