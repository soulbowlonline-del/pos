<!--  form code start here -->
<div class="form well">


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'order-item-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'order_id',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'item_detail_id',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'qty',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'price',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'discount_id',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'discount_amt',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'tax_id',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'tax_amount',array('class'=>'span5')); ?>


<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions()); ?>


<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions()); ?>


<?php echo $form->datepickerRow($model, 'update_time',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'))
; ?>


<?php echo $form->textFieldRow($model,'updated_by',array('class'=>'span5')); ?>








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