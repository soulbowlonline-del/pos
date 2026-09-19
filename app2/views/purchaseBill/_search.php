<?php
/**
 * Ported from protected/views/purchaseBill/_search.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Organization;
use app\models\Outlet;
use app\models\PurchaseOrder;
use app\models\User;
use app\models\Vendor;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\CJuiRadioButtonList;
?>
<div class="wide form">

<?php 	$form = ActiveForm::begin([
	'action' => Ui::to($this->context->route),
	'method' => 'get',
	'id' => 'purchase-bill-form',
	'type'=>'horizontal',		
]); ; 
?>

	<div class="row">
		<?php echo $form->label($model, 'id'); ?>
		<?php echo $form->textField($model, 'id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'code'); ?>
		<?php echo $form->textField($model, 'code', ['maxlength' => 255]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'start_date'); ?>
		<?php $form->renderWidget('zii.widgets.jui.CJuiDatePicker', [
			'model' => $model,
			'attribute' => 'start_date',
			'value' => $model->start_date,
			'options' => [
			'showButtonPanel' => true,
			'changeYear' => true,
			'dateFormat' => 'yy-mm-dd',
			],
			]);
; ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'end_date'); ?>
		<?php $form->renderWidget('zii.widgets.jui.CJuiDatePicker', [
			'model' => $model,
			'attribute' => 'end_date',
			'value' => $model->end_date,
			'options' => [
			'showButtonPanel' => true,
			'changeYear' => true,
			'dateFormat' => 'yy-mm-dd',
			],
			]);
; ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'receiving_date'); ?>
		<?php $form->renderWidget('zii.widgets.jui.CJuiDatePicker', [
			'model' => $model,
			'attribute' => 'receiving_date',
			'value' => $model->receiving_date,
			'options' => [
			'showButtonPanel' => true,
			'changeYear' => true,
			'dateFormat' => 'yy-mm-dd',
			],
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
		<?php echo $form->label($model, 'is_open_po'); ?>
		<?php echo $form->dropDownList($model, 'is_open_po', ['0' => 'No', '1' => 'Yes'], ['prompt' => 'All']); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'is_po_received'); ?>
		<?php echo $form->dropDownList($model, 'is_po_received', ['0' => 'No', '1' => 'Yes'], ['prompt' => 'All']); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'remarks'); ?>
		<?php $this->context->richTextEditor($model,'remarks'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'payment_terms'); ?>
		<?php $this->context->richTextEditor($model,'payment_terms'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'transport_mode'); ?>
		<?php echo $form->textField($model, 'transport_mode', ['maxlength' => 255]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'purchase_order_amount'); ?>
		<?php echo $form->textField($model, 'purchase_order_amount'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'charges_total_amount'); ?>
		<?php echo $form->textField($model, 'charges_total_amount'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'discount_amount'); ?>
		<?php echo $form->textField($model, 'discount_amount'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'frieght_charges'); ?>
		<?php echo $form->textField($model, 'frieght_charges'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'extra_charges'); ?>
		<?php echo $form->textField($model, 'extra_charges'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'total_amount'); ?>
		<?php echo $form->textField($model, 'total_amount'); ?>
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

	<div class="row">
		<?php echo $form->label($model, 'outlet_id'); ?>
		<?php echo $form->dropDownList($model, 'outlet_id', Gx::listData(Outlet::class), ['prompt' => 'All']); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'vendor_id'); ?>
		<?php echo $form->dropDownList($model, 'vendor_id', Gx::listData(Vendor::class), ['prompt' => 'All']); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'purchase_order_id'); ?>
		<?php echo $form->dropDownList($model, 'purchase_order_id', Gx::listData(PurchaseOrder::class), ['prompt' => 'All']); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'organization_id'); ?>
		<?php echo $form->dropDownList($model, 'organization_id', Gx::listData(Organization::class), ['prompt' => 'All']); ?>
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
