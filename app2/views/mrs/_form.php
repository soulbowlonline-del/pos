<?php
/**
 * Ported from protected/views/mrs/_form.php.
 */

use app\components\Gx;
use app\models\Organization;
use app\models\Outlet;
use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<!--  form code start here -->
<div class="form well">


<?php $form = ActiveForm::begin([
	'id' => 'mrs-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'code',['class'=>'form-control','maxlength'=>255]); ?>


<?php echo $form->datepickerRow($model, 'mrs_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>



<?php echo $form->datepickerRow($model, 'mrs_req_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>



<?php echo $form->textAreaRow($model,'remarks',  ['class'=>'form-control', 'rows'=>5]);; ?>


<?php echo $form->dropdownListRow($model, 'outlet_id', Gx::listData(Outlet::class),['class'=>'form-control']); ?>


<?php echo $form->dropdownListRow($model, 'organization_id', Gx::listData(Organization::class),['class'=>'form-control']); ?>



	<div class="form-actions">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Save',
		]); ?>
	</div>

<?php ActiveForm::end(); ?>

</div>
<!-- form code ends here -->