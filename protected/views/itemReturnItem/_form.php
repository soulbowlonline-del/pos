<!--  form code start here -->
<div class="form well">


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'item-return-item-form',
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


<?php echo $form->textFieldRow($model,'price',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'sale_rate',array('class'=>'span5','maxlength'=>10)); ?>


<?php echo $form->textFieldRow($model,'free',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'qty',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'discount',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'discount_amt',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'discount1',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'discount_amt1',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'cgst_per',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'sgst_per',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'cess_per',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'cgst_amt',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'sgst_amt',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'cess_amt',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'igst_per',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'igst_amt',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'tax_id',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'other_charge',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'total_amt',array('class'=>'span5','maxlength'=>10)); ?>


<?php echo $form->textFieldRow($model,'vendor_id',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'outlet_id',array('class'=>'span5')); ?>


<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions()); ?>


<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions()); ?>


<?php echo $form->textFieldRow($model,'return_id',array('class'=>'span5')); ?>


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