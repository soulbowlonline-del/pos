<?php

$this->breadcrumbs = array(
		$model->label(2) => array('index'),
		Yii::t('app', 'Create'),
);
?>
<section class="content-header">
<h1 class="pull-left"><?php echo Yii::t('app', 'Create') . ' : ' . GxHtml::encode($model->label(2)); ?></h1>
<?php //echo CHtml::link('Delete',array('user/empty'));?>
<?php $this->widget('bootstrap.widgets.TbButtonGroup', array(
	'buttons'=>$this->menu,
	'type'=>'success',
	'htmlOptions'=>array('class'=> 'pull-right margin10'),
));
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

<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'item-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>
	<div class="col-md-6">
<h2>Basic Info</h2>

<?php echo $form->textFieldRow($model,'title',array('class'=>'form-control','maxlength'=>255)); ?>

<?php echo $form->textFieldRow($model,'short_name',array('class'=>'form-control','maxlength'=>255)); ?>
<?php echo $form->textFieldRow($model,'item_code',array('class'=>'form-control','maxlength'=>255)); ?>
<?php //echo $form->textAreaRow($model,'description',array('class'=>'form-control','maxlength'=>255)); ?>
<?php echo $form->textFieldRow($model,'hsn_code',array('class'=>'form-control','maxlength'=>255)); ?>

<?php echo $form->dropDownListRow($model,'item_type',$model->getTypeOptions(),array('class'=>'form-control')); ?>
<?php echo $form->dropDownListRow($model, 'category_id', GxHtml::listDataEx(ItemCategory::model()->findAllAttributes(null, true)),array('class'=>'form-control')); ?>
<?php echo $form->dropDownListRow($model, 'company_id', GxHtml::listDataEx(ItemCompany::model()->findAllAttributes(null, true)),array('class'=>'form-control')); ?>

<?php echo $form->dropDownListRow($model, 'sub_company_id', GxHtml::listDataEx(ItemCompanyCategory::model()->findAllAttributes(null, true)),array('class'=>'form-control')); ?>
	<h2>Sale Info</h2>
<?php echo $form->checkBoxRow($model, 'is_discount'); ?>
<?php echo $form->checkBoxRow($model, 'is_coupon'); ?>

<?php echo $form->textFieldRow($model,'weight',array('class'=>'form-control','maxlength'=>255)); ?>


</div>
	<div class="col-md-6">
	<?php echo $form->textFieldRow($model,'purchase_price',array('class'=>'form-control','maxlength'=>255)); ?>
<?php echo $form->textFieldRow($model,'mrp',array('class'=>'form-control','maxlength'=>255)); ?>
<?php echo $form->textFieldRow($model,'sale_price',array('class'=>'form-control','maxlength'=>255)); ?>
<?php echo $form->textFieldRow($model,'whole_sale',array('class'=>'form-control','maxlength'=>255)); ?>
	<h2>Image</h2>
	<?php echo $form->fileFieldRow($model, 'image_file'); ?>
	
				<h2>Storage Info</h2>
	<?php echo $form->radioButtonListRow($model,'is_stockable',$model->getStockOptions()); ?>
	<?php echo $form->dropDownListRow($model,'movement_type',$model->getMovementTypeOptions(),array('class'=>'form-control')); ?>
	
<h2>Stock Info</h2>

<?php echo $form->checkboxRow($model,'company_bar_code',array('id'=>'itemDetail_is_bar_code')); ?>
<?php echo $form->textFieldRow($model,'bar_code',array('class'=>'form-control','maxlength'=>255)); ?>
<?php echo $form->dropDownListRow($model, 'tax_id', GxHtml::listDataEx(Tax::model()->findAllAttributes(null, true)),array('class'=>'form-control')); ?>
<?php echo $form->dropDownListRow($model,'unit',$model->getMeasurementTypeOptions(),array('class'=>'form-control')); ?>
<div class="form-group">
<label for="inputEmail3" class="control-label col-md-3">
Outlet
</label>
<div class="col-md-9">
<?php echo CHtml::activeListBox($model, 'outlet_id', Item::getAllOutlets(), array('class'=>'chosen', 'multiple'=>true, 'data-placeholder'=>'Select Outlet')) ?>
</div>
</div>	

<?php Yii::import('application.extensions.widgets.yii-chosen.EChosenWidget');
   
?>
 <?php $this->widget('EChosenWidget',array(
    // the select selector
    'selector'=>'.chosen',
    // Chosen options
));?>
 </div>







	<div class="form-actions">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Save',
		)); ?>
	</div>

<?php $this->endWidget(); ?>

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