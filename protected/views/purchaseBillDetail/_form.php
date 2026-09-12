<!--  form code start here -->
<div class="form well">


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'purchase-bill-detail-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'req_qty',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'bal_qty',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'approved_qty',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'mrp',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'price',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'discount',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'discount_amt',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'vat',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'other_charge',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'amount',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'sale_rate',array('class'=>'span5')); ?>


<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions()); ?>


<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions()); ?>


<?php echo $form->textFieldRow($model,'charge_amount',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'extra_charges',array('class'=>'span5')); ?>


<?php echo  '';$code = $this->richTextEditor() ;

					if ($code == 1) echo $form->html5EditorRow($model,'remarks', array('class'=>'span4', 'rows'=>5, 'height'=>'200', 'options'=>array('color'=>true)));

					else if ($code == 2) echo $form->redactorRow($model,'remarks', array('class'=>'span4', 'rows'=>5));

					else if ($code == 3) echo $form->ckEditorRow($model,'remarks', array('options'=>array('fullpage'=>'js:true', 'width'=>'640', 'resize_maxWidth'=>'640','resize_minWidth'=>'320')));

					else echo $form->textAreaRow($model,'remarks',  array('class'=>'span4', 'rows'=>5));; ?>


<?php echo $form->datepickerRow($model, 'update_time',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'))
; ?>


<?php echo $form->radioButtonListRow($model, 'updated_by', GxHtml::listDataEx(User::model()->findAllAttributes(null, true))); ?>


<?php echo $form->radioButtonListRow($model, 'item_detail_id', GxHtml::listDataEx(ItemDetail::model()->findAllAttributes(null, true))); ?>


<?php echo $form->radioButtonListRow($model, 'purchase_bill_id', GxHtml::listDataEx(PurchaseBill::model()->findAllAttributes(null, true))); ?>


<?php echo $form->radioButtonListRow($model, 'outlet_id', GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true))); ?>








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