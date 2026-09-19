<?php
/**
 * Ported from protected/views/user/_search.php.
 */

use app\components\Ui;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\CJuiRadioButtonList;
?>
<div class="wide form">

<?php 	$form = ActiveForm::begin([
	'action' => Ui::to($this->context->route),
	'method' => 'get',
	'id' => 'user-form',
	'type'=>'horizontal',		
]); ; 
?>

	<div class="row">
		<?php echo $form->label($model, 'id'); ?>
		<?php echo $form->textField($model, 'id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'full_name'); ?>
		<?php echo $form->textField($model, 'full_name', ['maxlength' => 256]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'email'); ?>
		<?php echo $form->textField($model, 'email', ['maxlength' => 256]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'lat'); ?>
		<?php echo $form->textField($model, 'lat', ['maxlength' => 32]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'long'); ?>
		<?php echo $form->textField($model, 'long', ['maxlength' => 32]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'contact_no'); ?>
		<?php echo $form->textField($model, 'contact_no', ['maxlength' => 255]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'date_of_birth'); ?>
		<?php $form->renderWidget('zii.widgets.jui.CJuiDatePicker', [
			'model' => $model,
			'attribute' => 'date_of_birth',
			'value' => $model->date_of_birth,
			'options' => [
			'showButtonPanel' => true,
			'changeYear' => true,
			'dateFormat' => 'yy-mm-dd',
			],
			]);
; ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'about_me'); ?>
		<?php $this->context->richTextEditor($model,'about_me'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'address'); ?>
		<?php echo $form->textField($model, 'address', ['size'=>80,'maxlength' => 512]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'postal_code'); ?>
		<?php echo $form->textField($model, 'postal_code'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'country'); ?>
		<?php echo $form->textField($model, 'country', ['maxlength' => 128]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'city'); ?>
		<?php echo $form->textField($model, 'city', ['maxlength' => 128]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'state'); ?>
		<?php 
			echo CJuiRadioButtonList::widget([
			'model'=>$model,
			'attribute'=>'state',
			'data'=>$model->getStatusOptions(),
			]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'lang'); ?>
		<?php echo $form->textField($model, 'lang', ['maxlength' => 8]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'image_file'); ?>
		<?php echo $form->textField($model, 'image_file', ['size'=>80,'maxlength' => 512]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'is_dispatcher'); ?>
		<?php echo $form->textField($model, 'is_dispatcher'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'is_driver'); ?>
		<?php echo $form->textField($model, 'is_driver'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'role_id'); ?>
		<?php echo $form->textField($model, 'role_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'state_id'); ?>
		<?php 
			echo CJuiRadioButtonList::widget([
			'model'=>$model,
			'attribute'=>'state_id',
			'data'=>$model->getStatusOptions(),
			]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'type_id'); ?>
		<?php 
			echo CJuiRadioButtonList::widget([
			'model'=>$model,
			'attribute'=>'type_id',
			'data'=>$model->getTypeOptions(),
			]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'last_visit_time'); ?>
		<?php $form->renderWidget('zii.widgets.jui.CJuiDatePicker', [
			'model' => $model,
			'attribute' => 'last_visit_time',
			'value' => $model->last_visit_time,
			'options' => [
			'showButtonPanel' => true,
			'changeYear' => true,
			'dateFormat' => 'yy-mm-dd',
			],
			]);
; ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'last_action_time'); ?>
		<?php $form->renderWidget('zii.widgets.jui.CJuiDatePicker', [
			'model' => $model,
			'attribute' => 'last_action_time',
			'value' => $model->last_action_time,
			'options' => [
			'showButtonPanel' => true,
			'changeYear' => true,
			'dateFormat' => 'yy-mm-dd',
			],
			]);
; ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'activation_key'); ?>
		<?php echo $form->textField($model, 'activation_key', ['size'=>80,'maxlength' => 512]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'is_active'); ?>
		<?php echo $form->dropDownList($model, 'is_active', ['0' => 'No', '1' => 'Yes'], ['prompt' => 'All']); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'login_error_count'); ?>
		<?php echo $form->textField($model, 'login_error_count'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'create_user_id'); ?>
		<?php echo $form->textField($model, 'create_user_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'create_time'); ?>
		<?php $form->renderWidget('zii.widgets.jui.CJuiDatePicker', [
			'model' => $model,
			'attribute' => 'create_time',
			'value' => $model->create_time,
			'options' => [
			'showButtonPanel' => true,
			'changeYear' => true,
			'dateFormat' => 'yy-mm-dd',
			],
			]);
; ?>
	</div>


	<div class="form-actions">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		]); ?>
	</div>
<?php ActiveForm::end(); ?>

</div><!-- search-form -->
