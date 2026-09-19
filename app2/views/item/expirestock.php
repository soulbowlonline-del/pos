<?php
/**
 * Ported from protected/views/item/expirestock.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Item;
use app\models\ItemDetail;
use app\models\ItemExpire;
use app\models\Outlet;
use app\models\User;
use app\models\Vendor;
use app\widgets\ActionColumn;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\ButtonGroup;
use app\widgets\CJuiDatePicker;
use app\widgets\GridView;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	'Manage',
];
?>
<section class="content-header">
  <h1> <?php echo 'Stock Expire'; ?> </h1>
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
        <div class="box-header"><h3 class="box-title">Select Vendor</h3></div>
          <div class="box-body">
          
	<div class="search-form">
<?php 	$form = ActiveForm::begin([
	'action' => Ui::to($this->context->route),
	'method' => 'post',
	'id' => 'item-form',
	'type'=>'horizontal',		
]); 
?>

		<?php echo $form->dropDownListRow($model, 'vendor_id', Gx::listData(Vendor::find()->where(['status'=>Vendor::STATUS_ACTIVE])->all()), ['prompt' => 'Select Vendor']); ?>
		<?php echo $form->dropDownListRow($model, 'outlet_id', Gx::listData(Outlet::find()->where(['status'=>Outlet::STATUS_ACTIVE])->all()), ['prompt' => 'Select Outlet']); ?>

	<div class="col-md-3 col-xs-12">
		<div class="form-actions">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		]); ?>
	</div>
	</div>
<?php ActiveForm::end(); ?>
</div>
<!--  form code start here -->
<div class="clearfix"></div>
<div class="form well">

<?php if($set == false){ ?>

<div class="alert alert-danger">
Selected Barcode has less quantity in stock then added.
</div>
<?php } ?>
<?php $form = ActiveForm::begin([
		'action' => Ui::to('itemExpireItem/create'),
	'id' => 'item-expire-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	

	<?php echo $form->errorSummary($model); ?>
	<div class="add-item">
<div class="col-md-10 col-sm-12 col-xs-12 margin10">

									<div class="row">


										<div class="col-md-3 col-xs-12 padding2px">
											<label class="control-label" for="">Item</label>
                  <?php echo $form->dropDownList($model, 'item_id',$model->getItemOptions($vendor_id),['class'=>'form-control','empty'=>'Select Item']); ?>
                </div>
										<div class="col-md-3 col-xs-12 padding2px">
											<label class="control-label" for="">Bar Code</label>
											<div id="item_detail_data"></div>
										</div>
												<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Qty</label>
                  <?php
                  echo $form->textField($model,'qty',['class'=>'form-control']); ?>
                </div>
                <div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Mrp</label>
                  <?php  echo $form->textField($model,'mrp',  ['class'=>'form-control']); ?>
                </div>
                <div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Pur Rate</label>
                  <?php  echo $form->textField($model,'sale_rate',  ['class'=>'form-control']); ?>
                </div>
                <div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Amount</label>
                  <?php  echo $form->textField($model,'total_amt',  ['class'=>'form-control']); ?>
                  <input type="hidden" name="ItemExpireItem[vendor_id]" value="" id="Item_expire_field_vendor_id">
                  <input type="hidden" name="ItemExpireItem[outlet_id]" value="" id="Item_expire_field_outlet_id">
                </div>
										
<div class="col-md-2 col-sm-12 col-xs-12 add-custom-bttn-box">
<div class="form-actions">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'add Item',
		]); ?>
	</div></div></div></div>
<?php ActiveForm::end(); ?>

</div>
<!-- form code ends here -->
</div></div>
	
	
     <div class="box">
        <div class="box-header"><h3 class="box-title">Expired Items</h3></div>
          <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customsmallgridwidth">
  <div class="">
<?php echo GridView::widget([
	'id' => 'item-expire-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
		'pager'=>true,
		/* 'afterAjaxUpdate'=>"function(){
                                                       $.datepicker.setDefaults($.datepicker.regional['en']);
                                                        $('#Projects_projStart').datepicker({'dateFormat': 'yy-mm-dd'});
		
                                                }", */
	'columns' => [
		//'id',
			[
					'attribute' =>'item_id',
					'header'=>'Item',
					'value' => function ($data, $key, $index) { return Gx::str($data->item); },
					//	'filter'=>Gx::listData(Item::class),
			],
			[
					'attribute' =>'item_detail_id',
					'header'=>'Barcode',
					'value' => function ($data, $key, $index) { return Gx::str($data->itemDetail); },
					//	'filter'=>Gx::listData(ItemDetail::class),
			],
			[
					'attribute' =>'vendor_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->vendor); },
					'filter'=>Gx::listData(Vendor::class),
			],
			[
					'attribute' =>'outlet_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->outlet); },
					'filter'=>Gx::listData(Outlet::class),
			],
			'qty',
		'mrp',
		'sale_rate',
	//	'free',
			[
					'attribute' =>'total_amt',
					'value' => function ($data, $key, $index) { return $data->total_amt; },
					'footer'=>$model->getTotals($model->search()->getKeys(),'total_amt','tbl_item_expire_item'),
			],
			
// 			array(
// 					'header' => '<a>Create Time</a>',
// 					'attribute' => 'create_time',
// 					'value' => function ($data, $key, $index) { return date("Y-m-d",strtotime($data->create_time)); },
// 					'filter' => CJuiDatePicker::widget(// 							array(
// 									'model' => $model,
// 									'attribute' => 'create_time',
// 									'language' => 'en',
// 									'htmlOptions' => array(
// 											'id' => 'Projects_projStart',
// 											'dateFormat' => 'yy-mm-dd',
// 									),
// 									'options' => array(  // (#3)
// 											'showOn' => 'focus',
// 											'dateFormat' => 'yy-mm-dd',
// 											'showOtherMonths' => true,
// 											'selectOtherMonths' => false,
// 											'changeMonth' => false,
// 											'changeYear' => false,
// 									)
// 							),
// 							true),
			
// 			),
		/*
		'qty',
		'total_amt',
		'vendor_id',
		array(
			'attribute' =>'outlet_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->outlet); },
			'filter'=>Gx::listData(Outlet::class),
			),
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>ItemExpire::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>ItemExpire::getTypeOptions(),
				),
		array(
			'attribute' =>'updated_by',
			'value' => function ($data, $key, $index) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
		/* array(
			'class' => ActionColumn::class,
			'htmlOptions' => array('nowrap'=>'nowrap'),
		), */
			[
						
					'header'=>'<a>Action</a>',
					'class' => ActionColumn::class,
					'template' => '{delete}', //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					'buttons'=>[
								
							'delete'=>[
			
									'url' => function ($data) { return Ui::to("itemExpireItem/delete", ["id" => $data->id]); },
									'label'=>'Delete',
									'options'=>['class'=>'update'],
										
							]
					]
			],
	],
]); ?>
<button class="btn btn-primary" id="approve" type="submit" name="approve">Save</button>
</div>
</div>

<script>
// function reloadGrid(data) {
    // $.fn.yiiGridView.update('menu-grid');
// }
$(document).ready(function(){
	//$('#mrs-qty input').addClass('approved_qty');
	//var mrsid = ("#MrsDetail_mrs_id").val();
	var vendor = "<?php echo $vendor_id;?>";
	var outlet = "<?php echo $outlet_id;?>";
	if(vendor != null){
		$('#ItemExpireItem_vendor_id').val(vendor);
		$('#Item_expire_field_vendor_id').val(vendor);
		
	}
	if(outlet != null){
		$('#ItemExpireItem_outlet_id').val(outlet);
		$('#Item_expire_field_outlet_id').val(outlet);
		$('#ItemExpireItem_qty').val(1);
		
		
	}
	$("#approve").click(function (event) {
	event.preventDefault();
	var vendor = $('#ItemExpireItem_vendor_id').val();
	var outlet = $('#ItemExpireItem_outlet_id').val();
	
 
	$.ajax({
	       url: '<?php echo Ui::to('item/saveExpire'); ?>',
	       type: 'post',

	       data: {
	    	   vendor: vendor,
	    	   outlet: outlet
	    	  
	              },
	       success: function (data) {
	    	$.fn.yiiGridView.update('menu-grid');
	    	alert('Data is saved successfully');
	    	location.reload();
	       }
		 
	  }); 

	  
    });

	
});
$('#ItemExpireItem_item_id').change(function(){
	checkBarcodes();
});
$('#ItemExpireItem_qty').change(function(){
	checkTaxes();
});
$('#ItemExpireItem_mrp').change(function(){
	checkTaxes();
});
function checkBarcodes(){
	 var item_id = $('#ItemExpireItem_item_id').val();
	    jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('item/ajaxExpireItems') ?>',
	       'data': {'item_id': item_id},
	       'success': function (data) {
	           $('#item_detail_data').html('');
	           $('#item_detail_data').html(data);
	         
	       },
	       'cache': false
	    }
	    );	
}
$('#ItemExpireItem_item_id').change(function(){
	checkBarcodes();
});
function checkTaxes(){
	 var item_id = $('#ItemExpireItem_item_id').val();
	 var qty = $('#ItemExpireItem_qty').val();
	 var item_detail_id = $('#ItemExpire_item_detaill_id').val();
	    jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('item/ajaxExpireValues') ?>',
	       'data': {'item_id': item_id,'item_detail_id':item_detail_id,'qty':qty},
	       dataType: 'json',
	       'success': function (data) {
	            $('#ItemExpireItem_qty').val(qty);
	           $('#ItemExpireItem_mrp').val(data.mrp);
	           $('#ItemExpireItem_sale_rate').val(data.sale_rate);
	           $('#ItemExpireItem_total_amt').val(data.total_amt);
	          
	       },
	       'cache': false
	    }
	    );	
}
$('#ItemExpireItem_vendor_id').change(function(){
	var vendor_id = $('#ItemExpireItem_vendor_id').val();
	checkOutlets(vendor_id);
	$('#Item_expire_field_vendor_id').val(vendor_id);
});
$('#ItemExpireItem_outlet_id').change(function(){
	var outlet_id = $('#ItemExpireItem_outlet_id').val();
	$('#Item_expire_field_outlet_id').val(outlet_id);
});
function checkOutlets(vendor_id){
	   jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('item/checkOutlets') ?>',
	       'data': {'vendor_id': vendor_id},
	       'success': function (data) {
	           $('#ItemExpireItem_outlet_id').html('');
	           $('#ItemExpireItem_outlet_id').html(data);
	       },
	       'cache': false
	    });
}
</script>


 
</div>
</div>
</div>
</div>
</div>
</div>
</section>