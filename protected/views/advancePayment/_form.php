<!--  form code start here -->
<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">

				<div class="box-header">
					<h3 class="box-title">Advance Payment</h3>
				</div>


				<div class="box-body">
					<div class="row">
						<div class="col-md-12">


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'advance-payment-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->datepickerRow($model, 'payment_date',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
							'options'=>array('format'=>'yyyy-mm-dd')))
; ?>


<?php echo $form->textFieldRow($model,'payment',array('class'=>'form-control')); ?>



<?php echo $form->dropDownListRow($model, 'vendor_id',$model->getAdvancePayVendorOptions(),array('class'=>'form-control')); ?>






	<div class="form-actions">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Save',
		)); ?>
	</div>

<?php $this->endWidget(); ?>

</div>
					</div>
				</div>


			</div>
		</div>
	</div>
</section>
<!-- form code ends here -->

<script>
$('#AdvancePayment_payment_date').datepicker({
    autoclose: true
});
</script>