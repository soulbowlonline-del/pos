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
			
				Yii::app()->session['tally_start_date'] ='';
		}
		if(empty($model->tally_end_date))
		{
				
			Yii::app()->session['tally_end_date'] ='';
		}
		?>
<section class="content-header">
  <h1> <?php echo Yii::t('app', 'Tally Report') ;?> </h1>
  
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
					
					$form = $this->beginWidget ( 'bootstrap.widgets.TbActiveForm', array (
							'id' => 'customer-export-form',
							'type' => 'horizontal',
							'action' => Yii::app ()->createUrl ( 'purchaseBillDetail/report?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => array (
									'enctype' => 'multipart/form-data' 
							) 
					) );
					?>
<?php

					$cols = array (
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
					);
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
		
		$this->widget ( 'bootstrap.widgets.TbButton', array (
				'buttonType' => 'submit',
				'type' => 'primary',
				'label' => 'Export',
				'htmlOptions' => array (
						'id' => 'form-export' 
				) 
		) );
		?>
	</div>
<?php $this->endWidget(); ?>
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
          
            <?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'stock-adjust-log-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>

<div class="col-md-6">

<?php echo $form->datepickerRow($model, 'tally_start_date',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
						'options'=>array('dateFormat'=>'yy-mm-dd')))

; ?>
</div>
<div class="col-md-6">
<?php echo $form->datepickerRow($model, 'tally_end_date',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
								'options'=>array('dateFormat'=>'yy-mm-dd')))

; ?>
</div>

	<div class="form-actions pull-left">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		)); ?>
	</div>

<?php $this->endWidget(); ?>
</br>
          <div class="row">
            <div class="col-md-12">


<div class="clearfix"></div>
 <div class="table-responsive customgridwidth">
               

 
<?php 
    $this->widget('bootstrap.widgets.TbGridView', array(
    'id'=>'purchase-order-detail-grid',
    'dataProvider'=>$model->reportsearch(),
    'pager'=>true,
    'filter'=>$model,
    		'afterAjaxUpdate'=>"function(){
                                                       $.datepicker.setDefaults($.datepicker.regional['en']);
                                                        $('#Projects_projStart').datepicker({'dateFormat': 'yy-mm-dd'});
    		
                                                }",
    'columns'=>array(
        // array(
            // 'id'=>'mrsId',
            // 'class'=>'CCheckBoxColumn',
            // 'selectableRows' => '50',   
        // ),
     //   'purchase_bill_id',
    	//	'tax_id',
		'id',
    		array(
    				'header'=>'Grn Date',
    				'value'=> 'date("Y-m-d",strtotime($data->create_time))',
    				),
    		array(
    				'header'=>'Date',
    				'name'=>'start_date',
    				'value'=>'isset($data->purchaseBill)?$data->purchaseBill->end_date:""',
    				'filter' => $this->widget('zii.widgets.jui.CJuiDatePicker',
    						array(
    								'model' => $model,
    								'attribute' => 'start_date',
    								'language' => 'en',
    								'htmlOptions' => array(
    										'id' => 'Projects_projStart',
    										'dateFormat' => 'yy-mm-dd',
    								),
    								'options' => array(  // (#3)
    										'showOn' => 'focus',
    										'dateFormat' => 'yy-mm-dd',
    										'showOtherMonths' => true,
    										'selectOtherMonths' => false,
    										'changeMonth' => false,
    										'changeYear' => false,
    								)
    						),
    						true),
    					
    		),
    		array(
    				'header'=>'Vendor',
    				'name'=>'vendor_id',
    				'value'=>'GxHtml::valueEx($data->purchaseBill->vendor)',
    				'filter'=>GxHtml::listDataEx(Vendor::model()->findAllAttributes(null, true)),
    					
    		),
    		array(
    				'header'=>'HSN Code',
    				'name'=>'tax_id',
    				'value'=>'isset($data->tax)?$data->tax->hrn_code:""',
    				'filter'=>Tax::getHsnCodeList(),
    					
    		),
    		array(
    				'header'=>'GST NO',
    				'value'=>'$data->getVendorTAXNO()',
    					
    		),
    		array(
    				'header'=>'CGST (%age)',
    				'value'=>'$data->getTaxPercentage("cgst_per")',
    					
    		),
    		array(
    				'header'=>'SGST (%age)',
    				'value'=>'$data->getTaxPercentage("sgst_per")',
    					
    		),
    		array(
    				'header'=>'CESS (%age)',
    				'value'=>'$data->getTaxPercentage("cess_per")',
    					
    		),
    		array(
    				'header'=>'IGST (%age)',
    				'value'=>'$data->igst_per',
    					
    		),
    		array(
    				'header'=>'Net Amount',
    				'name'=>'amount',
    				'value'=>'isset ( $data->purchaseBill ) ? $data->purchaseBill->net_bill_amount: ""',
    					
    		),
    		array(
    				'header'=>'Basic Value',
    				'name'=>'amount',
    				'value'=>'$data->getBasicAmount()',
    					
    		),
    		array(
    				'header'=>'Discount',
    				'value'=>'$data->getMainDiscount()',
    					
    		),
    		/* array(
    				'header'=>'Scheme Discount',
    				'value'=>'$data->discount_amt1',
    					
    		), */
    		array(
    				'header'=>'CGST Amount',
    				'value'=>'$data->getCgstAmount()',
    					
    		),
    		array(
    				'header'=>'SGST Amount',
    				'value'=>'$data->getSgstAmount()',
    					
    		),
    		array(
    				'header'=>'CESS Amount',
    				'value'=>'$data->getCessAmount()',
    					
    		),
    		array(
    				'header'=>'IGST Amount',
    				'value'=>'$data->getIgstAmount()',
    					
    		),
    		array(
    				'header'=>'GRN NUMBER',
    				//'name'=>'purchase_bill_id',
    				'value'=>'isset($data->purchaseBill)?$data->purchaseBill->grn_refrence_no:""',
    					
    		),
    		array(
    				'header'=>'SCHEME AND DISCOUNT',
    				'value'=>'$data->getSchemeDiscount()',
    					
    		),
    		
        
    ),
)); ?>


 
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
	var url = '<?php echo Yii::app()->createUrl('purchaseOrderDetail/admin')?>/id/'+vendor_id+'/poid/'+poid;
	window.location.href = url;
});
$(function(){
    $('.modal-body').slimScroll({
        height: '350px'
    });
});
</script>