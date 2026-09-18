<?php
/**
 * Ported from protected/views/orderItem/_form.php.
 */

use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<!--  form code start here -->
<div class="form well">


<?php $form = ActiveForm::begin([
	'id' => 'order-item-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'order_id',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'item_detail_id',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'qty',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'price',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'discount_id',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'discount_amt',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'tax_id',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'tax_amount',['class'=>'span5']); ?>


<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions()); ?>


<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions()); ?>


<?php echo $form->datepickerRow($model, 'update_time',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>


<?php echo $form->textFieldRow($model,'updated_by',['class'=>'span5']); ?>








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