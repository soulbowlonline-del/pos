<?php
/**
 * Ported from protected/views/item/vendorcreate.php.
 */

use app\components\Gx;
use app\models\Item;
use app\models\ItemCategory;
use app\models\ItemCompany;
use app\models\ItemCompanyCategory;
use app\models\Tax;
use app\models\User;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\ButtonGroup;
use app\widgets\EChosenWidget;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
		$model->label(2) => ['index'],
		'Create',
];
?>
<section class="content-header">
<h1 class="pull-left"><?php echo 'Create' . ' : ' . Html::encode($model->label(2)); ?></h1>
<?php //echo Html::a('Delete',array('user/empty'));?>
<?php echo ButtonGroup::widget([
	'buttons'=>$this->context->menu,
	'type'=>'success',
	'htmlOptions'=>['class'=> 'pull-right margin10'],
]);
?>
</section>
<!--  form code start here -->
<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">

				<div class="box-header">
					<h3 class="box-title">Create Items</h3>
				</div>


				<div class="box-body">
					<div class="row">
						<div class="col-md-12">

<?php $form = ActiveForm::begin([
	'id' => 'item-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>
	<div class="col-md-6">
<h2>Basic Info</h2>

<?php echo $form->textFieldRow($model,'title',['class'=>'form-control','maxlength'=>255]); ?>

<?php echo $form->textFieldRow($model,'short_name',['class'=>'form-control','maxlength'=>255]); ?>
<?php echo $form->textFieldRow($model,'item_code',['class'=>'form-control','maxlength'=>255]); ?>
<?php //echo $form->textAreaRow($model,'description',array('class'=>'form-control','maxlength'=>255)); ?>
<?php echo $form->textFieldRow($model,'hsn_code',['class'=>'form-control','maxlength'=>255]); ?>

<?php echo $form->dropDownListRow($model,'item_type',$model->getTypeOptions(),['class'=>'form-control']); ?>
<?php echo $form->dropDownListRow($model, 'category_id', Gx::listData(ItemCategory::class),['class'=>'form-control']); ?>
<?php echo $form->dropDownListRow($model, 'company_id', Gx::listData(ItemCompany::class),['class'=>'form-control']); ?>

<?php echo $form->dropDownListRow($model, 'sub_company_id', Gx::listData(ItemCompanyCategory::class),['class'=>'form-control']); ?>
	<h2>Sale Info</h2>
<?php echo $form->checkBoxRow($model, 'is_discount'); ?>
<?php echo $form->checkBoxRow($model, 'is_coupon'); ?>

<?php echo $form->textFieldRow($model,'weight',['class'=>'form-control','maxlength'=>255]); ?>


</div>
	<div class="col-md-6">
	<?php echo $form->textFieldRow($model,'purchase_price',['class'=>'form-control','maxlength'=>255]); ?>
<?php echo $form->textFieldRow($model,'mrp',['class'=>'form-control','maxlength'=>255]); ?>
<?php echo $form->textFieldRow($model,'sale_price',['class'=>'form-control','maxlength'=>255]); ?>
<?php echo $form->textFieldRow($model,'whole_sale',['class'=>'form-control','maxlength'=>255]); ?>
	<h2>Image</h2>
	<?php echo $form->fileFieldRow($model, 'image_file'); ?>
	
				<h2>Storage Info</h2>
	<?php echo $form->radioButtonListRow($model,'is_stockable',$model->getStockOptions()); ?>
	<?php echo $form->dropDownListRow($model,'movement_type',$model->getMovementTypeOptions(),['class'=>'form-control']); ?>
	
<h2>Stock Info</h2>

<?php echo $form->checkboxRow($model,'company_bar_code',['id'=>'itemDetail_is_bar_code']); ?>
<?php echo $form->textFieldRow($model,'bar_code',['class'=>'form-control','maxlength'=>255]); ?>
<?php echo $form->dropDownListRow($model, 'tax_id', Gx::listData(Tax::class),['class'=>'form-control']); ?>
<?php echo $form->dropDownListRow($model,'unit',$model->getMeasurementTypeOptions(),['class'=>'form-control']); ?>
<div class="form-group">
<label for="inputEmail3" class="control-label col-md-3">
Outlet
</label>
<div class="col-md-9">
<?php echo Html::activeListBox($model, 'outlet_id', Item::getAllOutlets(), ActiveForm::noUnselect(['class'=>'chosen', 'multiple'=>true, 'data-placeholder'=>'Select Outlet'])) ?>
</div>
</div>	

<?php 
   
?>
 <?php echo EChosenWidget::widget([
    // the select selector
    'selector'=>'.chosen',
    // Chosen options
]);?>
 </div>







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

<!-- form code ends here -->

  

</section>
<script>
$('#Item_title').on('change', function(){
	var title = $('#Item_title').val();

	if(title != ''){
		var shortText = jQuery.trim(title).substring(0, 10);
	    
	    console.log(shortText);
		$('#Item_short_name').val(shortText);
	}
});
$('#Item_sale_price').on('change', function(){
	var sale_price = $('#Item_sale_price').val();
	var mrp = $('#Item_mrp').val();
	if(sale_price != '' && mrp != ''){
		if(mrp < sale_price){
	  alert('MRP can not be less than sale price');
	    $('#Item_sale_price').val('');
	    $('#Item_mrp').val('');
	  
	    }
	}
});
$('#itemDetail_is_bar_code').on('change', function(){
	if(this.checked==true){
	      var barcode = "<?php echo User::randomBarcode();?>";
	    $('#Item_bar_code').val(barcode);
	    }
});

</script>