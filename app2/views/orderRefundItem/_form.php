<?php
/**
 * Ported from protected/views/orderRefundItem/_form.php.
 */

use app\components\Gx;
use app\models\Discount;
use app\models\ItemDetail;
use app\models\OrderRefund;
use app\models\User;
use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<!--  form code start here -->
<div class="form well">


<?php $form = ActiveForm::begin([
	'id' => 'order-refund-item-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->radioButtonListRow($model, 'order_refund_id', Gx::listData(OrderRefund::class)); ?>


<?php echo $form->radioButtonListRow($model, 'item_detail_id', Gx::listData(ItemDetail::class)); ?>


<?php echo $form->textFieldRow($model,'qty',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'price',['class'=>'span5']); ?>


<?php echo $form->radioButtonListRow($model, 'discount_id', Gx::listData(Discount::class)); ?>


<?php echo $form->textFieldRow($model,'discount_amt',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'tax_id',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'tax_amt',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'order_discount',['class'=>'span5']); ?>


<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions()); ?>


<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions()); ?>


<?php echo $form->datepickerRow($model, 'update_time',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>


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