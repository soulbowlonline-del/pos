<?php
/**
 * Ported from protected/views/mrn/_form.php.
 */

use app\components\Gx;
use app\models\MrnDetail;
use app\models\Mrs;
use app\models\Organization;
use app\models\Outlet;
use app\models\PurchaseOrder;
use app\models\User;
use app\widgets\ActiveForm;
use app\widgets\Button;
use yii\helpers\Html;
?>
<!--  form code start here -->
<div class="form well">


<?php $form = ActiveForm::begin([
	'id' => 'mrn-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'code',['class'=>'span5','maxlength'=>255]); ?>


<?php echo $form->datepickerRow($model, 'mrs_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>


<?php echo $form->datepickerRow($model, 'mrs_update_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>


<?php echo $form->datepickerRow($model, 'mrs_req_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'])
; ?>


<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions()); ?>


<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions()); ?>


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


<?php echo $form->radioButtonListRow($model, 'outlet_id', Gx::listData(Outlet::class)); ?>


<?php echo $form->radioButtonListRow($model, 'mrs_id', Gx::listData(Mrs::class)); ?>


<?php echo $form->radioButtonListRow($model, 'organization_id', Gx::listData(Organization::class)); ?>







<?php if ( count (MrnDetail::find()->all() ) > 0 ): ?>
		<label><?php echo Html::encode($model->getRelationLabel('mrnDetails')); ?></label>
	
		<?php echo $form->checkBoxListRow($model, 'mrnDetails', Gx::encodeEx(Gx::listData(MrnDetail::class), false, true)); ?>
<?php endif; ?>	


<?php if ( count (PurchaseOrder::find()->all() ) > 0 ): ?>
		<label><?php echo Html::encode($model->getRelationLabel('purchaseOrders')); ?></label>
	
		<?php echo $form->checkBoxListRow($model, 'purchaseOrders', Gx::encodeEx(Gx::listData(PurchaseOrder::class), false, true)); ?>
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