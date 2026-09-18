<?php
/**
 * Ported from protected/views/purchaseOrder/_form.php.
 */

use app\components\Gx;
use app\models\Mrn;
use app\models\Organization;
use app\models\Outlet;
use app\models\PurchaseBill;
use app\models\PurchaseOrderDetail;
use app\models\User;
use app\models\Vendor;
use app\widgets\ActiveForm;
use app\widgets\Button;
use yii\helpers\Html;
?>
<!--  form code start here -->
<div class="form well">


<?php $form = ActiveForm::begin([
	'id' => 'purchase-order-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'code',['class'=>'span5','maxlength'=>255]); ?>


<?php echo $form->datepickerRow($model, 'start_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>


<?php echo $form->datepickerRow($model, 'end_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>


<?php echo $form->datepickerRow($model, 'receiving_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>


<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions()); ?>


<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions()); ?>


<?php echo $form->checkBoxRow($model, 'is_open_po'); ?>


<?php echo $form->checkBoxRow($model, 'is_po_received'); ?>


<?php echo  '';$code = $this->context->richTextEditor() ;

					if ($code == 1) echo $form->html5EditorRow($model,'remarks', ['class'=>'span4', 'rows'=>5, 'height'=>'200', 'options'=>['color'=>true]]);

					else if ($code == 2) echo $form->redactorRow($model,'remarks', ['class'=>'span4', 'rows'=>5]);

					else if ($code == 3) echo $form->ckEditorRow($model,'remarks', ['options'=>['fullpage'=>'js:true', 'width'=>'640', 'resize_maxWidth'=>'640','resize_minWidth'=>'320']]);

					else echo $form->textAreaRow($model,'remarks',  ['class'=>'span4', 'rows'=>5]);; ?>


<?php echo  '';$code = $this->context->richTextEditor() ;

					if ($code == 1) echo $form->html5EditorRow($model,'payment_terms', ['class'=>'span4', 'rows'=>5, 'height'=>'200', 'options'=>['color'=>true]]);

					else if ($code == 2) echo $form->redactorRow($model,'payment_terms', ['class'=>'span4', 'rows'=>5]);

					else if ($code == 3) echo $form->ckEditorRow($model,'payment_terms', ['options'=>['fullpage'=>'js:true', 'width'=>'640', 'resize_maxWidth'=>'640','resize_minWidth'=>'320']]);

					else echo $form->textAreaRow($model,'payment_terms',  ['class'=>'span4', 'rows'=>5]);; ?>


<?php echo $form->textFieldRow($model,'transport_mode',['class'=>'span5','maxlength'=>255]); ?>


<?php echo $form->textFieldRow($model,'purchase_order_amount',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'charges_total_amount',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'discount_amount',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'frieght_charges',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'extra_charges',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'total_amount',['class'=>'span5']); ?>


<?php echo $form->datepickerRow($model, 'update_time',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>


<?php echo $form->radioButtonListRow($model, 'updated_by', Gx::listData(User::class)); ?>


<?php echo $form->radioButtonListRow($model, 'outlet_id', Gx::listData(Outlet::class)); ?>


<?php echo $form->radioButtonListRow($model, 'vendor_id', Gx::listData(Vendor::class)); ?>


<?php echo $form->radioButtonListRow($model, 'mrn_id', Gx::listData(Mrn::class)); ?>


<?php echo $form->radioButtonListRow($model, 'organization_id', Gx::listData(Organization::class)); ?>


<?php if ( count (PurchaseBill::find()->all() ) > 0 ): ?>
		<label><?php echo Html::encode($model->getRelationLabel('purchaseBills')); ?></label>
	
		<?php echo $form->checkBoxListRow($model, 'purchaseBills', Gx::encodeEx(Gx::listData(PurchaseBill::class), false, true)); ?>
<?php endif; ?>	








<?php if ( count (PurchaseOrderDetail::find()->all() ) > 0 ): ?>
		<label><?php echo Html::encode($model->getRelationLabel('purchaseOrderDetails')); ?></label>
	
		<?php echo $form->checkBoxListRow($model, 'purchaseOrderDetails', Gx::encodeEx(Gx::listData(PurchaseOrderDetail::class), false, true)); ?>
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