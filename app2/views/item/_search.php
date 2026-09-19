<?php
/**
 * Ported from protected/views/item/_search.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\ItemCategory;
use app\models\ItemCompany;
use app\models\ItemCompanyCategory;
use app\models\User;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\CJuiRadioButtonList;
?>
<div class="wide form">

<?php 	$form = ActiveForm::begin([
	'action' => Ui::to($this->context->route),
	'method' => 'get',
	'id' => 'item-form',
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
		<?php echo $form->label($model, 'item_code'); ?>
		<?php echo $form->textField($model, 'item_code', ['maxlength' => 255]); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'description'); ?>
		<?php $this->context->richTextEditor($model,'description'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'image_file'); ?>
		<?php $this->context->richTextEditor($model,'image_file'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'item_type'); ?>
		<?php echo $form->textField($model, 'item_type'); ?>
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
		<?php echo $form->label($model, 'is_tax'); ?>
		<?php echo $form->dropDownList($model, 'is_tax', ['0' => 'No', '1' => 'Yes'], ['prompt' => 'All']); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'is_discount'); ?>
		<?php echo $form->dropDownList($model, 'is_discount', ['0' => 'No', '1' => 'Yes'], ['prompt' => 'All']); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'category_id'); ?>
		<?php echo $form->dropDownList($model, 'category_id', Gx::listData(ItemCategory::class), ['prompt' => 'All']); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'sub_company_id'); ?>
		<?php echo $form->dropDownList($model, 'sub_company_id', Gx::listData(ItemCompanyCategory::class), ['prompt' => 'All']); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'company_id'); ?>
		<?php echo $form->dropDownList($model, 'company_id', Gx::listData(ItemCompany::class), ['prompt' => 'All']); ?>
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
