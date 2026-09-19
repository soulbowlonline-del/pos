<?php
/**
 * Ported from protected/views/order/_form.php.
 */

use app\components\Gx;
use app\models\OrderItem;
use app\models\OrderRefund;
use app\widgets\ActiveForm;
use app\widgets\Button;
use yii\helpers\Html;
?>
<!--  form code start here -->
<div class="form well">


<?php $form = ActiveForm::begin([
	'id' => 'order-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'bill_no',['class'=>'span5']); ?>


<?php echo $form->datepickerRow($model, 'bill_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>


<?php echo $form->textFieldRow($model,'mode_of_payment',['class'=>'span5','maxlength'=>255]); ?>


<?php echo $form->textFieldRow($model,'mode_of_delivery',['class'=>'span5','maxlength'=>255]); ?>


<?php echo $form->textFieldRow($model,'qty',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'discount_amt',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'total_amt',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'paid_amt',['class'=>'span5']); ?>


<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions()); ?>


<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions()); ?>


<?php echo $form->textFieldRow($model,'city_id',['class'=>'span5']); ?>


<?php echo $form->dropDownListRow($model, 'state_id',
			$model->getStatusOptions()); ?>


<?php echo $form->textFieldRow($model,'country_id',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'outlet_id',['class'=>'span5']); ?>


<?php echo  '';$code = $this->context->richTextEditor() ;

					if ($code == 1) echo $form->html5EditorRow($model,'address', ['class'=>'span4', 'rows'=>5, 'height'=>'200', 'options'=>['color'=>true]]);

					else if ($code == 2) echo $form->redactorRow($model,'address', ['class'=>'span4', 'rows'=>5]);

					else if ($code == 3) echo $form->ckEditorRow($model,'address', ['options'=>['fullpage'=>'js:true', 'width'=>'640', 'resize_maxWidth'=>'640','resize_minWidth'=>'320']]);

					else echo $form->textAreaRow($model,'address',  ['class'=>'span4', 'rows'=>5]);; ?>


<?php echo  '';$code = $this->context->richTextEditor() ;

					if ($code == 1) echo $form->html5EditorRow($model,'note', ['class'=>'span4', 'rows'=>5, 'height'=>'200', 'options'=>['color'=>true]]);

					else if ($code == 2) echo $form->redactorRow($model,'note', ['class'=>'span4', 'rows'=>5]);

					else if ($code == 3) echo $form->ckEditorRow($model,'note', ['options'=>['fullpage'=>'js:true', 'width'=>'640', 'resize_maxWidth'=>'640','resize_minWidth'=>'320']]);

					else echo $form->textAreaRow($model,'note',  ['class'=>'span4', 'rows'=>5]);; ?>


<?php echo $form->datepickerRow($model, 'update_time',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>


<?php echo $form->textFieldRow($model,'customer_id',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'updated_by',['class'=>'span5']); ?>







<?php if ( OrderItem::find()->exists() ): ?>
		<label><?php echo Html::encode($model->getRelationLabel('orderItems')); ?></label>
	
		<?php echo $form->checkBoxListRow($model, 'orderItems', Gx::encodeEx(Gx::listData(OrderItem::class), false, true)); ?>
<?php endif; ?>	


<?php if ( OrderRefund::find()->exists() ): ?>
		<label><?php echo Html::encode($model->getRelationLabel('orderRefunds')); ?></label>
	
		<?php echo $form->checkBoxListRow($model, 'orderRefunds', Gx::encodeEx(Gx::listData(OrderRefund::class), false, true)); ?>
<?php endif; ?>	



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