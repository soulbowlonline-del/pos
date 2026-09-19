<?php
/**
 * Ported from protected/views/paymentMode/_form.php.
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
					<h3 class="box-title">Advance Payment</h3>
				</div>


				<div class="box-body">
					<div class="row">
						<div class="col-md-12">


<?php $form = ActiveForm::begin([
	'id' => 'payment-mode-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>


<?php echo $form->textFieldRow($model,'title',['class'=>'form-control','maxlength'=>255]); ?>


<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions()); ?>

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