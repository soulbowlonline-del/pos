<!--  form code start here -->
<div class="form well">


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'order-refund-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'qty',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'discount',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'discount_amt',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'total_amt',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'paid_amt',array('class'=>'span5')); ?>


<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions()); ?>


<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions()); ?>


<?php echo $form->textFieldRow($model,'city_id',array('class'=>'span5')); ?>


<?php echo $form->dropDownListRow($model, 'state_id',
			$model->getStatusOptions()); ?>


<?php echo $form->textFieldRow($model,'country_id',array('class'=>'span5')); ?>


<?php echo  '';$code = $this->richTextEditor() ;

					if ($code == 1) echo $form->html5EditorRow($model,'address', array('class'=>'span4', 'rows'=>5, 'height'=>'200', 'options'=>array('color'=>true)));

					else if ($code == 2) echo $form->redactorRow($model,'address', array('class'=>'span4', 'rows'=>5));

					else if ($code == 3) echo $form->ckEditorRow($model,'address', array('options'=>array('fullpage'=>'js:true', 'width'=>'640', 'resize_maxWidth'=>'640','resize_minWidth'=>'320')));

					else echo $form->textAreaRow($model,'address',  array('class'=>'span4', 'rows'=>5));; ?>


<?php echo  '';$code = $this->richTextEditor() ;

					if ($code == 1) echo $form->html5EditorRow($model,'note', array('class'=>'span4', 'rows'=>5, 'height'=>'200', 'options'=>array('color'=>true)));

					else if ($code == 2) echo $form->redactorRow($model,'note', array('class'=>'span4', 'rows'=>5));

					else if ($code == 3) echo $form->ckEditorRow($model,'note', array('options'=>array('fullpage'=>'js:true', 'width'=>'640', 'resize_maxWidth'=>'640','resize_minWidth'=>'320')));

					else echo $form->textAreaRow($model,'note',  array('class'=>'span4', 'rows'=>5));; ?>


<?php echo $form->datepickerRow($model, 'update_time',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'))
; ?>


<?php echo $form->textFieldRow($model,'order_id',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'customer_id',array('class'=>'span5')); ?>


<?php echo $form->textFieldRow($model,'updated_by',array('class'=>'span5')); ?>


<?php if ( count (OrderRefundItem::model()->findAllAttributes(null, true) ) > 0 ): ?>
		<label><?php echo GxHtml::encode($model->getRelationLabel('orderRefundItems')); ?></label>
	
		<?php echo $form->checkBoxListRow($model, 'orderRefundItems', GxHtml::encodeEx(GxHtml::listDataEx(OrderRefundItem::model()->findAllAttributes(null, true)), false, true)); ?>
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