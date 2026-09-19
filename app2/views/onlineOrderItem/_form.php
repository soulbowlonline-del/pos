<?php
/**
 * Ported from protected/views/onlineOrderItem/_form.php.
 */

use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<!--  form code start here -->
<div class="form well">


<?php $form = ActiveForm::begin([
	'id' => 'online-order-item-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'name',['class'=>'span5','maxlength'=>255]); ?>


<?php echo $form->textFieldRow($model,'qty',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'price',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'total',['class'=>'span5']); ?>


<?php echo  '';$code = $this->context->richTextEditor() ;

					if ($code == 1) echo $form->html5EditorRow($model,'image_url', ['class'=>'span4', 'rows'=>5, 'height'=>'200', 'options'=>['color'=>true]]);

					else if ($code == 2) echo $form->redactorRow($model,'image_url', ['class'=>'span4', 'rows'=>5]);

					else if ($code == 3) echo $form->ckEditorRow($model,'image_url', ['options'=>['fullpage'=>'js:true', 'width'=>'640', 'resize_maxWidth'=>'640','resize_minWidth'=>'320']]);

					else echo $form->textAreaRow($model,'image_url',  ['class'=>'span4', 'rows'=>5]);; ?>


<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions()); ?>


<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions()); ?>


<?php echo $form->datepickerRow($model, 'update_time',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>


<?php echo $form->textFieldRow($model,'updated_by',['class'=>'span5']); ?>



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