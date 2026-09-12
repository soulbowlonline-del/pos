<!--  form code start here -->
<div class="form well">


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'credit-note-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'credit_number',array('class'=>'form-control','maxlength'=>255)); ?>


<?php echo $form->textFieldRow($model,'amt',array('class'=>'form-control')); ?>


<?php echo $form->textFieldRow($model,'amt_used',array('class'=>'form-control')); ?>





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

<script>
var batch = "<?php echo User::randomBarcode('11');?>";
$('#CreditNote_credit_number').val(batch);
</script>