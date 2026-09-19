<?php
/**
 * Ported from protected/views/order/_search.php.
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
	'id' => 'order-form',
	'type'=>'horizontal',		
]); ; 
?>

	<div class="row">
		<?php echo $form->label($model, 'id'); ?>
		<?php echo $form->textField($model, 'id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'bill_no'); ?>
		<?php echo $form->textField($model, 'bill_no'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'bill_date'); ?>
		<?php $form->renderWidget('zii.widgets.jui.CJuiDatePicker', [
			'model' => $model,
			'attribute' => 'bill_date',
			'value' => $model->bill_date,
			'options' => [
			'showButtonPanel' => true,
			'changeYear' => true,
			'dateFormat' => 'yy-mm-dd',
			],
			]);
; ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'mode_of_payment'); ?>
		<?php echo $form->textField($model, 'mode_of_payment', ['maxlength' => 255]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'mode_of_delivery'); ?>
		<?php echo $form->textField($model, 'mode_of_delivery', ['maxlength' => 255]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'qty'); ?>
		<?php echo $form->textField($model, 'qty'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'discount_amt'); ?>
		<?php echo $form->textField($model, 'discount_amt'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'total_amt'); ?>
		<?php echo $form->textField($model, 'total_amt'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'paid_amt'); ?>
		<?php echo $form->textField($model, 'paid_amt'); ?>
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
		<?php echo $form->label($model, 'outlet_id'); ?>
		<?php echo $form->textField($model, 'outlet_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'address'); ?>
		<?php $this->context->richTextEditor($model,'address'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'note'); ?>
		<?php $this->context->richTextEditor($model,'note'); ?>
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
		<?php echo $form->label($model, 'customer_id'); ?>
		<?php echo $form->textField($model, 'customer_id'); ?>
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
