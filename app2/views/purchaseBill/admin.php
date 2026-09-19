<?php
/**
 * Ported from protected/views/purchaseBill/admin.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Vendor;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\CJuiDatePicker;
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

	<h1><?php echo 'RBI Report'; ?></h1>
	<a href="<?php echo Ui::to('purchaseBill/import');?>" class="btn btn-info" >Import Payment</a>
</section>

<ul class="nav nav-pills" id="yw2">
	<li><button type="button" class="btn btn-info export-btn" data-toggle="modal"
			data-target="#myModal">Export</button></li>
			
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
							'action' => Ui::to( 'purchaseBill/admin?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => [
									'enctype' => 'multipart/form-data' 
							] 
					] );
					?>
<?php

				
					$cols = [
							'transaction_type' => 'Transaction Type (NFET,RTGS)',
							'ben_code' => 'Beneficiary Code',
							'ben_acc_no' => 'Beneficiary Account Number',
							'instrument_amt' => 'Instrument Amount',
							'ben_name' => 'Beneficiary Name (Upto 40 character withput any special character)',
							'cheque_no' => 'Cheque Number',
							'drawee_loc' => 'Drawee Location',
							'print_loc' => 'Print Location',
							'ben_add1' => 'Bene Address 1',
							'ben_add3' => 'Bene Address 3',
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
							'cheque_no'=>'Cheque Number',
							'chq' => 'Chq / Trn Date (DD/MM/YYYY)',
							'micr_no' => 'Micr Code',
							'ifsc_code' => 'IFSC Code',
							'bene_bank_name' => 'Bene Bank Name',
							'bene_branch_name' => 'Bene Bank Branch Name',
							'beneficiary_email' => 'Beneficiary email id',
							/* 'pay_detail1' => 'Payment details 1',
							'pay_detail2' => 'Payment details 2',
						
							
							'transaction_status' => 'Drawee Location',
							'reject_reason' => 'Reject Reason', */
							
							
							//'utr_no' => 'UTR no for RTGS',
							 
						
							
						
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
         <div class="box-header"><h3 class="box-title"><?php echo  'RBI Report'; ?></h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
  


<?php 

echo GridView::widget([
	'id' => 'purchase-bill-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
		'pager'=>true,
		'afterAjaxUpdate'=>"function(){
                                                       $.datepicker.setDefaults($.datepicker.regional['en']);
                                                        $('#Projects_projStart').datepicker({'dateFormat': 'yy-mm-dd'});
		
                                                }",
	'columns' => [
			
			[
					'header' => '<a>Payment details 1</a>',
					'attribute' => 'grn_refrence_no',
					'value' => function ($data, $key, $index) { return "Gr-".$data->grn_refrence_no; },
			
			],
		[
					'header' => '<a>Beneficiary Account Number</a>',
					'value' => function ($data, $key, $index) { return isset($data->vendor)?$data->vendor->acc_no:""; },
				
			],
			[
					'header' => '<a>Instrument Amount</a>',
					'attribute' => 'bill_amount',
					'value' => function ($data, $key, $index) { return isset($data->net_bill_amount)?$data->net_bill_amount:""; },
			
			],
			[
					'header' => '<a>Beneficiary Name</a>',
					'attribute' => 'vendor_id',
					'value' => function ($data, $key, $index) { return isset($data->vendor)?$data->vendor->name:""; },
					'filter' => Gx::listData(Vendor::class)
						
			],
			
			[
					'header' => 'Customer Reference Number</a>',
					'attribute' => 'bill_no',
					'value' => function ($data, $key, $index) { return isset($data->bill_no)?$data->bill_no:""; },
			
			],
			[
					'header' => '<a>Chq / Trn Date (DD/MM/YYYY)</a>',
					'attribute' => 'start_date',
					'value' => function ($data, $key, $index) { return isset($data->start_date)?$data->getChqDate():""; },
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
					'header' => '<a>IFSC Code</a>',
					'value' => function ($data, $key, $index) { return isset($data->vendor)?$data->vendor->ifsc:""; },
			
			],
			[
					'header' => '<a>Bene Bank Name</a>',
					'value' => function ($data, $key, $index) { return isset($data->vendor)?$data->vendor->bank_name:""; },
						
			],
			[
					'header' => '<a>Bene Bank Branch Name</a>',
					'value' => function ($data, $key, $index) { return isset($data->vendor)?$data->vendor->bank_name:""; },
			
			],
			[
					'header' => '<a>Beneficiary email id</a>',
					'value' => function ($data, $key, $index) { return $data->getVendorEmail(); },
						
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
$('#form-export').click(function(){
	$('#close_modal').trigger('click');
});
$(function(){
    $('.modal-body').slimScroll({
        height: '350px'
    });
});
</script>