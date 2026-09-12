<!--  form code start here -->
<div class="form well">


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'mrs-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'code',array('class'=>'form-control','maxlength'=>255)); ?>


<?php echo $form->datepickerRow($model, 'mrs_date',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'))
; ?>



<?php echo $form->datepickerRow($model, 'mrs_req_date',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'))
; ?>



<?php echo $form->textAreaRow($model,'remarks',  array('class'=>'form-control', 'rows'=>5));; ?>


<?php echo $form->dropdownListRow($model, 'outlet_id', GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),array('class'=>'form-control')); ?>


<?php echo $form->dropdownListRow($model, 'organization_id', GxHtml::listDataEx(Organization::model()->findAllAttributes(null, true)),array('class'=>'form-control')); ?>



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