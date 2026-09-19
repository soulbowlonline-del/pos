<?php
/**
 * Ported from protected/views/shift/_search.php.
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
	'id' => 'shift-form',
	'type'=>'horizontal',		
]); ; 
?>

	<div class="row">
		<?php echo $form->label($model, 'id'); ?>
		<?php echo $form->textField($model, 'id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'title'); ?>
		<?php echo $form->textField($model, 'title', ['maxlength' => 255]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'description'); ?>
		<?php $this->context->richTextEditor($model,'description'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'start_time'); ?>
		<?php $form->renderWidget('ext.widgets.clockpick.EClockpick', [
			'model' => $model,
			'attribute' => 'start_time',
			'value' => $model->start_time,
			'options'          =>[
			'starthour'    => 8,
			'endhour'      => 20,
			'showminutes'  => TRUE,
			'minutedivisions'   => 12,
			'military'      => TRUE,
			],
			'htmlOptions'      => ['size'=>5,'maxlength'=>5]
			]);
; ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'end_time'); ?>
		<?php $form->renderWidget('ext.widgets.clockpick.EClockpick', [
			'model' => $model,
			'attribute' => 'end_time',
			'value' => $model->end_time,
			'options'          =>[
			'starthour'    => 8,
			'endhour'      => 20,
			'showminutes'  => TRUE,
			'minutedivisions'   => 12,
			'military'      => TRUE,
			],
			'htmlOptions'      => ['size'=>5,'maxlength'=>5]
			]);
; ?>
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
