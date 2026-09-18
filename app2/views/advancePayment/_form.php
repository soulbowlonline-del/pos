<?php
/**
 * Ported from protected/views/advancePayment/_form.php.
 */

use app\widgets\ActiveForm;
use app\widgets\Button;
?>
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


<?php $form = ActiveForm::begin([
	'id' => 'advance-payment-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->datepickerRow($model, 'payment_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
							'options'=>['format'=>'yyyy-mm-dd']])
; ?>


<?php echo $form->textFieldRow($model,'payment',['class'=>'form-control']); ?>



<?php echo $form->dropDownListRow($model, 'vendor_id',$model->getAdvancePayVendorOptions(),['class'=>'form-control']); ?>






	<div class="form-actions">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Save',
		]); ?>
	</div>

<?php ActiveForm::end(); ?>

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