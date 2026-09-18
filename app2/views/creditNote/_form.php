<?php
/**
 * Ported from protected/views/creditNote/_form.php.
 */

use app\models\User;
use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<!--  form code start here -->
<div class="form well">


<?php $form = ActiveForm::begin([
	'id' => 'credit-note-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'credit_number',['class'=>'form-control','maxlength'=>255]); ?>


<?php echo $form->textFieldRow($model,'amt',['class'=>'form-control']); ?>


<?php echo $form->textFieldRow($model,'amt_used',['class'=>'form-control']); ?>





	<div class="form-actions">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Save',
		]); ?>
	</div>

<?php ActiveForm::end(); ?>

</div>
<!-- form code ends here -->

<script>
var batch = "<?php echo User::randomBarcode('11');?>";
$('#CreditNote_credit_number').val(batch);
</script>