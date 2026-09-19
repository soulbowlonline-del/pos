<?php
/**
 * Ported from protected/views/customer/_search.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\User;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\CJuiRadioButtonList;
?>
<div class="wide form">

<?php 	$form = ActiveForm::begin([
	'action' => Ui::to($this->context->route),
	'method' => 'get',
	'id' => 'customer-form',
	'type'=>'horizontal',		
]); ; 
?>

	<div class="row">
		<?php echo $form->label($model, 'id'); ?>
		<?php echo $form->textField($model, 'id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'name'); ?>
		<?php echo $form->textField($model, 'name', ['maxlength' => 255]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'email'); ?>
		<?php echo $form->textField($model, 'email', ['maxlength' => 255]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'fax'); ?>
		<?php echo $form->textField($model, 'fax', ['maxlength' => 255]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'address'); ?>
		<?php $this->context->richTextEditor($model,'address'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'city_id'); ?>
		<?php echo $form->textField($model, 'city_id'); ?>
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
		<?php echo $form->label($model, 'country_id'); ?>
		<?php echo $form->textField($model, 'country_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'zip_code'); ?>
		<?php echo $form->textField($model, 'zip_code'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'opening_balance'); ?>
		<?php echo $form->textField($model, 'opening_balance'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'credit_limit'); ?>
		<?php echo $form->textField($model, 'credit_limit'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'payment_days'); ?>
		<?php echo $form->textField($model, 'payment_days'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'contact_no'); ?>
		<?php echo $form->textField($model, 'contact_no'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'status'); ?>
		<?php 
			echo CJuiRadioButtonList::widget([
			'model'=>$model,
			'attribute'=>'status',
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

	<div class="row">
		<?php echo $form->label($model, 'update_time'); ?>
		<?php $form->renderWidget('zii.widgets.jui.CJuiDatePicker', [
			'model' => $model,
			'attribute' => 'update_time',
			'value' => $model->update_time,
			'options' => [
			'showButtonPanel' => true,
			'changeYear' => true,
			'dateFormat' => 'yy-mm-dd',
			],
			]);
; ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'create_user_id'); ?>
		<?php echo $form->dropDownList($model, 'create_user_id', Gx::listData(User::class), ['prompt' => 'All']); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'updated_by'); ?>
		<?php echo $form->dropDownList($model, 'updated_by', Gx::listData(User::class), ['prompt' => 'All']); ?>
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
