<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">

				<div class="box-header">
					<h3 class="box-title">Tax</h3>
				</div>


				<div class="box-body">
					<div class="row">
						<div class="col-md-12">


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'tax-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'title',array('class'=>'form-control','maxlength'=>255)); ?>


<?php echo $form->textFieldRow($model,'tax_val1',array('class'=>'form-control')); ?>


<?php echo $form->textFieldRow($model,'tax_val2',array('class'=>'form-control')); ?>
<?php echo $form->textFieldRow($model,'tax_val3',array('class'=>'form-control')); ?>
<?php echo $form->textFieldRow($model,'tax_val4',array('class'=>'form-control')); ?>
<?php echo $form->textFieldRow($model,'hrn_code',array('class'=>'form-control','readOnly'=>true)); ?>
<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions(),array('class'=>'form-control')); ?>


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
					</div>
				</div>


			</div>
		</div>
	</div>
</section>
<!-- form code ends here -->

<script>
$('#Tax_tax_val1').change(function(){
	calculateTax();
});
$('#Tax_tax_val2').change(function(){
	calculateTax();
});
$('#Tax_tax_val3').change(function(){
	calculateTax();
});
$('#Tax_tax_val4').change(function(){
	calculateTax();
});
function calculateTax(){
	var val1 = $('#Tax_tax_val1').val();
	if(val1 == ''){
		val1 = '0.00';
	}
	var val2 = $('#Tax_tax_val2').val();
	if(val2 == ''){
		val2 = '0.00';
	}
	var val3 = $('#Tax_tax_val3').val();
	if(val3 == ''){
		val3 = '0.00';
	}
	var val4 = $('#Tax_tax_val4').val();
	if(val4 == ''){
		val4 = '0.00';
	}

	var total_val = parseFloat(val1) +  parseFloat(val2) +  parseFloat(val3)+  parseFloat(val4);
	$('#Tax_hrn_code').val(total_val);
}
</script>