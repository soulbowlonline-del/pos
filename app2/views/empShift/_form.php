<?php
/**
 * Ported from protected/views/empShift/_form.php.
 */

use app\components\Gx;
use app\models\Emp;
use app\models\Shift;
use app\models\User;
use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<!--  form code start here -->
<div class="form well">


<?php $form = ActiveForm::begin([
	'id' => 'emp-shift-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->radioButtonListRow($model, 'emp_id', Gx::listData(Emp::class)); ?>


<?php echo $form->radioButtonListRow($model, 'shift_id', Gx::listData(Shift::class)); ?>


<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions()); ?>


<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions()); ?>


<?php echo $form->radioButtonListRow($model, 'updated_by', Gx::listData(User::class)); ?>







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