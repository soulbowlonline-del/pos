<?php
/**
 * Ported from protected/views/city/_form.php.
 */

use app\components\Gx;
use app\models\State;
use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<!--  form code start here -->
<div class="form well">


<?php $form = ActiveForm::begin([
	'id' => 'city-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'title',['class'=>'form-control','maxlength'=>255]); ?>

<?php echo $form->dropDownListRow($model, 'state_id', Gx::listData(State::find()->where(['status'=>State::STATUS_ACTIVE])->orderBy(['id' => SORT_DESC])->all()),['class'=>'form-control']); ?>


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
<!-- form code ends here -->