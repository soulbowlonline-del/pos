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
	$.fn.yiiGridView.update('purchase-bill-grid', {
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
<section class="content-header">

	<h1><?php echo 'B2B Grn Details'; ?></h1>
</section>



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
							'action' => Yii::app ()->createUrl ( 'purchaseBill/admin?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => array (
									'enctype' => 'multipart/form-data' 
							) 
					) );
					?>
<?php

				
					$cols = array (
							'transaction_type' => 'Transaction Type (NFET,RTGS)',
							'ben_code' => 'Beneficiary Code',
							'ben_acc_no' => 'Beneficiary Account Number',
							'instrument_amt' => 'Instrument Amount',
							'ben_name' => 'Beneficiary Name (Upto 40 character withput any special character)',
							'drawee_loc' => 'Drawee Location',
							'print_loc' => 'Print Location',
							'ben_add1' => 'Bene Address 1',
							'ben_add2' => 'Bene Address 2',
							'ben_add3' => 'Bene Address 3',
						    'ben_add4' => 'Bene Address 4',
							'ben_add5' => 'Bene Address 5',
							 'Inst_ref_no' => 'Instruction Reference Number',
							'customer_refrence_no' => 'Customer Reference Number(Any alpha numeric character upto 20)',
							'pay_detail1' => 'Payment details 1',
							'pay_detail2' => 'Payment details 2',
							'pay_detail3' => 'Payment details 3',
							'pay_detail4' => 'Payment details 4',
							'pay_detail5' => 'Payment details 5',
							'pay_detail6' => 'Payment details 6',
							'pay_detail7' => 'Payment details 7',
							'cheque_no' => 'Cheque Number',
							'chq' => 'Chq / Trn Date (DD/MM/YYYY)',
							'micr_no' => 'MICR Number',
							'ifsc_code' => 'IFSC Code',
							'bene_bank_name' => 'Bene Bank Name',
							'bene_branch_name' => 'Bene Bank Branch Name',
							'beneficiary_email' => 'Beneficiary email id' 
						
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
         <div class="box-header"><h3 class="box-title"><?php echo  'B2B GRN List'; ?></h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
  


<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'purchase-bill-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search($val = true),
	'filter' => $model,
		'pager'=>true,
		'afterAjaxUpdate'=>"function(){
                                                       $.datepicker.setDefaults($.datepicker.regional['en']);
                                                        $('#Projects_projStart').datepicker({'dateFormat': 'yy-mm-dd'});
		
                                                }",
	'columns' => array(
			'id',
		// 'grn_refrence_no',
			// 'bill_no',
			//'start_date',
			array('name'=>'start_date',
					'value'=>'$data->start_date',
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
			'end_date',
			/* array(
					'name' => 'total_amount',
					'value'=>'isset($data->total_amount)?$data->total_amount:""',
			
			), */
			array(
					'name'=>'vendor_id',
					'value'=>'GxHtml::valueEx($data->vendor)',
					//'filter'=>GxHtml::listDataEx(Vendor::model()->findAllAttributes(null, true)),
			),
		/* 	array(
					'name'=>'outlet_id',
					'value'=>'GxHtml::valueEx($data->outlet)',
					'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			), */
			'net_bill_amount',
			array(
						
					'header'=>'<a>Status</a>',
					'class'=>'CButtonColumn',
					'template' => '{view}',  //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
							'view'=>array(
								//	'visible'=>'$data->state_id=='.User::STATUS_INACTIVE,
									'url' =>'Yii::app()->controller->createUrl("B2bpurchaseBill/view", array("id" => $data->id))',
									'label'=>'view',
									'options'=>array('class'=>'view'),
			
							),
							
					)
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
$('#form-export').click(function(){
	$('#close_modal').trigger('click');
});
$(function(){
    $('.modal-body').slimScroll({
        height: '350px'
    });
});
</script>