<?php
/**
 * Ported from protected/views/advanceLogs/_form.php.
 */

use app\components\Gx;
use app\models\AdvancePayment;
use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<!--  form code start here -->
<div class="form well">


<?php $form = ActiveForm::begin([
	'id' => 'advance-logs-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'amount',['class'=>'span5']); ?>


<?php echo $form->radioButtonListRow($model, 'advance_payment_id', Gx::listData(AdvancePayment::class)); ?>


<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions()); ?>


<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions()); ?>


<?php echo $form->datepickerRow($model, 'update_time',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>




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