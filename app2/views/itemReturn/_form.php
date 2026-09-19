<?php
/**
 * Ported from protected/views/itemReturn/_form.php.
 */

use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<!--  form code start here -->
<div class="form well">


<?php $form = ActiveForm::begin([
	'id' => 'item-return-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'credit_note_no',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'invoice_no',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'grn_no',['class'=>'span5','maxlength'=>10]); ?>


<?php echo $form->textFieldRow($model,'bill_no',['class'=>'span5']); ?>





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