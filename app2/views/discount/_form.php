<?php
/**
 * Ported from protected/views/discount/_form.php.
 */

use app\models\Discount;
use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">

				<div class="box-header">
					<h3 class="box-title">Discount</h3>
				</div>


				<div class="box-body">
					<div class="row">
						<div class="col-md-12">



<?php $form = ActiveForm::begin([
	'id' => 'discount-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'title',['class'=>'form-control','maxlength'=>255]); ?>

<?php echo $form->textFieldRow($model,'applicable_amt',['class'=>'form-control']); ?>
<?php echo $form->dropDownListRow($model, 'discount_type',
			$model->getDiscountTypeOptions(),['class'=>'form-control']); ?>
			<div id="items" style="display:none">
			<?php echo $form->dropDownListRow($model, 'item_detail_id',$model->getItemOptions(),['multiple'=>'multiple']); ?></div>
<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions(),['class'=>'form-control']); ?>



<?php echo $form->textFieldRow($model,'amount',['class'=>'form-control']); ?>


<?php echo $form->datepickerRow($model, 'start_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>


<?php echo $form->datepickerRow($model, 'end_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>
<div class="form-group ">
		<label for="discount_time_dependent" class="control-label col-md-3 required">
			</label>
		<div class="col-md-9">
		<?php echo $form->checkBoxRow($model, 'is_time_dependent'); ?></div>
	</div>

<div id="time" style="display:none">
<?php

echo $form->timepickerRow ( $model, 'start_time', [
		'hint' => 'Click inside! to select a time.',
		'prepend' => '<i class="icon-clock"></i>' 
] );
?>
<?php

echo $form->timepickerRow ( $model, 'end_time', [
		'hint' => 'Click inside! to select a time.',
		'prepend' => '<i class="icon-clock"></i>' 
] );
?>
</div>

<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions(),['class'=>'form-control']); ?>




	<div class="form-actions">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Save',
		]); ?>
	</div>

<?php ActiveForm::end(); ?>

</div>
					</div>
				</div>


			</div>
		</div>
	</div>
</section>
<!-- form code ends here -->

<script>
$('#Discount_is_time_dependent').click(function(){
    if ($('#Discount_is_time_dependent').attr('checked')) {
      $('#time').show();
    }else{
    	  $('#time').hide();
    }
});
<?php if($model->discount_type != null && $model->discount_type == Discount::DISCOUNT_ITEM){?>
$('#items').show();

<?php }?>

<?php if($model->start_time != '00:00:00'){?>

$('#time').show();
$('#Discount_is_time_dependent').prop('checked', true);

$('#Discount_discount_type').change(function(){
	var val = $('#Discount_discount_type').val();
	if(val == 1){
		$('#items').show();
	}else{
		$('#items').hide();
	}
});
<?php }?>
</script>

