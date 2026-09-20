<?php
/**
 * Ported from protected/views/b2bpurchaseBillDetail/report.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Tax;
use app\models\Vendor;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\CJuiDatePicker;
use app\widgets\CheckboxColumn;
use app\widgets\GridView;
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
  <h1> <?php echo 'Tally Report' ;?> </h1>
  
</section>

<ul class="nav nav-pills" id="yw2">
	<li><button type="button" class="btn btn-info export-btn"
			data-toggle="modal" data-target="#myModal">Export</button></li>
</ul>

<!-- Modal -->
<div id="myModal" class="modal fade" role="dialog">
	<div class="modal-dialog">

		<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Select Columns</h4>
			</div>
			<div class="modal-body">
     <?php
					
					$form = ActiveForm::begin([
							'id' => 'customer-export-form',
							'type' => 'horizontal',
							'action' => Ui::to( 'purchaseBillDetail/report?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => [
									'enctype' => 'multipart/form-data' 
							] 
					] );
					?>
<?php

					$cols = [
							'date' => 'Date',
							'vendor' => 'Vendor',
							'hsn_code' => 'HSN Code',
							'bill_no' => 'Bill No',
							'tax_no' => 'GSTNO',
							'gst_per' => 'GST%',
							'cgst_per' => 'CGST%',
							'sgst_per' => 'SGST%',
							'igst_per' => 'IGST%',
							'cess_per' => 'CESS%',
							'net_amount' => 'Net Amount',
							'basic_value' => 'Basic Value',
							'discount' => 'Discount',
							'gst_amt' => 'GST',
							'cgst_amt' => 'CGST',
							'sgst_amt' => 'SGST',
							'igst_amt' => 'IGST',
							'cess_amt' => 'CESS',
							'grn_no' => 'GRN NUMBER',
							'scheme' => 'SCHEME AND DISCOUNT',
					];
					?>
<div class="form-group ">
					<label for="ItemStock_item_id"
						class="control-label col-md-3 required"> </label>
					<div class="col-md-9">
			<?php echo $form->checkboxListRow($model,'columns',$cols); ?>
		</div>
				</div>

				<div class="form-actions">
		<?php
		
		echo Button::widget([
				'buttonType' => 'submit',
				'type' => 'primary',
				'label' => 'Export',
				'htmlOptions' => [
						'id' => 'form-export' 
				] 
		] );
		?>
	</div>
<?php ActiveForm::end(); ?>
      </div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"
					id="close_modal">Close</button>
			</div>
		</div>

	</div>
</div>
<section class="content">

  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
        <div class="box-header"><h3 class="box-title">Tally</h3></div>
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
    				'value' => function ($data, $key, $index) { return date("Y-m-d",strtotime($data->create_time)); },
    				],
    		[
    				'header'=>'Date',
    				'attribute' =>'start_date',
    				'value' => function ($data, $key, $index) { return isset($data->purchaseBill)?$data->purchaseBill->end_date:""; },
    				'filter' => CJuiDatePicker::widget([
    								'model' => $model,
    								'attribute' => 'start_date',
    								'language' => 'en',
    								'htmlOptions' => [
    										'id' => 'Projects_projStart',
    										'dateFormat' => 'yy-mm-dd',
    								],
    								'options' => [  // (#3)
    										'showOn' => 'focus',
    										'dateFormat' => 'yy-mm-dd',
    										'showOtherMonths' => true,
    										'selectOtherMonths' => false,
    										'changeMonth' => false,
    										'changeYear' => false,
    								]
    						],
    						true),
    					
    		],
    		[
    				'header'=>'Vendor',
    				'attribute' =>'vendor_id',
    				'value' => function ($data, $key, $index) { return Gx::str($data->purchaseBill->vendor); },
    				'filter'=>Gx::listData(Vendor::class),
    					
    		],
    		[
    				'header'=>'HSN Code',
    				'attribute' =>'tax_id',
    				'value' => function ($data, $key, $index) { return isset($data->tax)?$data->tax->hrn_code:""; },
    				'filter'=>Tax::getHsnCodeList(),
    					
    		],
    		[
    				'header'=>'GST NO',
    				'value' => function ($data, $key, $index) { return $data->getVendorTAXNO(); },
    					
    		],
    		[
    				'header'=>'CGST (%age)',
    				'value' => function ($data, $key, $index) { return $data->getTaxPercentage("cgst_per"); },
    					
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
    				'header'=>'Net Amount',
    				'attribute' =>'amount',
    				'value' => function ($data, $key, $index) { return isset ( $data->purchaseBill ) ? $data->purchaseBill->net_bill_amount: ""; },
    					
    		],
    		[
    				'header'=>'Basic Value',
    				'attribute' =>'amount',
    				'value' => function ($data, $key, $index) { return $data->getBasicAmount(); },
    					
    		],
    		[
    				'header'=>'Discount',
    				'value' => function ($data, $key, $index) { return $data->getMainDiscount(); },
    					
    		],
    		/* array(
    				'header'=>'Scheme Discount',
    				'value' => function ($data, $key, $index) { return $data->discount_amt1; },
    					
    		), */
    		[
    				'header'=>'CGST Amount',
    				'value' => function ($data, $key, $index) { return $data->getCgstAmount(); },
    					
    		],
    		[
    				'header'=>'SGST Amount',
    				'value' => function ($data, $key, $index) { return $data->getSgstAmount(); },
    					
    		],
    		[
    				'header'=>'CESS Amount',
    				'value' => function ($data, $key, $index) { return $data->getCessAmount(); },
    					
    		],
    		[
    				'header'=>'IGST Amount',
    				'value' => function ($data, $key, $index) { return $data->getIgstAmount(); },
    					
    		],
    		[
    				'header'=>'GRN NUMBER',
    				//'attribute' =>'purchase_bill_id',
    				'value' => function ($data, $key, $index) { return isset($data->purchaseBill)?$data->purchaseBill->grn_refrence_no:""; },
    					
    		],
    		[
    				'header'=>'SCHEME AND DISCOUNT',
    				'value' => function ($data, $key, $index) { return $data->getSchemeDiscount(); },
    					
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