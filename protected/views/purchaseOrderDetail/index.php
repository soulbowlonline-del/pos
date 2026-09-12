<?php

$this->breadcrumbs = array(
		$model->label(2) => array('index'),
		Yii::t('app', 'Manage'),
);


Yii::app()->clientScript->registerScript('search', "
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
<section class="content-header">
  <h1> <?php echo Yii::t('app', 'Manage') ;?> <?php echo GxHtml::encode($model->label(2))?> </h1>
 
</section>
<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
        <div class="box-header"><h3 class="box-title">PurchaseOrderDetails</h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12 item-wrap">
<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'mrs-detail-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'action'=>Yii::app()->createUrl('purchaseOrderDetail/index',array('id'=>$user->id)),
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
<div class="box-body">

<?php echo $form->dropDownListRow($model, 'purchase_order_id', $model->getPOOptions($user->id),array('class'=>'form-control')); ?>


<?php echo $form->datepickerRow($model, 'start_date',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
							'options'=>array('format'=>'yyyy-mm-dd')))
; ?>
<?php echo $form->dropdownListRow($model, 'outlet_id', GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),array('class'=>'form-control')); ?>
<?php $user = Yii::app()->user->model;
if($user->role_id == 1){?>
<?php echo $form->dropdownListRow($model, 'vendor_id',$model->getPOVendorOptions(),array('class'=>'form-control')); ?>
<?php }?>

</div>


	<div class="form-actions box-footer">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		)); ?>
	</div>

<?php $this->endWidget(); ?>
			






<div class="clearfix"></div>
<hr />
<?php $vendor_id = null;
$role = UserRole::model()->findByAttributes(array('title'=>'Vendor'));
			$loggedinuser = Yii::app()->user->model;
			if($loggedinuser->role_id == $role->id){
				$user = Vendor::model ()->findByAttributes ( array (
						'create_user_id' => $loggedinuser->id 
				) );
				if($user){
					$vendor_id = $user->id;
				}
			} ?>
							<div class="col-md-12 item-list-table">
 <div class="table-responsive customgridwidth">
               
<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm', array(
    'enableAjaxValidation'=>true,
	'id' => 'mrs-qty',
)); ?>
 
<?php 
    $this->widget('bootstrap.widgets.TbGridView', array(
    'id'=>'purchase-order-detail-grid',
    'dataProvider'=>$model->search(),
    	
    'filter'=>$model,
    'columns'=>array(
        // array(
            // 'id'=>'mrsId',
            // 'class'=>'CCheckBoxColumn',
            // 'selectableRows' => '50',   
        // ),
        array(
			'name'=>'item_id',
			'value'=>'GxHtml::valueEx($data->item)',
				'filter'=>$model->getItemOptions($vendor_id), 
	),
			array(
					'name'=>'item_detail_id',
					'value'=>'GxHtml::valueEx($data->itemDetail)',
					'filter'=>$model->getItemOptionbarcodes(),
			),
			array(
					'name'=>'outlet_id',
					'value'=>'GxHtml::valueEx($data->outlet)',
					'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
			array(
					'header'=>'vendor',
					'value'=>'GxHtml::valueEx($data->purchaseOrder->vendor)',
					
			),
			array(
				'name'=>'req_qty',
				'value'=>'$data->req_qty',
				'filter'=>false,
			),
    		'approved_qty',
    		'mrp',
    		'sale_rate',
    		'price',
    	
    		'discount',
    		'discount_amt',
    	//	'vat',
    		array(
    				'header'=>'Tax',
    				'value'=>'GxHtml::valueEx($data->tax)',
    					
    		),
    		array(
    				'visible'=>$model->getGSTTrue($poid) == true,
    				'name'=>'cgst_per',
    				'value'=>'$data->cgst_per',
    					
    		),
    		array(
    				'visible'=>$model->getGSTTrue($poid) == true,
    				'name'=>'sgst_per',
    				'value'=>'$data->sgst_per',
    					
    		),
    		array(
    				'visible'=>$model->getGSTTrue($poid) == true,
    				'name'=>'sgst_per',
    				'value'=>'$data->cess_per',
    					
    		),
    		array(
    				'visible'=>$model->getGSTTrue($poid) == false,
    				'name'=>'igst_per',
    				'value'=>'$data->igst_per',
    					
    		),
    		array(
    				'visible'=>$model->getGSTTrue($poid) == true,
    				'name'=>'sgst_per',
    				'value'=>'$data->cgst_amt',
    					
    		),
    		array(
    				'visible'=>$model->getGSTTrue($poid) == true,
    				'name'=>'sgst_amt',
    				'value'=>'$data->sgst_amt',
    					
    		),
    		array(
    				'visible'=>$model->getGSTTrue($poid) == true,
    				'name'=>'cess_amt',
    				'value'=>'$data->cess_amt',
    					
    		),
    		array(
    				'visible'=>$model->getGSTTrue($poid) == false,
    				'name'=>'igst_amt',
    				'value'=>'$data->igst_amt',
    					
    		),
    		'other_charge',
    		'amount'
    		
        
    ),
)); ?>

<?php $this->endWidget(); ?>
 
              </div></div>
            </div>
          </div>
    
        </div>
      </div>
    </div>
  </div>
</section>
<?php $user = Yii::app()->user->model;
$role = UserRole::model()->findByAttributes(array('title'=>'Admin'));?>
<script>
$('#PurchaseOrderDetail_purchase_order_id').change(function(){
	var vendor_id = <?php echo $user->id?>;
	var poid = $('#PurchaseOrderDetail_purchase_order_id').val();
	var url = '<?php echo Yii::app()->createUrl('purchaseOrderDetail/index')?>/id/'+vendor_id+'/poid/'+poid;
	window.location.href = url;
});
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
$('.price_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id);
	
	
});
$('.discount_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id);
	
	
});

$('.other_charge_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id);
	
	
});
function gridcalculation(id){
	var CGST_amt = '0.00';
	var SGST_amt = '0.00';
	var CESS_amt = '0.00';
	var CGST_per = '0.00';
	var SGST_per = '0.00';
	var CESS_per = '0.00';
	var price = $('#price_input'+id).val();
	var discount = $('#discount_input'+id).val();
	 var CGST_per = $('#CGST_per_input'+id).val();
    var  SGST_per = $('#SGST_per_input'+id).val();
     var CESS_per = $('#CESS_per_input'+id).val(); 
	if(price != '' &&  discount != ''){
		console.log(price);
		console.log(discount);
		var discount_amt = price * discount/100;
		 CGST_amt = discount_amt * CGST_per/100;
		 SGST_amt = discount_amt * SGST_per/100;
		 CESS_amt = discount_amt * CESS_per/100; 
			$('#discount_amt_input'+id).val(discount_amt);
			$('#CGST_amt_input'+id).val(CGST_amt.toFixed(2));
			$('#SGST_amt_input'+id).val(SGST_amt.toFixed(2));
			$('#CESS_amt_input'+id).val(CESS_amt.toFixed(2));
		var other_charge = $('#other_charge_input'+id).val();
		console.log(other_charge);
		if (other_charge == ''){
			other_charge = '0.00';
		}
		var qty = $('#approve_input_qty'+id).val();
		var discounted_amount = parseFloat(price) - parseFloat(discount_amt);
		var total_amount =  qty * (parseFloat(discounted_amount) + parseFloat(CGST_amt) + parseFloat(SGST_amt) + parseFloat(CESS_amt)+ parseFloat(other_charge));
		$('#total_amount_input'+id).val(total_amount.toFixed(2));
		}
}
$(document).ready(function () {
	checkBarcodes();
	
    $('#PurchaseOrderDetail_item_id').change(function () {  
    	checkBarcodes();
    	
    });
   

 });

function checkBarcodes(){
	 var item_id = $('#PurchaseOrderDetail_item_id').val();
	    jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo CController::createUrl('purchaseOrderDetail/ajaxItems') ?>',
	       'data': {'item_id': item_id},
	       'success': function (data) {
	           $('#item_detail_data').html('');
	           $('#item_detail_data').html(data);
	         
	       },
	       'cache': false
	    }
	    );	
}
function checkTaxes(){
	 var item_id = $('#PurchaseOrderDetail_item_id').val();
	
	 var item_detail_id = $('#PurchaseOrderDetail_item_detail_id').val();
	    jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo CController::createUrl('purchaseOrderDetail/ajaxTax') ?>',
	       'data': {'item_id': item_id,'item_detail_id':item_detail_id},
	       dataType: 'json',
	       'success': function (data) {
	           $('#item_tax_data').html('');
	           $('#item_tax_data').html(data.options);
	           $('#CGST_per').val(data.cgst);
	           $('#SGST_per').val(data.sgst);
	           $('#CESS_per').val(data.cess);
	           calculation();
	       },
	       'cache': false
	    }
	    );	
}
</script>
<script>


$('#yw2').click(function(){
	var poid = $('#PurchaseOrderDetail_purchase_order_id').val();
var req_qty = $('#PurchaseOrderDetail_req_qty').val();
	if(req_qty != ''){

 jQuery.ajax({
     'type': 'POST',
     'url': '<?php echo CController::createUrl('purchaseOrderDetail/ajaxCreate') ?>/id/'+poid,
     data: $("#po-detail-add-form").serialize(),
     'success': function (data) {
         
         alert(data);
         location.reload();
    /*      $('#item_detail_data').html('');
         $('#item_detail_data').html(data); */
       
     },
     'cache': false
  }
  );
//console.log(form_values);
	}
	
});
$('#PurchaseOrderDetail_mrp').change(function(){
	var mrp = $('#PurchaseOrderDetail_mrp').val();
	if(mrp != ''){
		$('#PurchaseOrderDetail_sale_rate').val(mrp);
	}
	<?php if($user->role_id != $role->id){?>
	$('#PurchaseOrderDetail_sale_rate').attr('readonly', true);
	<?php }?>
	
});
$('#PurchaseOrderDetail_discount').change(function(){
	calculation();
	
});
$('#PurchaseOrderDetail_price').change(function(){
	calculation();
	
});
$('#PurchaseOrderDetail_other_charge').change(function(){
	calculation();
	
});
function calculation(){
	var CGST_amt = '0.00';
	var SGST_amt = '0.00';
	var CESS_amt = '0.00';
	var price = $('#PurchaseOrderDetail_price').val();
	var discount = $('#PurchaseOrderDetail_discount').val();
	 var CGST_per = $('#CGST_per').val();
    var  SGST_per = $('#SGST_per').val();
     var CESS_per = $('#CESS_per').val();
	if(price != '' &&  discount != ''){
		var discount_amt = price * discount/100;
		 CGST_amt = discount_amt * CGST_per/100;
		 SGST_amt = discount_amt * SGST_per/100;
		 CESS_amt = discount_amt * CESS_per/100;
			$('#PurchaseOrderDetail_discount_amt').val(discount_amt);
			$('#CGST_amt').val(CGST_amt.toFixed(2));
			$('#SGST_amt').val(SGST_amt.toFixed(2));
			$('#CESS_amt').val(CESS_amt.toFixed(2));
		var other_charge = $('#PurchaseOrderDetail_other_charge').val();
		console.log(other_charge);
		if (other_charge == ''){
			other_charge = '0.00';
		}
		var qty = $('#PurchaseOrderDetail_approved_qty').val();
		var discounted_amount = parseFloat(price) - parseFloat(discount_amt);
		var total_amount =  qty * (parseFloat(discounted_amount) + parseFloat(CGST_amt) + parseFloat(SGST_amt) + parseFloat(CESS_amt) + parseFloat(other_charge));
		$('#PurchaseOrderDetail_amount').val(total_amount.toFixed(2));
		}
}
</script>