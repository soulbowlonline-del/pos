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
        <div class="box-header"><h3 class="box-title">PurchaseBillDetails</h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">

<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'mrs-detail-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'action'=>Yii::app()->createUrl('purchaseBillDetail/admin'),
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>

<div class="box-body">
<?php //echo $form->dropDownListRow($model, 'purchase_bill_id', $model->getPOBillOptions($user->id),array('class'=>'form-control')); ?>


<?php echo $form->datepickerRow($model, 'start_date',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
							'options'=>array('format'=>'yyyy-mm-dd')))
; ?>
<?php echo $form->dropdownListRow($model, 'outlet_id', GxHtml::listDataEx(Outlet::model()->findAllByAttributes(array('status'=>Outlet::STATUS_ACTIVE))),array('class'=>'form-control')); ?>
<?php $user = Yii::app()->user->model;
if($user->role_id != 6){?>
<?php echo $form->dropdownListRow($model, 'vendor_id',$model->getPBillVendorOptions(),array('class'=>'form-control')); ?>
<?php }?>
<div class="form-group">
											<label class="control-label col-md-3" for="">Purchase Bill No</label>
											<div id="po_detail_data" class="col-md-9"></div>
										</div>
</div>


	<div class="form-actions box-footer">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		)); ?>
	</div>

<?php $this->endWidget(); ?>

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
<div class="clearfix"></div>
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
				//'filter'=>$model->getItemOptions($vendor_id), 
        		'filter'=> false,
				
	),
			array(
					'header'=>'<a>Bar Code</a>',
					'name'=>'item_detail_id',
					'value'=>'GxHtml::valueEx($data->itemDetail)',
					//	'filter'=>$model->getItemOptionbarcodes(),
					'filter'=> false,
			),
			array(
					'name'=>'outlet_id',
					'value'=>'GxHtml::valueEx($data->outlet)',
					'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
			array(
					'header'=>'vendor',
					'value'=>'GxHtml::valueEx($data->purchaseBill->vendor)',
					
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
    		'discount1',
    		'discount_amt1',
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
 
              </div>
            </div>
          </div>
    
        </div>
      </div>
    </div>
  </div>
</section>

<script>

<?php /*?>
$('#PurchaseBillDetail_purchase_bill_id').change(function(){
	
	var poid = $('#PurchaseBillDetail_purchase_bill_id').val();
	var url = '<?php echo Yii::app()->createUrl('purchaseBillDetail/admin')?>/id/'+vendor_id+'/poid/'+poid;
	window.location.href = url;
});*/ ?>
$(document).ready(function () {

	var vendor_id = $('#PurchaseBillDetail_vendor_id').val();
	checkPONo(vendor_id);
   
   

 });
$('#PurchaseBillDetail_vendor_id').change(function(){
	var vendor_id = $('#PurchaseBillDetail_vendor_id').val();
	checkPONo(vendor_id);
 
});

function checkPONo(vendor_id){
	<?php if($poid != ''){?>
	var poid = <?php echo $poid; ?>;
	<?php }else{?>
	var poid =   $("#PurchaseBillDetail_purchase_bill_id").val();
	<?php }?>
	   jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo CController::createUrl('purchaseBillDetail/ajaxPONo') ?>',
	       'data': {'vendor_id': vendor_id},
	       'success': function (data) {
	           $('#po_detail_data').html('');
	           $('#po_detail_data').html(data);
	           $('#PurchaseBillDetail_purchase_bill_id').val(poid);
	       },
	       'cache': false
	    });
}
</script>