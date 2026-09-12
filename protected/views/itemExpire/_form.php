<!--  form code start here -->
<div class="form well">


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'item-expire-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->radioButtonListRow($model, 'item_id', GxHtml::listDataEx(Item::model()->findAllAttributes(null, true))); ?>


<?php echo $form->radioButtonListRow($model, 'item_detail_id', GxHtml::listDataEx(ItemDetail::model()->findAllAttributes(null, true))); ?>


<?php echo $form->textFieldRow($model,'mrp',array('class'=>'span5','maxlength'=>10)); ?>


<?php echo $form->textFieldRow($model,'sale_rate',array('class'=>'span5','maxlength'=>10)); ?>


<?php echo $form->textFieldRow($model,'free',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'qty',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'total_amt',array('class'=>'span5','maxlength'=>10)); ?>


<?php echo $form->textFieldRow($model,'vendor_id',array('class'=>'span5')); ?>


<?php echo $form->radioButtonListRow($model, 'outlet_id', GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true))); ?>


<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions()); ?>


<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions()); ?>


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