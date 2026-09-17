<?php

$this->breadcrumbs = array(
	$model->label(2) => array('index'),
	Yii::t('app', 'Manage'),
);
?>
<section class="content-header">
  <h1> <?php echo Yii::t('app', 'Stock Expire'); ?> </h1>
  <?php $this->widget('bootstrap.widgets.TbButtonGroup', array(
	'buttons'=>$this->menu,
	'type'=>'success',
	'htmlOptions'=>array('class'=> 'pull-right'),
));
?>
</section>

<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
	
	 <div class="box">
        <div class="box-header"><h3 class="box-title">Select Vendor</h3></div>
          <div class="box-body">
          
	<div class="search-form">
<?php 	$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
	'action' => Yii::app()->createUrl($this->route),
	'method' => 'post',
	'id' => 'item-form',
	'type'=>'horizontal',		
)); 
?>

		<?php echo $form->dropDownListRow($model, 'vendor_id', GxHtml::listDataEx(Vendor::model()->findAllByAttributes(array('status'=>Vendor::STATUS_ACTIVE))), array('prompt' => Yii::t('app', 'Select Vendor'))); ?>
		<?php echo $form->dropDownListRow($model, 'outlet_id', GxHtml::listDataEx(Outlet::model()->findAllByAttributes(array('status'=>Outlet::STATUS_ACTIVE))), array('prompt' => Yii::t('app', 'Select Outlet'))); ?>

	<div class="col-md-3 col-xs-12">
		<div class="form-actions">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		)); ?>
	</div>
	</div>
<?php $this->endWidget(); ?>
</div>
<!--  form code start here -->
<div class="clearfix"></div>
<div class="form well">

<?php if($set == false){ ?>

<div class="alert alert-danger">
Selected Barcode has less quantity in stock then added.
</div>
<?php } ?>
<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
		'action' => Yii::app()->createUrl('itemExpireItem/create'),
	'id' => 'item-expire-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	

	<?php echo $form->errorSummary($model); ?>
	<div class="add-item">
<div class="col-md-10 col-sm-12 col-xs-12 margin10">

									<div class="row">


										<div class="col-md-3 col-xs-12 padding2px">
											<label class="control-label" for="">Item</label>
                  <?php echo $form->dropDownList($model, 'item_id',$model->getItemOptions($vendor_id),array('class'=>'form-control','empty'=>'Select Item')); ?>
                </div>
										<div class="col-md-3 col-xs-12 padding2px">
											<label class="control-label" for="">Bar Code</label>
											<div id="item_detail_data"></div>
										</div>
												<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Qty</label>
                  <?php
                  echo $form->textField($model,'qty',array('class'=>'form-control')); ?>
                </div>
                <div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Mrp</label>
                  <?php  echo $form->textField($model,'mrp',  array('class'=>'form-control')); ?>
                </div>
                <div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Pur Rate</label>
                  <?php  echo $form->textField($model,'sale_rate',  array('class'=>'form-control')); ?>
                </div>
                <div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Amount</label>
                  <?php  echo $form->textField($model,'total_amt',  array('class'=>'form-control')); ?>
                  <input type="hidden" name="ItemExpireItem[vendor_id]" value="" id="Item_expire_field_vendor_id">
                  <input type="hidden" name="ItemExpireItem[outlet_id]" value="" id="Item_expire_field_outlet_id">
                </div>
										
<div class="col-md-2 col-sm-12 col-xs-12 add-custom-bttn-box">
<div class="form-actions">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'add Item',
		)); ?>
	</div></div></div></div>
<?php $this->endWidget(); ?>

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
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'item-expire-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
		'pager'=>true,
		/* 'afterAjaxUpdate'=>"function(){
                                                       $.datepicker.setDefaults($.datepicker.regional['en']);
                                                        $('#Projects_projStart').datepicker({'dateFormat': 'yy-mm-dd'});
		
                                                }", */
	'columns' => array(
		//'id',
			array(
					'name'=>'item_id',
					'header'=>'Item',
					'value'=>'GxHtml::valueEx($data->item)',
					//	'filter'=>GxHtml::listDataEx(Item::model()->findAllAttributes(null, true)),
			),
			array(
					'name'=>'item_detail_id',
					'header'=>'Barcode',
					'value'=>'GxHtml::valueEx($data->itemDetail)',
					//	'filter'=>GxHtml::listDataEx(ItemDetail::model()->findAllAttributes(null, true)),
			),
			array(
					'name'=>'vendor_id',
					'value'=>'GxHtml::valueEx($data->vendor)',
					'filter'=>GxHtml::listDataEx(Vendor::model()->findAllAttributes(null, true)),
			),
			array(
					'name'=>'outlet_id',
					'value'=>'GxHtml::valueEx($data->outlet)',
					'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
			'qty',
		'mrp',
		'sale_rate',
	//	'free',
			array(
					'name'=>'total_amt',
					'value'=>'$data->total_amt',
					'footer'=>$model->getTotals($model->search()->getKeys(),'total_amt','tbl_item_expire_item'),
			),
			
// 			array(
// 					'header' => '<a>Create Time</a>',
// 					'name' => 'create_time',
// 					'value'=>'date("Y-m-d",strtotime($data->create_time))',
// 					'filter' => $this->widget('zii.widgets.jui.CJuiDatePicker',
// 							array(
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
			'name'=>'outlet_id',
			'value'=>'GxHtml::valueEx($data->outlet)',
			'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>ItemExpire::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>ItemExpire::getTypeOptions(),
				),
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			),
		*/
		/* array(
			'class'=>'bootstrap.widgets.TbButtonColumn',
			'htmlOptions' => array('nowrap'=>'nowrap'),
		), */
			array(
						
					'header'=>'<a>Action</a>',
					'class'=>'FaButtonColumn',
					'template' => '{delete}', //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
								
							'delete'=>array(
			
									'url' =>'Yii::app()->controller->createUrl("itemExpireItem/delete", array("id" => $data->id))',
									'label'=>'Delete',
									'options'=>array('class'=>'update'),
										
							)
					)
			),
	),
)); ?>
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
	       url: '<?php echo Yii::app()->createUrl('item/saveExpire'); ?>',
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
	       'url': '<?php echo CController::createUrl('item/ajaxExpireItems') ?>',
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
	       'url': '<?php echo CController::createUrl('item/ajaxExpireValues') ?>',
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
	       'url': '<?php echo CController::createUrl('item/checkOutlets') ?>',
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