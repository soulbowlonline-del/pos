<?php
/**
 * Ported from protected/views/onlineOrder/_search.php.
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
	'id' => 'online-order-form',
	'type'=>'horizontal',		
]); ; 
?>

	<div class="row">
		<?php echo $form->label($model, 'id'); ?>
		<?php echo $form->textField($model, 'id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'order_id'); ?>
		<?php echo $form->textField($model, 'order_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'item_count'); ?>
		<?php echo $form->textField($model, 'item_count'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'grand_total'); ?>
		<?php echo $form->textField($model, 'grand_total'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'first_name'); ?>
		<?php echo $form->textField($model, 'first_name', ['maxlength' => 255]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'last_name'); ?>
		<?php echo $form->textField($model, 'last_name', ['maxlength' => 255]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'street'); ?>
		<?php echo $form->textField($model, 'street', ['maxlength' => 255]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'city'); ?>
		<?php echo $form->textField($model, 'city', ['maxlength' => 255]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'telephone'); ?>
		<?php echo $form->textField($model, 'telephone', ['maxlength' => 255]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'zip_code'); ?>
		<?php echo $form->textField($model, 'zip_code', ['maxlength' => 255]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'country'); ?>
		<?php echo $form->textField($model, 'country', ['maxlength' => 255]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'delivery_slot'); ?>
		<?php echo $form->textField($model, 'delivery_slot', ['maxlength' => 255]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'ship_name'); ?>
		<?php echo $form->textField($model, 'ship_name', ['maxlength' => 255]); ?>
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
		<?php echo $form->label($model, 'status'); ?>
		<?php 
			echo CJuiRadioButtonList::widget([
			'model'=>$model,
			'attribute'=>'status',
			'data'=>$model->getStatusOptions(),
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
		<?php echo $form->textField($model, 'create_user_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'updated_by'); ?>
		<?php echo $form->textField($model, 'updated_by'); ?>
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
