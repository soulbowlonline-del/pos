<!--  form code start here -->
<div class="form well">


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'city-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'title',array('class'=>'form-control','maxlength'=>255)); ?>

<?php echo $form->dropDownListRow($model, 'state_id', GxHtml::listDataEx(State::model()->findAllByAttributes(array('status'=>State::STATUS_ACTIVE))),array('class'=>'form-control')); ?>


<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions(),array('class'=>'form-control')); ?>

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