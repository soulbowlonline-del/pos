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
  <h1> <?php echo Yii::t('app', 'Debit Return Report') ;?> </h1>
  
</section>
<?php    $this->widget('bootstrap.widgets.TbMenu', array(
       'type' => 'pills',
       'stacked' => false,
       'items' => array(
        		array('label' => 'Export',
        				'url' => array('itemReturnItem/report' ,'exportCSV'=>'1',
        						
        		
        		),
       		
       		),
       ),
   ));  ?>

<section class="content">

  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
        <div class="box-header"><h3 class="box-title">Debit Return Report</h3></div>
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
    				'value'=>'$data->getGRNDateData()',
					
    				),
    		array(
    				'header'=>'Bill Date',
    				//'name'=>'start_date',
    				'value'=>'$data->getBillDateData()',
    				
    					
    		),
			
			/*array(
    				'header'=>'Date',
    				'name'=>'start_date',
    				'value'=>'date("d/m/Y",strtotime($data->create_time))',
    				
    					
    		),*/
			
    		array(
    				'header'=>'Vendor',
    				'name'=>'vendor_id',
    				'value'=>'$data->getVendorName()',
    				'filter'=>GxHtml::listDataEx(Vendor::model()->findAllAttributes(null, true)),
    					
    		),
    		array(
    				'header'=>'HSN Code',
    				'name'=>'tax_id',
    				'value'=>'isset($data->tax)?$data->tax->hrn_code:""',
    				'filter'=>Tax::getHsnCodeList(),
    					
    		),
			array(
    				'header'=>'Bill No.',
    				//'name'=>'purchase_bill_id',
    				'value'=>'isset ( $data->itemReturn ) ? $data->itemReturn->bill_no: ""',
    					
    		),
    		
    		array(
    				'header'=>'GST NO',
    				'value'=>'$data->getVendorTAXNO()',
    					
    		),
			array(
    				'header'=>'GST (%age)',
    				'value'=>'$data->getTotalGstPer()'
    					
    		),
    		array(
    				'header'=>'CGST (%age)',
    				'value'=>'$data->getTaxPercentage("cgst_per")'
    					
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
    				'header'=>'Credit Note No.',
    				//'name'=>'purchase_bill_id',
    				'value'=>'isset ( $data->itemReturn ) ? $data->itemReturn->credit_note_no: ""',
    					
    		),
			array(
    				'header'=>'Credit Note Date',
    				//'name'=>'purchase_bill_id',
    				'value'=>'isset ( $data->itemReturn ) ? $data->itemReturn->credit_note_date: ""',
    					
    		),
			
			array(
    				'header'=>'Debit Note Date',
    				'value'=>'isset ( $data->itemReturn->grn_save_date ) ? date("Y-m-d",strtotime( $data->itemReturn->grn_save_date )) : ""',
    					
    		),
			array(
    				'header'=>'Supplier Invoice No.',
    				//'name'=>'purchase_bill_id',
    				'value'=>'isset ( $data->itemReturn ) ? $data->itemReturn->invoice_no: ""',
    					
    		),
			
    		array(
    				'header'=>'Net Amount',
    			//	'name'=>'total_amt',
    				'value'=>'isset ( $data->itemReturn ) ? $data->itemReturn->total_amt: ""',
    					
    		),
    		array(
    				'header'=>'Basic Value',
    			//	'name'=>'gross_amt',
    				'value'=>'($data->price * $data->qty) - $data->discount_amt',
    					
    		),
    		array(
    				'header'=>'Discount',
    				// 'value'=>'isset ( $data->itemReturn ) ? $data->itemReturn->discount_amt: ""',
    				'value'=>'$data->discount_amt',
    					
    		),
    		array(
    				'header'=>'GST Amount',
    				'value'=>'$data->cgst_amt + $data->sgst_amt + $data->cess_amt + $data->igst_amt',
    					
    		),
    		array(
    				'header'=>'CGST Amount',
    				'value'=>'$data->cgst_amt',
    					
    		),
    		array(
    				'header'=>'SGST Amount',
    				'value'=>'$data->sgst_amt',
    					
    		),
    		array(
    				'header'=>'CESS Amount',
    				'value'=>'$data->cess_amt',
    					
    		),
    		array(
    				'header'=>'IGST Amount',
    				'value'=>'$data->igst_amt',
    					
    		),
    		array(
    				'header'=>'GRN NUMBER',
    				//'name'=>'purchase_bill_id',
					
					'value'=>'$data->getCustomGRNNumber()',
					
    				//'value'=>"GR-" .'isset ( $data->itemReturn ) ?  $data->itemReturn->grn_no: ""',
    					
    		)
    		
        
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