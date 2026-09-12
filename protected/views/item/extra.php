<?php

$this->breadcrumbs = array(
		$model->label(2) => array('index'),
		Yii::t('app', 'Create'),
);
?>
<section class="content-header">
<div class="row">
<div class="col-md-12 margin10">
<a href="<?php echo Yii::app()->createUrl('item/create',array('id'=>$id));?>" class="btn btn-primary">Product Info</a>
<a href="<?php echo Yii::app()->createUrl('item/extra',array('id'=>$id));?>" class="btn btn-primary">Extra Info</a>
<a href="<?php echo Yii::app()->createUrl('item/vendor',array('id'=>$id));?>" class="btn btn-primary">Vendor Information</a>
<a href="<?php echo Yii::app()->createUrl('stockLog/admin',array('id'=>$id));?>" class="btn btn-primary">Movement History</a>
<a href="<?php echo Yii::app()->createUrl('orderItem/index',array('id'=>$id));?>" class="btn btn-primary">Order History</a>
<a href="<?php echo Yii::app()->createUrl('itemDetail/create',array('id'=>$id));?>" class="btn btn-primary">Add Subitem</a>
<a href="<?php echo Yii::app()->createUrl('vendorSchemes/add',array('id'=>$id));?>" class="btn btn-primary">Add Vendor Scheme</a>

</div>
</div>


    
<h1 class="pull-left"><?php echo Yii::t('app', 'Extra Info'); ?></h1>
<?php //echo CHtml::link('Delete',array('user/empty'));?>


</section>

<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
        	<div class="box">
            
            <div class="box-header"><h3 class="box-title">Items</h3></div>
            
            
            <div class="box-body">
          <div class="row">
            <div class="col-md-12">
            
            
            <!--  form code start here -->
 


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'item-extra-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
<?php if(Yii::app()->user->hasFlash('success')){ ?>

<div class="alert alert-success"><?php echo Yii::app()->user->getFlash('success'); ?>
</div>
<?php } ?>
<?php if(Yii::app()->user->hasFlash('error')){ ?>

<div class="alert alert-danger"><?php echo Yii::app()->user->getFlash('error'); ?>
</div>
<?php } ?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>
	<div class="col-md-12">
    
    <h4 class="page-header">Storage Info</h4>
	
	<?php echo $form->radioButtonListRow($model,'is_stockable',$model->getStockOptions()); ?>
	<?php echo $form->dropDownListRow($model,'movement_type',$model->getMovementTypeOptions(),array('class'=>'form-control')); ?>
	

 <h4 class="page-header">Stock Info</h4>

<div class="form-group">
<label for="inputEmail3" class="control-label col-md-3">

</label>
<div class="col-md-9">
<?php echo $form->checkboxRow($itemdetail,'company_bar_code',array('id'=>'itemDetail_is_bar_code')); ?>
</div>
</div>
<?php if(!$itemdetail->bar_code){?>
<?php echo $form->textFieldRow($itemdetail,'bar_code',array('class'=>'form-control','value'=>$model->item_code,'maxlength'=>255)); ?>
<?php } else { ?>
<?php echo $form->textFieldRow($itemdetail,'bar_code',array('class'=>'form-control','maxlength'=>255)); ?>
<?php } ?>
<?php echo $form->dropDownListRow($itemdetail, 'tax_id', GxHtml::listDataEx(Tax::model()->findAllAttributes(null, true)),array('class'=>'form-control')); ?>
<?php echo $form->textFieldRow($model,'opening_stock',array('class'=>'form-control','maxlength'=>255)); ?>

</div>
	<div class="col-md-12">
 
<h4 class="page-header">Stock Level</h4>
<?php if($model->max_qty == ''){
	$model->max_qty = 50;
}?>
<?php echo $form->textFieldRow($model,'max_qty',array('class'=>'form-control','maxlength'=>255)); ?>
<?php if($model->min_qty == ''){
	$model->min_qty = 10;
}?>
<?php echo $form->textFieldRow($model,'min_qty',array('class'=>'form-control','maxlength'=>255)); ?>

<?php if($model->reorder_qty == ''){
	$model->reorder_qty = 10;
}?>
<?php echo $form->textFieldRow($model,'reorder_qty',array('class'=>'form-control','maxlength'=>255)); ?>
<?php echo $form->dropDownListRow($model,'unit',$model->getMeasurementTypeOptions(),array('class'=>'form-control')); ?>
<div class="form-group">
<label for="inputEmail3" class="control-label col-md-3">
Outlet
</label>
<div class="col-md-9">
<?php echo CHtml::activeListBox($itemdetail, 'outlet_id', Item::getAllOutlets(), array('class'=>'chosen', 'multiple'=>true, 'data-placeholder'=>'Select Outlet')) ?>
</div>
</div>	
</div>

<?php Yii::import('application.extensions.widgets.yii-chosen.EChosenWidget');
   
?>
 <?php $this->widget('EChosenWidget',array(
    // the select selector
    'selector'=>'.chosen',
    // Chosen options
));?>


	<div class="form-actions bttn-wrap">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Save',
		)); ?>
		<?php $this->widget('bootstrap.widgets.TbButtonGroup', array(
	'buttons'=>$this->menu,
	'type'=>'success',
	//'htmlOptions'=>array('class'=> 'pull-right margin10'),
));
?>
	</div>

<?php $this->endWidget(); ?>

 
<!-- form code ends here -->


</div>
</div>
</div>
            
            
            </div>
        </div>    
     </div>
</section>



<script>
<?php if($itemdetail->id != ''){?>
var company = "<?php echo $itemdetail->company_bar_code;?>";
console.log('company'+company);
if(company == 1){
	
	$("#itemDetail_is_bar_code").prop("checked", false);
}else{
	$("#itemDetail_is_bar_code").prop("checked", true);
}

<?php }else{?>
$("#itemDetail_is_bar_code").prop("checked", true);

<?php }?>
var barcode = "<?php echo $bar_code;?>";
$( document ).ready(function() {
  
 //   $('#ItemDetail_bar_code').val(barcode);
});
$('#itemDetail_is_bar_code').on('change', function(){
	if(this.checked==true){
	     $('#ItemDetail_bar_code').val('');
	    }else{
	    	   $('#ItemDetail_bar_code').val(barcode);
	    }
});

</script>

</section>