<?php
/**
 * Ported from protected/views/itemExpireItem/_search.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Item;
use app\models\ItemDetail;
use app\models\ItemExpire;
use app\models\Outlet;
use app\models\User;
use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<div class="wide form">

<?php 	$form = ActiveForm::begin([
	'action' => Ui::to($this->route),
	'method' => 'get',
	'id' => 'item-expire-item-form',
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
		<?php echo $form->label($model, 'total_amt'); ?>
		<?php echo $form->textField($model, 'total_amt', ['maxlength' => 10]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'vendor_id'); ?>
		<?php echo $form->textField($model, 'vendor_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'outlet_id'); ?>
		<?php echo $form->dropDownList($model, 'outlet_id', Gx::listData(Outlet::class), ['prompt' => 'All']); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'status'); ?>
		<?php 
			$this->widget('ext.widgets.CJuiRadioButtonList', [
			'model'=>$model,
			'attribute'=>'status',
			'data'=>$model->getStatusOptions(),
			]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'type_id'); ?>
		<?php 
			$this->widget('ext.widgets.CJuiRadioButtonList', [
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
		<?php echo $form->label($model, 'item_expire_id'); ?>
		<?php echo $form->dropDownList($model, 'item_expire_id', Gx::listData(ItemExpire::class), ['prompt' => 'All']); ?>
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
