<!--  form code start here -->
<div class="form well">


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'purchase-order-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'code',array('class'=>'span5','maxlength'=>255)); ?>


<?php echo $form->datepickerRow($model, 'start_date',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'))
; ?>


<?php echo $form->datepickerRow($model, 'end_date',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'))
; ?>


<?php echo $form->datepickerRow($model, 'receiving_date',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'))
; ?>


<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions()); ?>


<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions()); ?>


<?php echo $form->checkBoxRow($model, 'is_open_po'); ?>


<?php echo $form->checkBoxRow($model, 'is_po_received'); ?>


<?php echo  '';$code = $this->richTextEditor() ;

					if ($code == 1) echo $form->html5EditorRow($model,'remarks', array('class'=>'span4', 'rows'=>5, 'height'=>'200', 'options'=>array('color'=>true)));

					else if ($code == 2) echo $form->redactorRow($model,'remarks', array('class'=>'span4', 'rows'=>5));

					else if ($code == 3) echo $form->ckEditorRow($model,'remarks', array('options'=>array('fullpage'=>'js:true', 'width'=>'640', 'resize_maxWidth'=>'640','resize_minWidth'=>'320')));

					else echo $form->textAreaRow($model,'remarks',  array('class'=>'span4', 'rows'=>5));; ?>


<?php echo  '';$code = $this->richTextEditor() ;

					if ($code == 1) echo $form->html5EditorRow($model,'payment_terms', array('class'=>'span4', 'rows'=>5, 'height'=>'200', 'options'=>array('color'=>true)));

					else if ($code == 2) echo $form->redactorRow($model,'payment_terms', array('class'=>'span4', 'rows'=>5));

					else if ($code == 3) echo $form->ckEditorRow($model,'payment_terms', array('options'=>array('fullpage'=>'js:true', 'width'=>'640', 'resize_maxWidth'=>'640','resize_minWidth'=>'320')));

					else echo $form->textAreaRow($model,'payment_terms',  array('class'=>'span4', 'rows'=>5));; ?>


<?php echo $form->textFieldRow($model,'transport_mode',array('class'=>'span5','maxlength'=>255)); ?>


<?php echo $form->textFieldRow($model,'purchase_order_amount',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'charges_total_amount',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'discount_amount',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'frieght_charges',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'extra_charges',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'total_amount',array('class'=>'span5')); ?>


<?php echo $form->datepickerRow($model, 'update_time',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'))
; ?>


<?php echo $form->radioButtonListRow($model, 'updated_by', GxHtml::listDataEx(User::model()->findAllAttributes(null, true))); ?>


<?php echo $form->radioButtonListRow($model, 'outlet_id', GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true))); ?>


<?php echo $form->radioButtonListRow($model, 'vendor_id', GxHtml::listDataEx(Vendor::model()->findAllAttributes(null, true))); ?>


<?php echo $form->radioButtonListRow($model, 'mrn_id', GxHtml::listDataEx(Mrn::model()->findAllAttributes(null, true))); ?>


<?php echo $form->radioButtonListRow($model, 'organization_id', GxHtml::listDataEx(Organization::model()->findAllAttributes(null, true))); ?>


<?php if ( count (PurchaseBill::model()->findAllAttributes(null, true) ) > 0 ): ?>
		<label><?php echo GxHtml::encode($model->getRelationLabel('purchaseBills')); ?></label>
	
		<?php echo $form->checkBoxListRow($model, 'purchaseBills', GxHtml::encodeEx(GxHtml::listDataEx(PurchaseBill::model()->findAllAttributes(null, true)), false, true)); ?>
<?php endif; ?>	








<?php if ( count (PurchaseOrderDetail::model()->findAllAttributes(null, true) ) > 0 ): ?>
		<label><?php echo GxHtml::encode($model->getRelationLabel('purchaseOrderDetails')); ?></label>
	
		<?php echo $form->checkBoxListRow($model, 'purchaseOrderDetails', GxHtml::encodeEx(GxHtml::listDataEx(PurchaseOrderDetail::model()->findAllAttributes(null, true)), false, true)); ?>
<?php endif; ?>	



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