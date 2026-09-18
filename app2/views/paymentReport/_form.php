<?php
/**
 * Ported from protected/views/paymentReport/_form.php.
 */

use app\components\Gx;
use app\models\User;
use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<!--  form code start here -->
<div class="form well">


<?php $form = ActiveForm::begin([
	'id' => 'payment-report-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'doc_no',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'chq_no',['class'=>'span5','maxlength'=>255]); ?>


<?php echo $form->textFieldRow($model,'comp_code',['class'=>'span5','maxlength'=>255]); ?>


<?php echo $form->textFieldRow($model,'house_bank',['class'=>'span5','maxlength'=>255]); ?>


<?php echo $form->textFieldRow($model,'hb_acct',['class'=>'span5','maxlength'=>255]); ?>


<?php echo $form->textFieldRow($model,'ben_acc_no',['class'=>'span5','maxlength'=>255]); ?>


<?php echo $form->textFieldRow($model,'ref_no',['class'=>'span5','maxlength'=>255]); ?>


<?php echo $form->textFieldRow($model,'amount',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'vendor_id',['class'=>'span5']); ?>


<?php echo $form->datepickerRow($model, 'run_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>


<?php echo $form->datepickerRow($model, 'inst_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>


<?php echo $form->datepickerRow($model, 'value_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>


<?php echo $form->textFieldRow($model,'pay_type',['class'=>'span5','maxlength'=>255]); ?>


<?php echo $form->textFieldRow($model,'pay_status',['class'=>'span5','maxlength'=>255]); ?>


<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions()); ?>


<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions()); ?>


<?php echo $form->datepickerRow($model, 'update_time',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>


<?php echo $form->radioButtonListRow($model, 'updated_by', Gx::listData(User::class)); ?>





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