<?php
/**
 * Ported from protected/views/itemDetail/update.php.
 */

use app\components\Gx;
use app\models\Item;
use app\models\Tax;
use app\models\User;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\EChosenWidget;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	Gx::str($model) => ['view', 'id' => Gx::pk($model)],
	'Update',
];
?>
<section class="content">
<div class="page-header">
<h1><?php echo 'Update' . ' ' . Html::encode($model->label()) . ' : ' . Html::encode(Gx::str($model)); ?></h1>
</div>

<!--  form code start here -->
<!--  form code start here -->
<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
        	<div class="box">
            
            <div class="box-header"><h3 class="box-title">Update SubItem</h3></div>
            
            
            <div class="box-body">
          <div class="row">
            <div class="col-md-12">


<?php $form = ActiveForm::begin([
	'id' => 'item-detail-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->dropDownListRow($model, 'item_id', Gx::listData(Item::class),['class'=>'form-control']); ?>

<?php echo $form->checkboxRow($model,'company_bar_code',['id'=>'item_is_bar_code']); ?>

<?php echo $form->textFieldRow($model,'bar_code',['class'=>'form-control','maxlength'=>255]); ?>
<?php echo $form->textFieldRow($model,'mrp',['class'=>'form-control','maxlength'=>255]); ?>

<?php echo $form->textFieldRow($model,'open_stock_qty',['class'=>'form-control']); ?>

<div class="form-group">
<label for="inputEmail3" class="control-label col-md-3">
Outlet
</label>
<div class="col-md-9">
<?php echo Html::activeListBox($model, 'outlet_id', Item::getAllOutlets(), ActiveForm::noUnselect(['class'=>'chosen', 'multiple'=>true, 'data-placeholder'=>'Select'])) ?>
</div>
</div>

<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions(),['class'=>'form-control'],['class'=>'form-control']); ?>



<?php echo $form->dropDownListRow($model, 'tax_id', Gx::listData(Tax::class),['class'=>'form-control']); ?>
<?php 
   
?>
 <?php echo EChosenWidget::widget([
    // the select selector
    'selector'=>'.chosen',
    // Chosen options
]);?>
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
$('#item_is_bar_code').on('change', function(){
	if(this.checked==true){
	      var barcode = "<?php echo User::randomBarcode();?>";
	    $('#ItemDetail_bar_code').val(barcode);
	    }
});

</script>
</section>