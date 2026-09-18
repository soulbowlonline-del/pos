<?php
/**
 * Ported from protected/views/shift/_form.php.
 */

use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<!--  form code start here -->
<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">

				<div class="box-header">
					<h3 class="box-title">Create Shift</h3>
				</div>


				<div class="box-body">
					<div class="row">
						<div class="col-md-12">

<?php $form = ActiveForm::begin([
	'id' => 'shift-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'title',['class'=>'form-control','maxlength'=>255]); ?>


<?php echo $form->textAreaRow($model,'description',  ['class'=>'form-control', 'rows'=>5]);; ?>


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


<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions()); ?>



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