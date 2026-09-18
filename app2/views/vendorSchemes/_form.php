<?php
/**
 * Ported from protected/views/vendorSchemes/_form.php.
 */

use Yii;
use app\models\Item;
use app\widgets\ActiveForm;
use app\widgets\Button;
use yii\helpers\Html;
?>
<script src="<?php  echo '/themes/bar'; ?>/js/jquery-ui.js"></script>
<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">

				<div class="box-header">
					<h3 class="box-title">Vendor Scheme</h3>
				</div>


				<div class="box-body">
					<div class="row">
						<div class="col-md-12">

<?php $form = ActiveForm::begin([
	'id' => 'vendor-schemes-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
<?php if(Yii::$app->user->hasFlash('success')){ ?>

<div class="alert alert-success"><?php echo Yii::$app->user->getFlash('success'); ?>
</div>
<?php } ?>
<?php if(Yii::$app->user->hasFlash('error')){ ?>

<div class="alert alert-danger"><?php echo Yii::$app->user->getFlash('error'); ?>
</div>
<?php } ?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->dropDownListRow($model, 'vendor_id',Item::getAllActiveVendors(),['class'=>'form-control']); ?>
<div class="form-group">
<label for="inputEmail3" class="control-label col-md-3">
Item
</label>
<div class="col-md-9">
<?php echo CHtml::activeListBox($model, 'item_id',Item::getActiveItems(), ['class'=>'chosen', 'multiple'=>true, 'data-placeholder'=>'Select Item']) ?>
</div>
</div>	


<?php Yii::import('application.extensions.widgets.yii-chosen.EChosenWidget');
   
?>
 <?php $this->widget('EChosenWidget',[
    // the select selector
    'selector'=>'.chosen',
    // Chosen options
]);?>

<?php echo $form->textFieldRow($model,'total_sale',['class'=>'form-control']); ?>


<?php echo $form->datepickerRow($model, 'start_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
							'options'=>['format'=>'yyyy-mm-dd']])
; ?>


<?php echo $form->datepickerRow($model, 'end_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
							'options'=>['format'=>'yyyy-mm-dd']])
; ?>


<?php echo $form->dropDownListRow($model, 'type_id',
			$model->getTypeOptions()); ?>
<?php echo $form->textFieldRow($model,'discount',['class'=>'form-control']); ?>




<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions()); ?>






	<div class="form-actions">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Save',
		]); ?>
	</div>

<?php ActiveForm::end(); ?>
</div>
					</div>
				</div>


			</div>
		</div>
	</div>
</section>
<!-- form code ends here -->

<script>
$('#VendorSchemes_start_date').datepicker({
    autoclose: true
    
});
$('#VendorSchemes_end_date').datepicker({
    autoclose: true
});


/* $('#VendorSchemes_start_date').datepicker().on('changeDate', function(){
    $(this).hide();
  });  */
//$('input[name="VendorSchemes[start_date]"]').datepicker({ autoClose: true});

</script>