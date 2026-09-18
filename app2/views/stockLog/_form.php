<?php
/**
 * Ported from protected/views/stockLog/_form.php.
 */

use app\components\Gx;
use app\models\Item;
use app\models\ItemDetail;
use app\models\Outlet;
use app\models\Vendor;
use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<!--  form code start here -->
<div class="form well">


<?php $form = ActiveForm::begin([
	'id' => 'stock-log-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->radioButtonListRow($model, 'item_detail_id', Gx::listData(ItemDetail::class)); ?>


<?php echo $form->radioButtonListRow($model, 'item_id', Gx::listData(Item::class)); ?>


<?php echo $form->textFieldRow($model,'batch_no',['class'=>'span5','maxlength'=>255]); ?>


<?php echo $form->textFieldRow($model,'Qty',['class'=>'span5']); ?>


<?php echo $form->radioButtonListRow($model, 'outlet_id', Gx::listData(Outlet::class)); ?>


<?php echo $form->radioButtonListRow($model, 'vendor_id', Gx::listData(Vendor::class)); ?>


<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions()); ?>


<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions()); ?>


<?php echo $form->datepickerRow($model, 'update_time',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>







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