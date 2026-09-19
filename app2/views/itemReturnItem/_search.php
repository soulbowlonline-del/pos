<?php
/**
 * Ported from protected/views/itemReturnItem/_search.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Item;
use app\models\ItemDetail;
use app\models\User;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\CJuiRadioButtonList;
?>
<div class="wide form">

<?php 	$form = ActiveForm::begin([
	'action' => Ui::to($this->context->route),
	'method' => 'get',
	'id' => 'item-return-item-form',
	'type'=>'horizontal',		
]); ; 
?>

	<div class="row">
		<?php echo $form->label($model, 'id'); ?>
		<?php echo $form->textField($model, 'id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'item_id'); ?>
		<?php echo $form->dropDownList($model, 'item_id', Gx::listData(Item::class), ['prompt' => 'All']); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'item_detail_id'); ?>
		<?php echo $form->dropDownList($model, 'item_detail_id', Gx::listData(ItemDetail::class), ['prompt' => 'All']); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'mrp'); ?>
		<?php echo $form->textField($model, 'mrp', ['maxlength' => 10]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'price'); ?>
		<?php echo $form->textField($model, 'price'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'sale_rate'); ?>
		<?php echo $form->textField($model, 'sale_rate', ['maxlength' => 10]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'free'); ?>
		<?php echo $form->textField($model, 'free'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'qty'); ?>
		<?php echo $form->textField($model, 'qty'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'discount'); ?>
		<?php echo $form->textField($model, 'discount'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'discount_amt'); ?>
		<?php echo $form->textField($model, 'discount_amt'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'discount1'); ?>
		<?php echo $form->textField($model, 'discount1'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'discount_amt1'); ?>
		<?php echo $form->textField($model, 'discount_amt1'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'cgst_per'); ?>
		<?php echo $form->textField($model, 'cgst_per'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'sgst_per'); ?>
		<?php echo $form->textField($model, 'sgst_per'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'cess_per'); ?>
		<?php echo $form->textField($model, 'cess_per'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'cgst_amt'); ?>
		<?php echo $form->textField($model, 'cgst_amt'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'sgst_amt'); ?>
		<?php echo $form->textField($model, 'sgst_amt'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'cess_amt'); ?>
		<?php echo $form->textField($model, 'cess_amt'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'igst_per'); ?>
		<?php echo $form->textField($model, 'igst_per'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'igst_amt'); ?>
		<?php echo $form->textField($model, 'igst_amt'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'tax_id'); ?>
		<?php echo $form->textField($model, 'tax_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'other_charge'); ?>
		<?php echo $form->textField($model, 'other_charge'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'total_amt'); ?>
		<?php echo $form->textField($model, 'total_amt', ['maxlength' => 10]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'vendor_id'); ?>
		<?php echo $form->textField($model, 'vendor_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'outlet_id'); ?>
		<?php echo $form->textField($model, 'outlet_id'); ?>
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
		<?php echo $form->label($model, 'return_id'); ?>
		<?php echo $form->textField($model, 'return_id'); ?>
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
