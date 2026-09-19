<?php
/**
 * Ported from protected/views/purchaseBillDetail/list.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Outlet;
use app\models\User;
use app\models\Vendor;
use app\widgets\ActionColumn;
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

	<h1><?php echo 'Grn Details'; ?></h1>
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
         <div class="box-header"><h3 class="box-title"><?php echo  'GRN List'; ?></h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
  


<?php echo GridView::widget([
	'id' => 'purchase-bill-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search($val = true),
	'filter' => $model,
		'pager'=>true,
		'afterAjaxUpdate'=>"function(){
                                                       $.datepicker.setDefaults($.datepicker.regional['en']);
                                                        $('#Projects_projStart').datepicker({'dateFormat': 'yy-mm-dd'});
		
                                                }",
	'columns' => [
			'id',
		'grn_refrence_no',
			'bill_no',
			//'start_date',
			['attribute' =>'start_date',
					'value' => function ($data, $key, $index) { return $data->start_date; },
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
			'end_date',
			/* array(
					'attribute' => 'total_amount',
					'value' => function ($data, $key, $index) { return isset($data->total_amount)?$data->total_amount:""; },
			
			), */
			[
					'attribute' =>'vendor_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->vendor); },
					//'filter'=>Gx::listData(Vendor::class),
			],
		/* 	array(
					'attribute' =>'outlet_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->outlet); },
					'filter'=>Gx::listData(Outlet::class),
			), */
			'net_bill_amount',
			[
						
					'header'=>'<a>Status</a>',
					'class' => ActionColumn::class,
					'template' => '{view}',  //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					'buttons'=>[
							'view'=>[
								//	'visible' => function ($data) { return $data->state_id==User::STATUS_INACTIVE; },
									'url' => function ($data) { return Ui::to("purchaseBill/view", ["id" => $data->id]); },
									'label'=>'view',
									'options'=>['class'=>'view'],
			
							],
							
					]
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