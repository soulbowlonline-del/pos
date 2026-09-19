<?php
/**
 * Ported from protected/views/itemReturnItem/admin.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Item;
use app\models\ItemDetail;
use app\models\Outlet;
use app\models\Tax;
use app\models\UserRole;
use app\models\Vendor;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\ButtonGroup;
use app\widgets\CheckboxColumn;
use app\widgets\GridView;
use app\widgets\TbTypeAhead;
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
	$.fn.yiiGridView.update('mrs-detail-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<?php 
$gst = true;
$start_date = null;
$end_date = null;
$user = Yii::$app->user->model;
if($vendor_id !='' && $outlet_id != ''){
$vendor = Vendor::findOne($vendor_id);
if($vendor){
	$outlet = Outlet::findOne($outlet_id);
	if($outlet){
		if($vendor->state_id != $outlet->state_id){
			$gst = false;
		}
	}
}
}?>
<section class="content-header">
  <h1> <?php echo 'Manage' ;?> <?php echo Html::encode($model->label(2))?> </h1>
  <a href="<?php echo Ui::to('itemReturn/list');?>"
		class="btn btn-info export-btn" target="_blank">Merge</a>
	<a href="<?php echo Ui::to('itemReturnItem/printPdf',array('vendor_id'=>$vendor_id, 'outlet_id' => $outlet_id));?>" class="btn btn-primary export-btn" target="_blank">Print Pdf</a>
	<?php echo ButtonGroup::widget([
	'buttons'=>$this->context->menu,
	'type'=>'success',
	'htmlOptions'=>['class'=> 'pull-right'],
]);
?>
</section>
<section class="content">

  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
        <div class="box-header"><h3 class="box-title">ItemReturnItems</h3></div>
        <div class="box-body">
          <div class="row">
          <!--  form code start here -->

            <div class="col-md-12">

<?php 	$form = ActiveForm::begin([
	//'action' => Ui::to($this->context->route),
	'method' => 'post',
	'id' => 'item-return-item-form',
	'type'=>'horizontal',		
]); 
?>

		<?php echo $form->dropDownListRow($model, 'vendor_id', Gx::listData(Vendor::find()->where(['status'=>Vendor::STATUS_ACTIVE])->all()), ['prompt' => 'Select Vendor']); ?>
		<?php echo $form->dropDownListRow($model, 'outlet_id', Gx::listData(Outlet::find()->where(['status'=>Outlet::STATUS_ACTIVE])->all()), ['prompt' => 'Select Outlet']); ?>


		<div class="form-actions">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		]); ?>
	
	</div>
<?php ActiveForm::end(); ?>



</div>


<?php

$form = ActiveForm::begin([
		'id' => 'mrs-detail-add-form',
		'type' => 'horizontal',
		// 'action'=> Ui::to('ItemReturnItem/create'),
		'enableAjaxValidation' => true,
		'htmlOptions' => [
				'enctype' => 'multipart/form-data' 
		] 
] );
?>
	<div class="add-item">
								<div class="col-md-10 col-sm-12 col-xs-12 margin10">

									<div class="row">


													<div class="col-md-2 col-xs-12 padding2px">
											<!-- <label class="control-label" for="">Bar Code</label>
											<div id="item_detail_data"></div> -->
											<input type="hidden" name="ItemReturnItem[item_detail_id]" id="ItemReturnItem_item_detaill_id">
											<label class="control-label" for="">Bar Code</label>
                  <?php echo $form->textField($model,'bar_code',['class'=>'form-control','id'=>'ItemReturnItem_bar_code']); ?>
										</div>
										<div class="col-md-3 col-xs-12 padding2px">
										
<label class="control-label" for="">Item</label>

<input type="hidden" name="ItemReturnItem[item_id]" id="item_hsn_code">


   <?php 
   
   echo TbTypeAhead::widget([
              'model' => $model,
              'attribute' => 'item_val_id',
              'enableHogan' => true,
              'htmlOptions'=>['class'=>'form-control'],
              'options' => [
                           [
                                         'limit' => 100,
                                         'attribute' => 'item_val_id',
                                         'valueKey' => 'name',
                                         'remote' => [
                                                       'url' => Ui::to('/item/getAllItems') .'?vendor_id='.$vendor_id.'&&term=%QUERY',
                                         ],
                           		'template' => '<p>{{name}}     <strong> [ {{mrp}} ] </strong></p>',
                                  //     'template' => '<p>{{name}}<strong> [ {{username}} ] </strong> - {{user_id}}</p>',
                                         'engine' => new \yii\web\JsExpression('Hogan'),
                           ]
              ],
              
               'events' => [
                           'selected' => new \yii\web\JsExpression("function(obj, datum, name) {
                    var    uid = datum.item_id;
                           $('#item_hsn_code').val(uid);
                           		checkBarcodes();
          
         }")
              ], 
   ]);
   
   
   ?>
   <?php echo $form->error($model,'item_id');?>
  </div>
										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Req Qty</label>
                  <?php echo $form->textField($model,'qty',['class'=>'form-control']); ?>
                </div>



										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Mrp</label>
                  <?php  echo $form->textField($model,'mrp',  ['class'=>'form-control']); ?>
                </div>


										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Price</label>
                  <?php  echo $form->textField($model,'price',  ['class'=>'form-control','id'=>'ItemReturnItem_price_field']); ?>
                </div>

										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Sale Rate</label>
                 <?php  echo $form->textField($model,'sale_rate',  ['class'=>'form-control']); ?>
                </div>
                	
                	<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Disc %</label>
                  <?php  echo $form->textField($model,'discount',  ['class'=>'form-control']); ?>
                </div>
	<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Disc Amt</label>
                 <?php  echo $form->textField($model,'discount_amt',  ['class'=>'form-control']); ?>
                </div>

									</div>

									<div class="row">

									

									
<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for=""> Disc1 %</label>
                  <?php  echo $form->textField($model,'discount1',  ['class'=>'form-control']); ?>
                </div>

										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for=""> Disc1 Amt</label>
                 <?php  echo $form->textField($model,'discount_amt1',  ['class'=>'form-control']); ?>
                </div>
										<div class="col-md-2 col-xs-12 padding2px">
											<label class="control-label" for="">Tax</label>
											<div id="item_tax_data"><?php  echo $form->textField($model,'tax_id',  ['class'=>'form-control']); ?></div>
                 <?php echo $form->error($model, 'tax_id');?>
                 
                </div>

   <?php if($gst == true){?>
										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">CGTS %</label> <input
												type="text" name="ItemReturnItem[cgst_per]" id="CGST_per"
												class="form-control" placeholder="%">
										</div>

										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">CGTS Amt</label> <input
												type="text" name="ItemReturnItem[cgst_amt]" id="CGST_amt"
												class="form-control" placeholder="Amount">
										</div>


										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">SGTS %</label> <input
												type="text" name="ItemReturnItem[sgst_per]" id="SGST_per"
												class="form-control" placeholder="%">
										</div>

										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">SGTS Amt</label> <input
												type="text" name="ItemReturnItem[sgst_amt]" id="SGST_amt"
												class="form-control" placeholder="Amount">
										</div>

										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">CESS %</label> <input
												type="text" name="ItemReturnItem[cess_per]" id="CESS_per"
												class="form-control" placeholder="%">
										</div>

										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">CESS Amt</label> <input
												type="text" name="ItemReturnItem[cess_amt]" id="CESS_amt"
												class="form-control" placeholder="Amount">
										</div>
<?php }else{?>
									<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">CESS %</label> <input
												type="text" name="ItemReturnItem[cess_per]" id="CESS_per"
												class="form-control" placeholder="%">
										</div>

										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">CESS Amt</label> <input
												type="text" name="ItemReturnItem[cess_amt]" id="CESS_amt"
												class="form-control" placeholder="Amount">
										</div>
										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">IGST %</label> <input
												type="text" name="ItemReturnItem[igst_per]" id="IGST_per"
												class="form-control" placeholder="%">
										</div>

										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">IGST Amt</label> <input
												type="text" name="ItemReturnItem[igst_amt]" id="IGST_amt"
												class="form-control" placeholder="Amount">
										</div>
<?php }?>
										
                <div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Margin</label>
                 <?php  echo $form->textField($model,'margin',  ['class'=>'form-control']); ?>
                </div>

										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Amount</label>
                  <?php  echo $form->textField($model,'total_amt',  ['class'=>'form-control']); ?>
                  <input type="hidden" name="ItemReturnItem[vendor_id]" value="<?php echo $vendor_id;?>" id="item_return_vendor_id">
                  <input type="hidden" name="ItemReturnItem[outlet_id]" value="<?php echo $outlet_id;?>" id="item_return_outlet_id">
                </div>



									</div>



								</div>

								<div class="col-md-2 col-sm-12 col-xs-12 add-bttn-box">
                
                <?php
																
																echo Button::widget([
																		'buttonType' => 'button',
																		'type' => 'primary',
																		'label' => 'Add Item' 
																]
																 );
																?>
	


                
                </div>

							</div>

<?php ActiveForm::end(); ?>

<?php 
//if($mrsid != null){
/* echo ButtonGroup::widget(array(
	'buttons'=>$this->context->menu,
	'type'=>'success',
	'htmlOptions'=>array('class'=> 'pull-right'),
)); 
} */
?>

<div class="clearfix"></div>
<hr />

							<div class="col-md-12 item-list-table">
							<?php $form = ActiveForm::begin([
    'enableAjaxValidation'=>true,
	'id' => 'mrs-qty',
]); ?>
<div class="row">
<div class="col-md-2 col-xs-12 padding2px">
											<label class="control-label" for="">Credit Note No.</label>
											<div id="credit_note_no"><input class="form-control" name="credit_note_no" id="ItemReturncredit_note_no" type="text" required="required"></div>
                 <span class="help-inline error" id="ItemReturncredit_note_noem_" style="display: none"></span>                 
                </div>
				
				<div class="col-md-2 col-xs-12 padding2px">
											<label class="control-label" for="">Supplier Invoice No.</label>
											<div id="invoice_no"><input class="form-control" name="invoice_no" id="ItemReturninvoice_no" type="text" required="required"></div>
                 <span class="help-inline error" id="ItemReturninvoice_noem_" style="display: none"></span>                 
                </div>
				<div class="col-md-2 col-xs-12 padding2px">
											<label class="control-label" for="">Grn No.</label>
											<div id="grn_no"><input class="form-control" name="grn_no" id="ItemReturngrn_no" type="text" required="required"></div>
                 <span class="help-inline error" id="ItemReturngrn_noem_" style="display: none"></span>                 
                </div>
				<div class="col-md-2 col-xs-12 padding2px">
											<label class="control-label" for="">Bill No.</label>
											<div id="bill_no"><input class="form-control" name="bill_no" id="ItemReturnbill_no" type="text" required="required"></div>
                 <span class="help-inline error" id="ItemReturnbill_noem_" style="display: none"></span>                 
                </div>
				<div class="col-md-2col-xs-12 padding2px">
				<?php echo $form->datepickerRow($model, 'credit_note_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?></div>
				</div>
<div class="table-responsive customsmallgridwidth">
				  <div class="">


 
<?php 
    echo GridView::widget([
    'id'=>'menu-grid',
    'dataProvider'=>$model->search(),
    'filter'=>$model,
    		'itemsCssClass'=>'table table-bordered table-striped dataTable',
    'columns'=>[
        // array(
            // 'id'=>'mrsId',
            // 'class' => CheckboxColumn::class,
            // 'selectableRows' => '50',   
        // ),
        [
			'attribute' =>'item_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->item); },
			'filter'=>Gx::listData(Item::class),
	],
			[
					'attribute' =>'item_detail_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->itemDetail); },
					'filter'=>Gx::listData(ItemDetail::class),
			],
		/*	 array(
					'attribute' =>'outlet_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->outlet); },
					'filter'=>Gx::listData(Outlet::class),
			),
			array(
					'header'=>'vendor',
					'value' => function ($data, $key, $index) { return Gx::str($data->mrs->vendor); },
					
			), */
    		[
    				'header'=>'Total Remain Qty',
    				'value' => function ($data, $key, $index) { return isset($data->item)?$data->item->getTotalRemainingQuantity():""; },
    				'htmlOptions'=>['class'=>'item_qty_field'],
    		
    		],
    		/* array(
    				'attribute' =>'qty',
    		
    				'value' => function ($data, $key, $index) { return $data->qty; },
    				'filter'=>false,
    				//	'filterInputOptions' =>array('style'=>'width: 20px'),
    		), */
			/* array(
				'attribute' =>'req_qty',
				
				'value' => function ($data, $key, $index) { return $data->req_qty; },
				'filter'=>false,
				//	'filterInputOptions' =>array('style'=>'width: 20px'),
			), */
    		[
    					
    				'header'=>'qty',
    				'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'qty', ["id"=>"req_input$data->id","class"=>"req_input"]); },
    				'format' => 'raw',
    				'htmlOptions'=>['class'=>'req_qty'],
    					
    		],
			[
    				'header' => 'Sent In Api',
    				'value' => function ($data, $key, $index) { return Html::dropDownList("ItemReturnItem[type_id]",$data->type_id,$data->getAPIOptions(),["id"=>"api_type$data->id","class"=>"api_type"]); },
    				'format' => 'raw',
    				'htmlOptions' => [
    						'class' => 'api_type'
    				]
    		],
    		[
    					
    				'header'=>'Mrp',
    				'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'mrp', ["id"=>"mrp_input$data->id","class"=>"mrp_input"]); },
    				'format' => 'raw',
    				'htmlOptions'=>['class'=>'mrp'],
    					
    		],
    		[
    					
    				'header'=>'Sale Rate',
    				'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'sale_rate', ["id"=>"sale_rate$data->id","class"=>"sale_rate_input","disabled"=>$data->getCompanyBarcode($data->item_detail_id)]); },
    				'format' => 'raw',
    				'htmlOptions'=>['class'=>'sale_rate'],
    					
    		],
    		[
    					
    				'header'=>'Price',
    				'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'price', ["id"=>"price_input$data->id","class"=>"price_inpput"]); },
    				'format' => 'raw',
    				'htmlOptions'=>['class'=>'price'],
    					
    		],
    		
    		
    		
    		[
    					
    				'header'=>'Dis%',
    				'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'discount',["id"=>"discount_input$data->id","class"=>"discount_input"]); },
    				'format' => 'raw',
    				'htmlOptions'=>['class'=>'discount'],
    					
    		],
    		[
    					
    				'header'=>'Disc Amt',
    				'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'discount_amt',["id"=>"discount_amt_input$data->id","class"=>"discount_amt_input"]); },
    				'format' => 'raw',
    				'htmlOptions'=>['class'=>'discount_amt'],
    					
    		],
    		[
    					
    				'header'=>'Other Disc%',
    				'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'discount1',["id"=>"discount1_input$data->id","class"=>"discount1_input"]); },
    				'format' => 'raw',
    				'htmlOptions'=>['class'=>'discount1'],
    					
    		],
    		[
    					
    				'header'=>'Other Disc Amt',
    				'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'discount_amt1',["id"=>"discount_amt1_input$data->id","class"=>"discount_amt1_input"]); },
    				'format' => 'raw',
    				'htmlOptions'=>['class'=>'discount_amt1'],
    					
    		],
    		[
    				'header'=>'Tax',
    				// 'value' => function ($data, $key, $index) { return Gx::str($data->tax); },
						'value' => function ($data, $key, $index) { return Html::dropDownList("PurchaseBillDetail[tax]",$data->tax_id,$data->getAllTaxOptions($data->tax_id, $data->vendor_id, $data->outlet_id),["id"=>"select_tax$data->id","class"=>"select_tax"]); },
						'format' => 'raw',
    				'htmlOptions'=>['class'=>'select_tax_val'],
    		],
    		 [
    				'visible'=>$model->getGSTTrue($vendor_id,$outlet_id) == true,
    				'header'=>'CGST (%age)',
    				'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'cgst_per',["id"=>"CGST_per_input$data->id","class"=>"CGST_per_input","ReadOnly"=>"ReadOnly"]); },
    				'format' => 'raw',
    				'htmlOptions'=>['class'=>'CGST_per_input'],
    					
    		],
    		[
    				'visible'=>$model->getGSTTrue($vendor_id,$outlet_id) == true,
    				'header'=>'SGST (%age)',
    				'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'sgst_per',["id"=>"SGST_per_input$data->id","class"=>"SGST_per_input","ReadOnly"=>"ReadOnly"]); },
    				'format' => 'raw',
    				'htmlOptions'=>['class'=>'SGST_per_input'],
    					
    		],
    		[
    				// 'visible'=>$model->getGSTTrue($vendor_id,$outlet_id) == true,
    				'header'=>'CESS (%age)',
    				'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'cess_per',["id"=>"CESS_per_input$data->id","class"=>"CESS_per_input","ReadOnly"=>"ReadOnly"]); },
    				'format' => 'raw',
    				'htmlOptions'=>['class'=>'CESS_per_input'],
    					
    		],
    		[
    				'visible'=>$model->getGSTTrue($vendor_id,$outlet_id) == true,
    				'header'=>'CGST Amt',
    				'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'cgst_amt',["id"=>"CGST_amt_input$data->id","class"=>"CGST_amount_input","ReadOnly"=>"ReadOnly"]); },
    				'format' => 'raw',
    				'htmlOptions'=>['class'=>'CGST_amt_input'],
    					
    		],
    		[
    				'visible'=>$model->getGSTTrue($vendor_id,$outlet_id) == true,
    				'header'=>'SGST Amt',
    				'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'sgst_amt',["id"=>"SGST_amt_input$data->id","class"=>"SGST_amount_input","ReadOnly"=>"ReadOnly"]); },
    				'format' => 'raw',
    				'htmlOptions'=>['class'=>'SGST_amt_input'],
    					
    		],
    		[
    				// 'visible'=>$model->getGSTTrue($vendor_id,$outlet_id) == true,
    				'header'=>'CESS Amt',
    				'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'cess_amt',["id"=>"CESS_amt_input$data->id","class"=>"CESS_amount_input","ReadOnly"=>"ReadOnly"]); },
    				'format' => 'raw',
    				'htmlOptions'=>['class'=>'CESS_amt_input'],
    					
    		],
    		[
    				'visible'=>$model->getGSTTrue($vendor_id,$outlet_id) == false,
    				'header'=>'IGST (%age)',
    				'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'igst_per',["id"=>"IGST_per_input$data->id","class"=>"IGST_per_input","ReadOnly"=>"ReadOnly"]); },
    				'format' => 'raw',
    				'htmlOptions'=>['class'=>'IGST_per_input'],
    					
    		],
    		[
    				'visible'=>$model->getGSTTrue($vendor_id,$outlet_id) == false,
    				'header'=>'IGST Amt',
    				'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'igst_amt',["id"=>"IGST_amt_input$data->id","class"=>"IGST_amount_input","ReadOnly"=>"ReadOnly"]); },
    				'format' => 'raw',
    				'htmlOptions'=>['class'=>'IGST_amt_input'],
    					
    		],
    		/* array(
    					
    				'header'=>'Other Charge',
    				'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'other_charge',["id"=>"other_charge_input$data->id","class"=>"other_charge_input"]); },
    				'format' => 'raw',
    				'htmlOptions'=>array('class'=>'other_charge'),
    					
    		), */
    		[
    					
    				'header'=>'Amt',
    				'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'total_amt',["id"=>"total_amount_input$data->id","class"=>"total_amount_input"]); },
    				'format' => 'raw',
    				'htmlOptions'=>['class'=>'amount'],
    					
    		],
    		
        
    ],
]); ?>


<script>
// function reloadGrid(data) {
    // $.fn.yiiGridView.update('menu-grid');
// }
$(document).ready(function(){
	//$('#mrs-qty input').addClass('approved_qty');
	//var mrsid = ("#ItemReturnItem_mrs_id").val();
	$('#ItemReturngrn_no').change(function(){
		var grn_no = $('#ItemReturngrn_no').val();
		$.ajax({
	       url: '<?php echo Ui::to('ItemReturnItem/ajaxbillno'); ?>',
	       type: 'post',

	       data: {
	                 grn_no: grn_no,
	                
	                
	             },
	       success: function (data) {
	    	$('#ItemReturnbill_no').val(data);
	       }
		 
	  }); 
	});
	
	$("#approve").click(function (event) 
	{
		event.preventDefault();
		var formData = {};
		var mrpData = {};
		var priceData = {};
		var salerateData = {};
		var discountData = {};
		var discount_amtData = {};
		var discountData1 = {};
		var discount_amtData1 = {};
		var vatData = {};
		var other_chargeData = {};
		var amountData = {};
		var cgstData = {};
		var sgstData = {};
		var cessData = {};
		var igstData = {};
		var cgstamtData = {};
		var sgstamtData = {};
		var cessamtData = {};
		var igstamtData = {};
		var taxselectData = {};
		var apitype = {};
		var status = 1;
		
		var credit_note_no = $('#ItemReturncredit_note_no').val();
			var invoice_no = $('#ItemReturninvoice_no').val();
		var bill_no = $('#ItemReturnbill_no').val();
			var grn_no = $('#ItemReturngrn_no').val();
			var credit_note_date = $('#ItemReturnItem_credit_note_date').val();
		if(credit_note_no != '' && invoice_no != '' && bill_no != '' && grn_no != '' && credit_note_date != ''){
			$.ajax({
 		       url: '<?php echo Ui::to('ItemReturnItem/ajaxCreditBillNo'); ?>',
 		       type: 'post',
 		        data: {
 							credit_note_no: credit_note_no,
 	          },
 		       success: function (data) {
 			if(data == 'Success'){

				var gross_amt = $('#gross_amount').val();
				var total_discount =   $('#total_discount').val();
				var tax_amount =   $('#tax_amount').val();
				var bill_amount =   $('#bill_amount').val();

				$("td.req_qty input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var approved_qty = str.split('input');
				
					formData[approved_qty['1']] = val;
			}); 
				$("td.mrp input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var mrp = str.split('input');
					mrpData[mrp['1']] = val;
			}); 
			$("td.api_type select").each(function(key,value){
						var str = $(this).attr('id');
						console.log('str'+str);
						var arr = str.split('type');
						var vat = arr['1'];
						var val = $('#'+str).val(); 
						apitype[vat] = val;
						console.log('apitype'+apitype);
					});

				$("td.price input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var price = arr['1'];
					priceData[price] = val;
			}); 
				$("td.sale_rate input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var salerate = str.split('rate');
						salerateData[salerate['1']] = val;
			}); 
				$("td.discount input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var discount = arr['1'];
				discountData[discount] = val;
			}); 
				$("td.discount_amt input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var discount_amt = arr['1'];
				
					discount_amtData[discount_amt] = val;
				}); 
				$("td.discount1 input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var discount = arr['1'];
					discountData1[discount] = val;
				}); 
					$("td.discount_amt1 input:text").each(function(key,value){
						var val = $(this).val(); 
						var str = $(this).attr('id');
						var arr = str.split('input');
						var discount_amt = arr['1'];
					
						discount_amtData1[discount_amt] = val;
					}); 
				$("td.CGST_per_input input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var vat = arr['1'];
					
					cgstData[vat] = val;
				}); 
				$("td.SGST_per_input input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var vat = arr['1'];
					
					sgstData[vat] = val;
				});
				$("td.CESS_per_input input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var vat = arr['1'];
					
					cessData[vat] = val;
				});
				$("td.CGST_amt_input input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var vat = arr['1'];
					
					cgstamtData[vat] = val;
				});
				$("td.SGST_amt_input input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var vat = arr['1'];
					
					sgstamtData[vat] = val;
				});
				$("td.CESS_amt_input input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var vat = arr['1'];
					
					cessamtData[vat] = val;
				});
				$("td.other_charge input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var other_charge = arr['1'];
					other_chargeData[other_charge] = val;
				}); 
				$("td.amount input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var amount = arr['1'];
					
					amountData[amount] = val;
				}); 
				
				$("td.IGST_per_input input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var vat = arr['1'];
					
					igstData[vat] = val;
				});
				$("td.IGST_amt_input input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var vat = arr['1'];
					
					igstamtData[vat] = val;
				});

				$("td.select_tax_val select").each(function(key,value){
				var str = $(this).attr('id');
				console.log('str'+str);
				var arr = str.split('tax');
				var vat = arr['1'];
				var val = $('#'+str).val(); 
				taxselectData[vat] = val;
			});
				
				$.ajax({
							url: '<?php echo Ui::to('ItemReturnItem/ajaxupdate'); ?>',
							type: 'post',

							data: {
												qty: formData,
												mrp: mrpData,
												salerate: salerateData,
												discount: discountData,
												discount_amt: discount_amtData,
												discount1: discountData1,
												discount_amt1: discount_amtData1,
												cgstData: cgstData,
												sgstData: sgstData,
												cessData: cessData,
												cgstamtData: cgstamtData,
												sgstamtData: sgstamtData,
												cessamtData: cessamtData,
												igstData: igstData,
												igstamtData: igstamtData,
												taxselectData: taxselectData,
												price: priceData,
												vat: vatData,
												other_charge: other_chargeData,
												amount: amountData,
												status: status,
												gross_amt: gross_amt,
												total_discount: total_discount,
												tax_amount: tax_amount,
												bill_amount: bill_amount,
												credit_note_no: credit_note_no,
												invoice_no: invoice_no,
												bill_no: bill_no,
												grn_no: grn_no,
								credit_note_date:credit_note_date,
								type_id:apitype
												
										},
							success: function (data) {
								location.reload();
							/*  $.fn.yiiGridView.update('menu-grid');
								$('#gross_amount').val('');
								$('#total_discount').val('');
								$('#tax_amount').val('');
								$('#bill_amount').val(''); */
							}
					
					}); 
		
			} else {
 				alert(credit_note_no + ' Credit Note No. is already exists.');
 			}
		}
		})
		}else{
			alert('Please fill Credit Note No., Bill No.,Invoice No. and Grn No.');
		}
	});

	$("#update").click(function (event) 
	{
		event.preventDefault();
		var formData = {};
		var mrpData = {};
		var priceData = {};
		var salerateData = {};
		var discountData = {};
		var discount_amtData = {};
		var discountData1 = {};
		var discount_amtData1 = {};
		var vatData = {};
		var other_chargeData = {};
		var amountData = {};
		var cgstData = {};
		var sgstData = {};
		var cessData = {};
		var igstData = {};
		var cgstamtData = {};
		var sgstamtData = {};
		var cessamtData = {};
		var igstamtData = {};
		var taxselectData = {};
		var apitype = {};
		
		var credit_note_no = $('#ItemReturncredit_note_no').val();
			var invoice_no = $('#ItemReturninvoice_no').val();
		var bill_no = $('#ItemReturnbill_no').val();
			var grn_no = $('#ItemReturngrn_no').val();
			var credit_note_date = $('#ItemReturnItem_credit_note_date').val();
		if(confirm("Do you really want to update?") == true){
				var gross_amt = $('#gross_amount').val();
				var total_discount =   $('#total_discount').val();
				var tax_amount =   $('#tax_amount').val();
				var bill_amount =   $('#bill_amount').val();

				$("td.req_qty input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var approved_qty = str.split('input');
				
					formData[approved_qty['1']] = val;
			}); 
				$("td.mrp input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var mrp = str.split('input');
					mrpData[mrp['1']] = val;
			}); 
			$("td.api_type select").each(function(key,value){
						var str = $(this).attr('id');
						console.log('str'+str);
						var arr = str.split('type');
						var vat = arr['1'];
						var val = $('#'+str).val(); 
						apitype[vat] = val;
						console.log('apitype'+apitype);
					});

				$("td.price input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var price = arr['1'];
					priceData[price] = val;
			}); 
				$("td.sale_rate input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var salerate = str.split('rate');
						salerateData[salerate['1']] = val;
			}); 
				$("td.discount input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var discount = arr['1'];
				discountData[discount] = val;
			}); 
				$("td.discount_amt input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var discount_amt = arr['1'];
				
					discount_amtData[discount_amt] = val;
				}); 
				$("td.discount1 input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var discount = arr['1'];
					discountData1[discount] = val;
				}); 
					$("td.discount_amt1 input:text").each(function(key,value){
						var val = $(this).val(); 
						var str = $(this).attr('id');
						var arr = str.split('input');
						var discount_amt = arr['1'];
					
						discount_amtData1[discount_amt] = val;
					}); 
				$("td.CGST_per_input input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var vat = arr['1'];
					
					cgstData[vat] = val;
				}); 
				$("td.SGST_per_input input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var vat = arr['1'];
					
					sgstData[vat] = val;
				});
				$("td.CESS_per_input input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var vat = arr['1'];
					
					cessData[vat] = val;
				});
				$("td.CGST_amt_input input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var vat = arr['1'];
					
					cgstamtData[vat] = val;
				});
				$("td.SGST_amt_input input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var vat = arr['1'];
					
					sgstamtData[vat] = val;
				});
				$("td.CESS_amt_input input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var vat = arr['1'];
					
					cessamtData[vat] = val;
				});
				$("td.other_charge input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var other_charge = arr['1'];
					other_chargeData[other_charge] = val;
				}); 
				$("td.amount input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var amount = arr['1'];
					
					amountData[amount] = val;
				}); 
				
				$("td.IGST_per_input input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var vat = arr['1'];
					
					igstData[vat] = val;
				});
				$("td.IGST_amt_input input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var vat = arr['1'];
					
					igstamtData[vat] = val;
				});

				$("td.select_tax_val select").each(function(key,value){
				var str = $(this).attr('id');
				console.log('str'+str);
				var arr = str.split('tax');
				var vat = arr['1'];
				var val = $('#'+str).val(); 
				taxselectData[vat] = val;
			});
				
				$.ajax({
							url: '<?php echo Ui::to('ItemReturnItem/ajaxupdateonly'); ?>',
							type: 'post',

							data: {
								qty: formData,
								mrp: mrpData,
								salerate: salerateData,
								discount: discountData,
								discount_amt: discount_amtData,
								discount1: discountData1,
								discount_amt1: discount_amtData1,
								cgstData: cgstData,
								sgstData: sgstData,
								cessData: cessData,
								cgstamtData: cgstamtData,
								sgstamtData: sgstamtData,
								cessamtData: cessamtData,
								igstData: igstData,
								igstamtData: igstamtData,
								taxselectData: taxselectData,
								price: priceData,
								vat: vatData,
								other_charge: other_chargeData,
								amount: amountData,
								status: status,
								gross_amt: gross_amt,
								total_discount: total_discount,
								tax_amount: tax_amount,
								bill_amount: bill_amount,
								credit_note_no: credit_note_no,
								invoice_no: invoice_no,
								bill_no: bill_no,
								grn_no: grn_no,
								credit_note_date:credit_note_date,
								type_id:apitype
							},
							success: function (data) {
								if(data == 'Success'){
								alert('Data is saved successfully');
								location.reload();
								}else{
									/*  if(data == 'Advance'){
									alert('Please do advance payment first'); 
									} */
									if(data == 'Failed'){
										alert('There is some error. Please try again'); 
										}
								}
							}
					
					}); 
		}
	});

})
</script>
<button class="btn btn-primary" id="approve" type="submit" name="approve">Save</button>
<button class="btn btn-info" id="update" type="submit" name="update">Update</button>

<?php //echo CHtml::ajaxSubmitButton('Activate',array('ItemReturnItem/ajaxupdate','act'=>'Insert'), array('success'=>'reloadGrid')); ?>


 </div>
 </div>
 <?php ActiveForm::end(); ?>
 <div class="clearfix"></div>
		<div class="row">
		<?php ?>
<div class="col-md-7  tax-table-responsive table-responsive customsmallgridwidth">
<?php $taxes = Tax::find()->where(['status'=>Tax::STATUS_ACTIVE])->all();?>
<?php if($taxes){?>
	<div id="taxes">
<table>
<tr>
<th>Basic Value</th>
<th>Title</th>
<th>HRN Code</th>
<th>CGST(%age)</th>
<th>CGST Amount</th>
<th>SGST(%age)</th>
<th>SGST Amount</th>
<th>CESS(%age)</th>
<th>CESS Amount</th>
<th>IGST(%age)</th>
<th>IGST Amount</th>
</tr>
<?php
	
	$total_cgst = '0.00';
	$total_sgst = '0.00';
	$total_cess = '0.00';
	$total_igst = '0.00';
	?>
<?php foreach($taxes as $tax){
	
	$total_cgst = $total_cgst + $model->getItemReturnCgstAmount ( $vendor_id, $outlet_id, $tax->id, 'cgst_amt' );
	$total_sgst = $total_sgst + $model->getItemReturnCgstAmount ( $vendor_id, $outlet_id, $tax->id, 'sgst_amt' );
	$total_cess = $total_cess + $model->getItemReturnCgstAmount ( $vendor_id, $outlet_id, $tax->id, 'cess_amt' );
	$total_igst = $total_igst + $model->getItemReturnCgstAmount ( $vendor_id, $outlet_id, $tax->id, 'igst_amt' );
	?>

<tr>
<td><?php echo $model->getItemReturnItemAmount($vendor_id, $outlet_id,$tax->id);?></td>
<td><?php echo $tax->title;?></td>
<td><?php echo $tax->hrn_code;?></td>
<td><?php echo $tax->tax_val1;?></td>
<td><?php echo $model->getItemReturnCgstAmount ( $vendor_id, $outlet_id, $tax->id, 'cgst_amt' ) ?></td>
<td><?php echo $tax->tax_val2;?></td>
<td><?php echo $model->getItemReturnCgstAmount ( $vendor_id, $outlet_id, $tax->id, 'sgst_amt' ) ?></td>
<td><?php echo $tax->tax_val3;?></td>
<td><?php echo $model->getItemReturnCgstAmount ( $vendor_id, $outlet_id, $tax->id, 'cess_amt' ) ?></td>
<td><?php echo $tax->tax_val4;?></td>
<td><?php echo $model->getItemReturnCgstAmount ( $vendor_id, $outlet_id, $tax->id, 'igst_amt' ) ?></td>
</tr>

<?php }?>
<tr>
												<td></td>
												<td></td>
												<td></td>
												<td>Total Cgst</td>
												<td><b><?php echo $total_cgst;?></b></td>
												<td>Total Sgst</td>
												<td><b><?php echo $total_sgst;?></b></td>
												<td>Total Cess</td>
												<td><b><?php echo $total_cess;?></b></td>
												<td>Total Igst</td>
												<td><b><?php echo $total_igst;?></b></td>
											</tr>
</table>
</div>
<?php }?>
</div>
<div class="col-md-5  col-md-offset-7">
<div class="total-bill">

<div class="form-group">
<label class="col-sm-4 control-label text-right">Gross Amount </label>
<div class="col-sm-8"><input type="text" class="form-control" readonly="readonly" id="gross_amount" ></div>
<div class="clearfix"></div>
</div>
<div class="form-group">
<label class="col-sm-4 control-label text-right">Total Discount </label>
 <div class="col-sm-8"><input type="text" class="form-control"  readonly="readonly" id="total_discount" >
 </div>
  <div class="clearfix"></div>
 </div>
<div class="form-group">
<label class="col-sm-4 control-label text-right">Tax Amount</label>
<div class="col-sm-8"> <input type="text" class="form-control"  readonly="readonly" id="tax_amount" >
</div>
<div class="clearfix"></div>
</div>
<div class="form-group">
<label class="col-sm-4 control-label text-right">Bill Amount </label>
<div class="col-sm-8"><input type="text" class="form-control"  readonly="readonly" id="bill_amount" >
</div>
<div class="clearfix"></div>
</div>

</div>
</div>

</div>
              </div>
            </div>
          </div>
    
        </div>
      </div>
    </div>
  </div>
</section>
<?php $user = Yii::$app->user->model;
$role = UserRole::find()->where(['title'=>'Admin'])->one();?>
<script>
$('.mrp_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 
	 if(mrp != ''){
			$('#sale_rate'+arr['1']).val(mrp);
		}
		<?php if($user->role_id != $role->id){?>
		$('#sale_rate'+arr['1']).attr('readonly', true);
		<?php }?>
	
});
$('.req_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id);
	
	
});
$('.mrp_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id);
	
	
});
$('.price_inpput').change(function(){


	var mrp = $(this).val();
	var price = mrp;
     var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	var qty = $('#req_input'+id).val();
	var search = mrp.search( '/' );
	if(search != '-1'){
	var price = parseFloat(mrp)/parseFloat(qty);
	}
	$(this).val(parseFloat(price).toFixed(2));

	 gridcalculation(id);
	
	
});
$('.discount_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id,val=0);
	
	
});

$('.other_charge_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id);
	
	
});
$('.discount_amt_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id,val=1);
	
	
});
$('.discount_amt1_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id,val=1);
	
	
});
$('.discount1_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id,val=2);
	
	
});

function gridcalculation(id,val=1){
	var CGST_amt = '0.00';
	var SGST_amt = '0.00';
	var CESS_amt = '0.00';
	var IGST_amt = '0.00';
	var CGST_per = '0.00';
	var SGST_per = '0.00';
	var CESS_per = '0.00';
	var IGST_per = '0.00';
	var margin = '0.00';
	var price = $('#price_input'+id).val();
	var discount = $('#discount_input'+id).val();
	var discount1 = $('#discount1_input'+id).val();
	 var CGST_per = $('#CGST_per_input'+id).val();
    var  SGST_per = $('#SGST_per_input'+id).val();
     var CESS_per = $('#CESS_per_input'+id).val(); 
     var IGST_per = $('#IGST_per_input'+id).val(); 
     if(discount == '' ){
         discount = '0.00';
     }
     if(discount1 == '' ){
         discount1 = '0.00';
     }
     
	if(price != '' &&  discount != ''){
		var qty = $('#req_input'+id).val();
		var discount_amt = qty * (price * discount/100);
		if(val !=0 && discount =='0.00'){
			discount_amt = $('#discount_amt_input'+id).val(); 
			if(discount_amt == ''){
				discount_amt = '0.00';
			}
		}
		var discount_amt1 = qty * (price * discount1/100);
		if(val !=2 && discount1 =='0.00'){
			discount_amt1 = $('#discount_amt1_input'+id).val(); 
			if(discount_amt1 == ''){
				discount_amt1 = '0.00';
			}
		}
		
		var calculate_amount = qty * (parseFloat(price));
		console.log('calculate_amount' + calculate_amount)
		var calculate_discount =  parseFloat(discount_amt1)+parseFloat(discount_amt);
		console.log('calculate_discount' + calculate_discount)
		var taxable_amount = parseFloat(calculate_amount) - parseFloat(calculate_discount);
		console.log('taxable_amount' + taxable_amount)
		 CGST_amt = taxable_amount * CGST_per/100;
		console.log('CGST_amt' + CGST_amt)
		 SGST_amt = taxable_amount * SGST_per/100;
		 CESS_amt = taxable_amount * CESS_per/100; 
		 IGST_amt = taxable_amount * IGST_per/100; 
			$('#discount_amt_input'+id).val(parseFloat(discount_amt).toFixed(2));
			$('#discount_amt1_input'+id).val(parseFloat(discount_amt1).toFixed(2));
			$('#CGST_amt_input'+id).val(parseFloat(CGST_amt).toFixed(2));
			$('#SGST_amt_input'+id).val(SGST_amt.toFixed(2));
			$('#CESS_amt_input'+id).val(CESS_amt.toFixed(2));
			$('#IGST_amt_input'+id).val(IGST_amt.toFixed(2));
		var other_charge = $('#other_charge_input'+id).val();
	
		if (other_charge == ''){
			other_charge = '0.00';
		}
		var mrp = $('#mrp_input'+id).val();
		console.log('mrp'+mrp);
		<?php if($model->getGSTTrue($vendor_id,$outlet_id) == true){?>
		 var gst = parseFloat(CGST_amt)+parseFloat(SGST_amt) + parseFloat(CESS_amt);
		<?php }else{?>
		 var gst = parseFloat(IGST_amt) + parseFloat(CESS_amt);
		<?php }?>
		
		
		 var margin = ((parseFloat(mrp) - parseFloat(price))+ parseFloat(gst))*100/(parseFloat(price)+ parseFloat(gst));
		 $('#margin_input'+id).val(margin.toFixed(2));
		 var calculated_amt = 0;
		 var total_discount_amt = 0;
		 var calculated_tax_amt = 0;
		 var calculated_gross = 0;
		 <?php if($model->getGSTTrue($vendor_id,$outlet_id) == true){?>
		var total_amount =  parseFloat(taxable_amount) + parseFloat(CGST_amt) +  parseFloat(SGST_amt) + parseFloat(CESS_amt);
       <?php }else{?>
       var total_amount =  parseFloat(taxable_amount) + parseFloat(IGST_amt)  + parseFloat(CESS_amt);
       <?php }?>
		$('#total_amount_input'+id).val(total_amount.toFixed(2));
		
		
		 $(".total_amount_input").each(function() {
			
			 calculated_amt += parseFloat($(this).val());
			 console.log('total amount '+$(this).val()); 
			   
		    });
		 $(".discount_amt_input").each(function() {
			 var str = $(this).attr('id');
			 console.log('str'+str); 
			 var arr = str.split('input');
			 var id = arr['1'];
			 total_discount_amt += parseFloat($(this).val())+parseFloat($('#discount_amt1_input'+id).val());
			 console.log('val2'+$(this).val()); 
			   
		    });
		 $(".CGST_amount_input").each(function() {
			 var str = $(this).attr('id');
			 console.log('str'+str); 
			 var arr = str.split('input');
			 var id = arr['1'];
			 var this_tax = parseFloat($('#CGST_amt_input'+id).val()) + parseFloat($('#SGST_amt_input'+id).val()) +
			 parseFloat($('#CESS_amt_input'+id).val()) ;
			 calculated_tax_amt += this_tax;
			 console.log('val3'+calculated_tax_amt); 
			   
		    });
		 $(".IGST_amount_input").each(function() {
			 var str = $(this).attr('id');
			 console.log('str'+str); 
			 var arr = str.split('input');
			 var id = arr['1'];
			 var this_tax = parseFloat($('#IGST_amt_input'+id).val()) +
			 parseFloat($('#CESS_amt_input'+id).val()) ;
			 calculated_tax_amt += this_tax;
			 console.log('val3'+calculated_tax_amt); 
			   
		    });
		 $(".price_inpput").each(function() {
			 var str = $(this).attr('id');
			 var arr = str.split('input');
			 var id = arr['1'];
			 var this_price = parseFloat($('#price_input'+id).val()) * parseFloat($('#req_input'+id).val()) ;
			 calculated_gross += this_price;
			 console.log('val4'+calculated_gross); 
			   
		    });
		    $('#gross_amount').val(calculated_gross.toFixed(2));
		    $('#total_discount').val(total_discount_amt.toFixed(2));
		    $('#tax_amount').val(calculated_tax_amt.toFixed(2));
			var total = (calculated_gross + calculated_tax_amt) - total_discount_amt;
		     $('#bill_amount').val(total.toFixed());
		  
		///var total_amount =  qty * (parseFloat(discounted_amount) + parseFloat(CGST_amt) + parseFloat(SGST_amt) + parseFloat(CESS_amt)+ parseFloat(other_charge));
		var table_tax_id = $('#select_tax'+id).val();
		    checkTaxTable(table_tax_id,id);
		
		}
}

function checkTaxTable(tax_id,id){
	var cgst = '0.00';
	var sgst = '0.00';
	var cess = '0.00';
	var igst = '0.00';
	var return_item_ids = [];
	$(".select_tax").each(function() {
		 var str = $(this).attr('id');
		 console.log('str'+str); 
		 var arr = str.split('tax');
		 var valid = arr['1'];
		
		console.log('kriti'+valid );
		 var free_val = $('#free_val'+valid).val();
		 var app_id = $('#select_tax'+valid).val();
		 var cgst = $('#CGST_amt_input'+valid).val();
		 var sgst = $('#SGST_amt_input'+valid).val();
		 var cess = $('#CESS_amt_input'+valid).val();
		 var igst = $('#IGST_amt_input'+valid).val();
		 var qty = $('#req_input'+valid).val();
		 var price = $('#price_input'+valid).val();
		 var discount = $('#discount_amt_input'+valid).val();
		 var discount1 = $('#discount_amt1_input'+valid).val();
		 var igst = $('#IGST_amt_input'+valid).val();
		 if(free_val == 'No'){
			 var is_free = 0;
		 }else{
			 var is_free = 1;
		 }
		 var data =  {
				 "id": valid,
			        "tax_id":app_id,
			        "cgst" :cgst,
			        "sgst" :sgst,
			        "cess" :cess,
			        "igst" :igst,
			        "qty" : qty,
			        "price":price,
			        "discount":discount,
			        "discount1":discount1,
			        "is_free" :is_free
			       
		    };
		 var receiveddata = JSON.stringify(data); 
		
		 console.log('receiveddata'+receiveddata);
		  
		 return_item_ids.push(receiveddata);
		console.log('return_item_ids: ' + return_item_ids);
		
			
		   
	    });
	    
	
	$(".CGST_amount_input").each(function() {
		 var str = $(this).attr('id');
		// console.log('str'+str); 
		 var arr = str.split('input');
		 var valid = arr['1'];
		 var app_id = $('#select_tax'+valid).val();
		 if(app_id == tax_id){
		 var this_tax = parseFloat(cgst) + parseFloat($('#CGST_amt_input'+valid).val());
		 cgst = this_tax;
		 }
		 
		   
	    });
	$(".SGST_amount_input").each(function() {
		 var str = $(this).attr('id');
		// console.log('str'+str); 
		 var arr = str.split('input');
		 var valid = arr['1'];
		 var app_id = $('#select_tax'+valid).val();
		 if(app_id == tax_id){
		 var this_tax = parseFloat(sgst) + parseFloat($('#SGST_amt_input'+valid).val());
		 sgst = this_tax;
		 }
		 
		   
	    });
	$(".CESS_amount_input").each(function() {
		 var str = $(this).attr('id');
		// console.log('str'+str); 
		 var arr = str.split('input');
		 var valid = arr['1'];
		 var app_id = $('#select_tax'+valid).val();
		 if(app_id == tax_id){
			 var this_tax = parseFloat(cess) + parseFloat($('#CESS_amt_input'+valid).val());
			 cess = this_tax;
		
		 }
		 
		   
	    });
	$(".IGST_amount_input").each(function() {
		 var str = $(this).attr('id');
		// console.log('str'+str); 
		 var arr = str.split('input');
		 var valid = arr['1'];
		 var app_id = $('#select_tax'+valid).val();
		 if(app_id == tax_id){
			 var this_tax = parseFloat(igst) + parseFloat($('#IGST_amt_input'+valid).val());
			 igst = this_tax;
		
		 }
		 
		   
	    });
   // console.log('cgst'+cgst);
   //console.log('sgst'+sgst);
    console.log('cess'+cess);
  //  console.log('igst'+igst);

	jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('ItemReturnItem/ajaxTaxTable') ?>',
	       'data': {'tax_id': tax_id,'id':id,
	    	   'cgst': cgst,'sgst':sgst,
	    	   'cess': cess,'igst':igst,'return_item_ids':return_item_ids},
	      // dataType: 'json',
	       'success': function (data) {
		      console.log('data'+data);
	          $('#taxes').html('');
	         $('#taxes').html(data);
	       },
	       'cache': false
	    }
	    );
}


$(document).ready(function () {
	
	$('.select_tax').change(function(){
	var tax = $(this).val();
	console.log('id'+$(this).attr('id'));
	var str = $(this).attr('id');
	 var arr = str.split('tax');
	 var id = arr['1'];
	
	var tax_id = $('#select_tax'+id).val();
	// console.log('tax_id', tax_id, tax);
	checkTaxVal(tax,id);
 
});
	//checkBarcodes();
	checkcompletecalc();
	 <?php if($vendor_id != null) {?>
	   var select_vendor = "<?php echo $vendor_id;?>";
	   $('#ItemReturnItem_vendor_id').val(select_vendor);
	   <?php }?>
	   <?php if($outlet_id != null) {?>
	   var select_outlet = "<?php echo $outlet_id;?>";
	   $('#ItemReturnItem_outlet_id').val(select_outlet);
	   <?php }?>
	   <?php if($start_date != null) {?>
	   var start_date = "<?php echo $start_date;?>";
	   $('#ItemReturnItem_mrs_req_date').val(start_date);
	   <?php }?>
	var vendor_id = $('#ItemReturnItem_vendor_id').val();
	
	
    $('#ItemReturnItem_item_id').change(function () {  
    	checkBarcodes();
    	
    });
   

 });

 function checkTaxVal(tax_id,id){
	  jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('purchaseBillDetail/ajaxTax') ?>',
	       'data': {'tax_id': tax_id},
	       dataType: 'json',
	       'success': function (data) {
	           $('#CGST_per_input'+id).val(data.cgst);
	           $('#SGST_per_input'+id).val(data.sgst);
	           $('#CESS_per_input'+id).val(data.cess);
	           $('#IGST_per_input'+id).val(data.igst);
	           gridcalculation(id);
	       },
	       'cache': false
	    }
	    );
	  
}

function checkcompletecalc(){
	 var calculated_amt = 0;
	 var total_discount_amt = 0;
	 var calculated_tax_amt = 0;
	 var calculated_gross = 0;
$(".total_amount_input").each(function() {
	
	 calculated_amt += parseFloat($(this).val());
	 console.log('bill total val1'+$(this).val()); 
	   
   });
$(".discount_amt_input").each(function() {
	 var str = $(this).attr('id');
	 console.log('str'+str); 
	 var arr = str.split('input');
	 var id = arr['1'];
	 total_discount_amt += parseFloat($(this).val())+parseFloat($('#discount_amt1_input'+id).val());
	 console.log('val2'+$(this).val()); 
	   
   });
$(".CGST_amount_input").each(function() {
	 var str = $(this).attr('id');
	 console.log('str'+str); 
	 var arr = str.split('input');
	 var id = arr['1'];
	 var this_tax = parseFloat($('#CGST_amt_input'+id).val()) + parseFloat($('#SGST_amt_input'+id).val()) +
	 parseFloat($('#CESS_amt_input'+id).val()) ;
	 calculated_tax_amt += this_tax;
	 console.log('val3'+calculated_tax_amt); 
	   
   });
$(".IGST_amount_input").each(function() {
	 var str = $(this).attr('id');
	 console.log('str'+str); 
	 var arr = str.split('input');
	 var id = arr['1'];
	 var this_tax = parseFloat($('#IGST_amt_input'+id).val()) +
	 parseFloat($('#CESS_amt_input'+id).val()) ;
	 calculated_tax_amt += this_tax;
	 console.log('val3'+calculated_tax_amt); 
	   
   });
$(".price_inpput").each(function() {
	 var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 var this_price = parseFloat($('#price_input'+id).val()) * parseFloat($('#req_input'+id).val()) ;
	 calculated_gross += this_price;
	 console.log('val4'+calculated_gross); 
	   
   });
   $('#gross_amount').val(calculated_gross.toFixed(2));
   $('#total_discount').val(total_discount_amt.toFixed(2));
   $('#tax_amount').val(calculated_tax_amt.toFixed(2));
   var total= calculated_gross + calculated_tax_amt;
    
	$('#bill_amount').val(total.toFixed());

   }
$('#ItemReturnItem_vendor_id').change(function(){
	var vendor_id = $('#ItemReturnItem_vendor_id').val();
	
 
});
function checkBarcodes(){
	 var vendor_id = $('#ItemReturnItem_vendor_id').val();
	 console.log('vendor_id'+vendor_id);
	 if(vendor_id != ''){
	 var item_id = $('#item_hsn_code').val();
	    jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('purchaseBillDetail/ajaxItems') ?>',
	       'data': {'item_id': item_id},
	       'success': function (data) {
	          // $('#item_detail_data').html('');
	          // $('#item_detail_data').html(data);
	    	   $('#ItemReturnItem_bar_code').val('');
	           $('#ItemReturnItem_bar_code').val(data);
	           $('#ItemReturnItem_bar_code').trigger('change');
	         
	       },
	       'cache': false
	    }
	    );	
	 }else{
		  alert('Please select vendor and outlet');
	 }
}
$('#ItemReturnItem_bar_code').change(function(){
	  checkTaxes();	
	});


function checkTaxes(){
	 var item_id = $('#item_hsn_code').val();
	 var vendor_id = $('#ItemReturnItem_vendor_id').val();
	 var item_detail_id = $('#ItemReturnItem_bar_code').val();
	 if(vendor_id != ''){
	    jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('ItemReturnItem/ajaxTax') ?>',
	       'data': {'item_id': item_id,'item_detail_id':item_detail_id,'vendor_id':vendor_id},
	       dataType: 'json',
	       'success': function (data) {
	    	   if(data.msg == 'success'){
	           $('#item_tax_data').html('');
	           $('#item_tax_data').html(data.options);
	           $('#CGST_per').val(data.cgst);
	           $('#SGST_per').val(data.sgst);
	           $('#CESS_per').val(data.cess);
	           $('#IGST_per').val(data.igst);
	           $('#ItemReturnItem_mrp').val(data.mrp);
	           $('#ItemReturnItem_price_field').val(data.price);
	           $('#ItemReturnItem_sale_rate').val(data.sale_rate);
	           $('#ItemReturnItem_item_detaill_list_id').val(data.tax_id);
	           $('#ItemReturnItem_item_detaill_id').val(data.item_detail_id);
		          
	           $('#ItemReturnItem_item_val_id').val(data.item_title);
	           $('#item_hsn_code').val(data.item_id);
	           if(data.attr == 'readOnly'){
	           $('#ItemReturnItem_sale_rate').attr('readonly', true);
	           }else{
		           
	        	   $('#ItemReturnItem_sale_rate').attr('readonly', false);
	           }
	           calculation();
	    	   }else{
	    		   if(data.msg == 'Inactive'){
				       alert('Scanned Item is not of Active');
			    	   }else{
			    		   alert('Scanned Item is not of selected vendor');
			    	   }
	    	   }
	       },
	       'cache': false
	    }
	    );	
	 }else{
		 alert('Please select vendor and outlet');
	 }
}
</script>
<script>
$('#ItemReturnItem_mrs_id').change(function(){
	var vendor_id = $('#ItemReturnItem_vendor_id').val();
	console.log(vendor_id);
	var mrsid = $('#ItemReturnItem_mrs_id').val();
	var url = '<?php echo Ui::to('ItemReturnItem/admin')?>/id/'+vendor_id+'/mrsid/'+mrsid;
	window.location.href = url;
});

$('#yw3').click(function(){
var req_qty = $('#ItemReturnItem_qty').val();
var vendor_id = $('#item_return_vendor_id').val();
var outlet_id = $('#item_return_outlet_id').val();
console.log('req_qty'+req_qty+'vendor_id'+vendor_id+'outlet_id'+outlet_id);

	if(req_qty != '' ){
	
	if(vendor_id != '' && outlet_id != ''){

 jQuery.ajax({
     'type': 'POST',
     'url': '<?php echo Ui::to('ItemReturnItem/create') ?>',
     data: $("#mrs-detail-add-form").serialize(),
     'success': function (data) {
         
      //   alert(data);
      if(data == 'success'){
        location.reload();
      }else{
          alert('Select Vendor and Outlet');
      }
    /*      $('#item_detail_data').html('');
         $('#item_detail_data').html(data); */
       
     },
     'cache': false
  }
  );
//console.log(form_values);
	}else{
		alert('Please select vendor and outlet');
	}
	}else{
		alert('Please add required quantity');
	}
	
});
$('#ItemReturnItem_mrp').change(function(){
	var mrp = $('#ItemReturnItem_mrp').val();
	if(mrp != ''){
		$('#ItemReturnItem_sale_rate').val(mrp);
	}
	<?php if($user->role_id != $role->id){?>
	$('#ItemReturnItem_sale_rate').attr('readonly', true);
	<?php }?>
	
});

$('#ItemReturnItem_qty').change(function(){
	calculation();
	
});
$('#ItemReturnItem_price_field').change(function(){
	calculation();
	
});
$('#ItemReturnItem_discount').change(function(){
	calculation(val=0);
	
});
$('#ItemReturnItem_discount_amt').change(function(){
	calculation(val=1);
	
});
$('#ItemReturnItem_discount1').change(function(){
	calculation(val=2);
	
});
$('#ItemReturnItem_discount_amt1').change(function(){
	calculation(val=1);
	
});
$('#ItemReturnItem_other_charge').change(function(){
	calculation();
	
});
$('#ItemReturnItem_mrp').change(function(){
	calculation();
	
});
$('#ItemReturnItem_mrp').change(function(){
	calculation();
	
});
function calculation(val = 1){
	
	var CGST_amt = '0.00';
	var SGST_amt = '0.00';
	var CESS_amt = '0.00';
	var IGST_amt = '0.00';
	var price = $('#ItemReturnItem_price_field').val();
	var discount = $('#ItemReturnItem_discount').val();
	var discount1 = $('#ItemReturnItem_discount1').val();
	 var CGST_per = $('#CGST_per').val();
    var  SGST_per = $('#SGST_per').val();
     var CESS_per = $('#CESS_per').val();
     var IGST_per = $('#IGST_per').val();
     if(discount == '' || discount == 'NaN'){
         var discount = '0.00';
         var discount_amt = '0.00';
     }
     if(discount1 == ''|| discount1 == 'NaN'){
         var discount1 = '0.00';
         var discount_amt1 = '0.00';
     }
 	console.log('IGST_per'+IGST_per);
 	console.log('IGST_amt'+IGST_amt);
	if(price != '' &&  discount != ''){
		console.log('discount'+discount);
		var qty = $('#ItemReturnItem_qty').val();
		if(discount != '0.00')
		var discount_amt = qty * (price * discount/100);
		
		if(val != 0){
			 discount_amt =  $('#ItemReturnItem_discount_amt').val();
			 if(discount_amt == ''){
				  var discount_amt = '0.00';
			 }
		}
		
		console.log('discount_amt'+discount_amt);
		if(discount1 != '0.00')
		var discount_amt1 = qty * (price * discount1/100);
		if(val != 2){
			 discount_amt1 =  $('#ItemReturnItem_discount_amt1').val();
			 if(discount_amt1 == ''){
				  var discount_amt1 = '0.00';
			 }
		}
		console.log('discount_amt1'+discount_amt1);
		var calculate_amount = qty * (parseFloat(price));
		console.log('calculate_amount' + calculate_amount);
		var calculate_discount =  parseFloat(discount_amt1)+parseFloat(discount_amt);
		console.log('calculate_discount' + calculate_discount);
		var taxable_amount = parseFloat(calculate_amount) - parseFloat(calculate_discount);
		console.log('taxable_amount' + taxable_amount)
		 CGST_amt = taxable_amount * CGST_per/100;
		 SGST_amt = taxable_amount * SGST_per/100;
		 CESS_amt = taxable_amount * CESS_per/100;
		 IGST_amt = taxable_amount * IGST_per/100;
			$('#ItemReturnItem_discount_amt').val(discount_amt);
			$('#ItemReturnItem_discount_amt1').val(discount_amt1);
			$('#CGST_amt').val(CGST_amt.toFixed(2));
			$('#SGST_amt').val(SGST_amt.toFixed(2));
			$('#CESS_amt').val(CESS_amt.toFixed(2));
			$('#IGST_amt').val(IGST_amt.toFixed(2));
			var mrp = $('#ItemReturnItem_mrp').val();
			console.log('mrp'+mrp);
			<?php if($model->getGSTTrue($vendor_id,$outlet_id) == true){?>
			 var gst = parseFloat(CGST_amt)+parseFloat(SGST_amt) + parseFloat(CESS_amt);
			<?php }else{?>
			 var gst = parseFloat(IGST_amt) + parseFloat(CESS_amt);
			<?php }?>
			
			
			 var margin = ((parseFloat(mrp) - parseFloat(price))+ parseFloat(gst))*100/(parseFloat(price)+ parseFloat(gst));
			 $('#ItemReturnItem_margin').val(margin.toFixed(2));
		var other_charge = $('#ItemReturnItem_other_charge').val();
		
		if (other_charge == ''){
			other_charge = '0.00';
		}
		console.log(other_charge);
		 <?php if($model->getGSTTrue($vendor_id,$outlet_id)== true){?>
		var total_amount =  parseFloat(taxable_amount) + parseFloat(CGST_amt) + parseFloat(SGST_amt) + parseFloat(CESS_amt);
<?php }else{?>
var total_amount =  parseFloat(taxable_amount) + parseFloat(IGST_amt) + parseFloat(CESS_amt);

<?php }?>

		$('#ItemReturnItem_total_amt').val(total_amount.toFixed(2));
		}
}
$('#ItemReturnItem_vendor_id').change(function(){
	var vendor_id = $('#ItemReturnItem_vendor_id').val();
	$('#item_return_vendor_id').val(vendor_id);
	checkOutlets(vendor_id);

});
$('#ItemReturnItem_outlet_id').change(function(){
	var outlet_id = $('#ItemReturnItem_outlet_id').val();
	$('#item_return_outlet_id').val(outlet_id);
});
function checkOutlets(vendor_id){
	   jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('item/checkOutlets') ?>',
	       'data': {'vendor_id': vendor_id},
	       'success': function (data) {
	           $('#ItemReturnItem_outlet_id').html('');
	           $('#ItemReturnItem_outlet_id').html(data);
	       },
	       'cache': false
	    });
}
$(document).ready(function(){
	//$('#mrs-qty input').addClass('approved_qty');
	//var mrsid = ("#MrsDetail_mrs_id").val();
	var vendor = "<?php echo $vendor_id;?>";
	var outlet = "<?php echo $outlet_id;?>";
	if(vendor != null){
		$('#ItemReturnItem_vendor_id').val(vendor);
		$('#item_return_vendor_id').val(vendor);
		
		
		
	}
	if(outlet != null){
		$('#ItemReturnItem_outlet_id').val(outlet);
		$('#item_return_outlet_id').val(outlet);
		$('#ItemReturnItem_qty').val(1);
	
		
		
	}
});
</script>