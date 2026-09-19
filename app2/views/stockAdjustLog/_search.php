<?php
/**
 * Ported from protected/views/stockAdjustLog/_search.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Item;
use app\models\ItemDetail;
use app\models\Outlet;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\CJuiRadioButtonList;
?>
<div class="wide form">

<?php 	$form = ActiveForm::begin([
	'action' => Ui::to($this->context->route),
	'method' => 'get',
	'id' => 'stock-adjust-log-form',
	'type'=>'horizontal',		
]); ; 
?>

	<div class="row">
		<?php echo $form->label($model, 'id'); ?>
		<?php echo $form->textField($model, 'id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'date'); ?>
		<?php $form->renderWidget('zii.widgets.jui.CJuiDatePicker', [
			'model' => $model,
			'attribute' => 'date',
			'value' => $model->date,
			'options' => [
			'showButtonPanel' => true,
			'changeYear' => true,
			'dateFormat' => 'yy-mm-dd',
			],
			]);
; ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'item_detail_id'); ?>
		<?php echo $form->dropDownList($model, 'item_detail_id', Gx::listData(ItemDetail::class), ['prompt' => 'All']); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'item_id'); ?>
		<?php echo $form->dropDownList($model, 'item_id', Gx::listData(Item::class), ['prompt' => 'All']); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'mrp'); ?>
		<?php echo $form->textField($model, 'mrp'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'current_stock'); ?>
		<?php echo $form->textField($model, 'current_stock'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'actual_stock'); ?>
		<?php echo $form->textField($model, 'actual_stock'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'adjusted'); ?>
		<?php echo $form->textField($model, 'adjusted'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'outlet_id'); ?>
		<?php echo $form->dropDownList($model, 'outlet_id', Gx::listData(Outlet::class), ['prompt' => 'All']); ?>
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
		<?php echo $form->label($model, 'status'); ?>
		<?php 
			echo CJuiRadioButtonList::widget([
			'model'=>$model,
			'attribute'=>'status',
			'data'=>$model->getStatusOptions(),
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


	<div class="form-actions">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		]); ?>
	</div>
<?php ActiveForm::end(); ?>

</div><!-- search-form -->
