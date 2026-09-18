<?php
/**
 * Ported from protected/views/purchaseBillDetail/_form.php.
 */

use app\components\Gx;
use app\models\ItemDetail;
use app\models\Outlet;
use app\models\PurchaseBill;
use app\models\User;
use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<!--  form code start here -->
<div class="form well">


<?php $form = ActiveForm::begin([
	'id' => 'purchase-bill-detail-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'req_qty',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'bal_qty',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'approved_qty',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'mrp',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'price',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'discount',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'discount_amt',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'vat',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'other_charge',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'amount',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'sale_rate',['class'=>'span5']); ?>


<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions()); ?>


<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions()); ?>


<?php echo $form->textFieldRow($model,'charge_amount',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'extra_charges',['class'=>'span5']); ?>


<?php echo  '';$code = $this->context->richTextEditor() ;

					if ($code == 1) echo $form->html5EditorRow($model,'remarks', ['class'=>'span4', 'rows'=>5, 'height'=>'200', 'options'=>['color'=>true]]);

					else if ($code == 2) echo $form->redactorRow($model,'remarks', ['class'=>'span4', 'rows'=>5]);

					else if ($code == 3) echo $form->ckEditorRow($model,'remarks', ['options'=>['fullpage'=>'js:true', 'width'=>'640', 'resize_maxWidth'=>'640','resize_minWidth'=>'320']]);

					else echo $form->textAreaRow($model,'remarks',  ['class'=>'span4', 'rows'=>5]);; ?>


<?php echo $form->datepickerRow($model, 'update_time',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>


<?php echo $form->radioButtonListRow($model, 'updated_by', Gx::listData(User::class)); ?>


<?php echo $form->radioButtonListRow($model, 'item_detail_id', Gx::listData(ItemDetail::class)); ?>


<?php echo $form->radioButtonListRow($model, 'purchase_bill_id', Gx::listData(PurchaseBill::class)); ?>


<?php echo $form->radioButtonListRow($model, 'outlet_id', Gx::listData(Outlet::class)); ?>








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