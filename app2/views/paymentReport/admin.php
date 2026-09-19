<?php
/**
 * Ported from protected/views/paymentReport/admin.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\PaymentReport;
use app\models\User;
use app\widgets\ActiveForm;
use app\widgets\Button;
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
	$.fn.yiiGridView.update('payment-report-grid', {
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

	<h1><?php echo 'Payment Report'; ?></h1>
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
							'action' => Ui::to( 'paymentReport/admin?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => [
									'enctype' => 'multipart/form-data' 
							] 
					] );
					?>
<?php

$cols = [
							'doc_no'=>'DocNo',
							'pay_type'=>'Pay Type',
							'chq_no'=>'Chq No.',
							'comp_code'=>'Comp Code',
							'house_bank'=>'House Bank',
							'hb_acct'=>'HB Acct',
							'vendor_id'=>'Vendor Name',
							'ben_acc_no'=>'Bene Acct No',
							'run_date'=>'Run Dt',
							'inst_date'=>'Inst.Dt',
							'value_date'=>'Value Dt',
							'amount'=>'Amt',
							'ref_no'=>'Trans.Ref.No',
							'pay_status'=>'Status'
						
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
         <div class="box-header"><h3 class="box-title"><?php echo  'Payment Report'; ?></h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
  




<?php echo GridView::widget([
	'id' => 'payment-report-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => [
		'id',
		'doc_no',
		'chq_no',
		'comp_code',
		'house_bank',
		'hb_acct',
		
		'ben_acc_no',
		'ref_no',
		'amount',
		'vendor_id',
		'run_date',
		'inst_date',
		'value_date',
		[
				'attribute' => 'pay_type',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->pay_type); },
				'filter'=>PaymentReport::getTypeOptions(),
				],
		[
				'attribute' => 'pay_status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->pay_status); },
				'filter'=>PaymentReport::getStatusOptions(),
				],
	/*	array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>PaymentReport::getTypeOptions(),
				),
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>PaymentReport::getStatusOptions(),
				),
		'update_time',
		array(
			'attribute' =>'updated_by',
			'value' => function ($data, $key, $index) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
		
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