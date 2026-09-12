<!--  form code start here -->
<div class="form well">


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'payment-report-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'doc_no',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'chq_no',array('class'=>'span5','maxlength'=>255)); ?>


<?php echo $form->textFieldRow($model,'comp_code',array('class'=>'span5','maxlength'=>255)); ?>


<?php echo $form->textFieldRow($model,'house_bank',array('class'=>'span5','maxlength'=>255)); ?>


<?php echo $form->textFieldRow($model,'hb_acct',array('class'=>'span5','maxlength'=>255)); ?>


<?php echo $form->textFieldRow($model,'ben_acc_no',array('class'=>'span5','maxlength'=>255)); ?>


<?php echo $form->textFieldRow($model,'ref_no',array('class'=>'span5','maxlength'=>255)); ?>


<?php echo $form->textFieldRow($model,'amount',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'vendor_id',array('class'=>'span5')); ?>


<?php echo $form->datepickerRow($model, 'run_date',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'))
; ?>


<?php echo $form->datepickerRow($model, 'inst_date',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'))
; ?>


<?php echo $form->datepickerRow($model, 'value_date',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'))
; ?>


<?php echo $form->textFieldRow($model,'pay_type',array('class'=>'span5','maxlength'=>255)); ?>


<?php echo $form->textFieldRow($model,'pay_status',array('class'=>'span5','maxlength'=>255)); ?>


<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions()); ?>


<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions()); ?>


<?php echo $form->datepickerRow($model, 'update_time',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'))
; ?>


<?php echo $form->radioButtonListRow($model, 'updated_by', GxHtml::listDataEx(User::model()->findAllAttributes(null, true))); ?>





	<div class="form-actions">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Save',
		)); ?>
	</div>

<?php $this->endWidget(); ?>

</div>
<!-- form code ends here -->