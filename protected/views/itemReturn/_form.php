<!--  form code start here -->
<div class="form well">


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'item-return-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'credit_note_no',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'invoice_no',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'grn_no',array('class'=>'span5','maxlength'=>10)); ?>


<?php echo $form->textFieldRow($model,'bill_no',array('class'=>'span5')); ?>





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