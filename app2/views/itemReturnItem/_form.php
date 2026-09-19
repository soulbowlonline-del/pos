<?php
/**
 * Ported from protected/views/itemReturnItem/_form.php.
 */

use app\components\Gx;
use app\models\Item;
use app\models\ItemDetail;
use app\models\User;
use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<!--  form code start here -->
<div class="form well">


<?php $form = ActiveForm::begin([
	'id' => 'item-return-item-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->radioButtonListRow($model, 'item_id', Gx::listData(Item::class)); ?>


<?php echo $form->radioButtonListRow($model, 'item_detail_id', Gx::listData(ItemDetail::class)); ?>


<?php echo $form->textFieldRow($model,'mrp',['class'=>'span5','maxlength'=>10]); ?>


<?php echo $form->textFieldRow($model,'price',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'sale_rate',['class'=>'span5','maxlength'=>10]); ?>


<?php echo $form->textFieldRow($model,'free',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'qty',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'discount',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'discount_amt',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'discount1',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'discount_amt1',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'cgst_per',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'sgst_per',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'cess_per',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'cgst_amt',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'sgst_amt',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'cess_amt',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'igst_per',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'igst_amt',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'tax_id',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'other_charge',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'total_amt',['class'=>'span5','maxlength'=>10]); ?>


<?php echo $form->textFieldRow($model,'vendor_id',['class'=>'span5']); ?>


<?php echo $form->textFieldRow($model,'outlet_id',['class'=>'span5']); ?>


<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions()); ?>


<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions()); ?>


<?php echo $form->textFieldRow($model,'return_id',['class'=>'span5']); ?>


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