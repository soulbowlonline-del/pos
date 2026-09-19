<?php
/**
 * Ported from protected/views/item/extra.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Item;
use app\models\Tax;
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
<div class="row">
<div class="col-md-12 margin10">
<a href="<?php echo Ui::to('item/create',array('id'=>$id));?>" class="btn btn-primary">Product Info</a>
<a href="<?php echo Ui::to('item/extra',array('id'=>$id));?>" class="btn btn-primary">Extra Info</a>
<a href="<?php echo Ui::to('item/vendor',array('id'=>$id));?>" class="btn btn-primary">Vendor Information</a>
<a href="<?php echo Ui::to('stockLog/admin',array('id'=>$id));?>" class="btn btn-primary">Movement History</a>
<a href="<?php echo Ui::to('orderItem/index',array('id'=>$id));?>" class="btn btn-primary">Order History</a>
<a href="<?php echo Ui::to('itemDetail/create',array('id'=>$id));?>" class="btn btn-primary">Add Subitem</a>
<a href="<?php echo Ui::to('vendorSchemes/add',array('id'=>$id));?>" class="btn btn-primary">Add Vendor Scheme</a>

</div>
</div>


    
<h1 class="pull-left"><?php echo 'Extra Info'; ?></h1>
<?php //echo Html::a('Delete',array('user/empty'));?>


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
 


<?php $form = ActiveForm::begin([
	'id' => 'item-extra-form',
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
	<div class="col-md-12">
    
    <h4 class="page-header">Storage Info</h4>
	
	<?php echo $form->radioButtonListRow($model,'is_stockable',$model->getStockOptions()); ?>
	<?php echo $form->dropDownListRow($model,'movement_type',$model->getMovementTypeOptions(),['class'=>'form-control']); ?>
	

 <h4 class="page-header">Stock Info</h4>

<div class="form-group">
<label for="inputEmail3" class="control-label col-md-3">

</label>
<div class="col-md-9">
<?php echo $form->checkboxRow($itemdetail,'company_bar_code',['id'=>'itemDetail_is_bar_code']); ?>
</div>
</div>
<?php if(!$itemdetail->bar_code){?>
<?php echo $form->textFieldRow($itemdetail,'bar_code',['class'=>'form-control','value'=>$model->item_code,'maxlength'=>255]); ?>
<?php } else { ?>
<?php echo $form->textFieldRow($itemdetail,'bar_code',['class'=>'form-control','maxlength'=>255]); ?>
<?php } ?>
<?php echo $form->dropDownListRow($itemdetail, 'tax_id', Gx::listData(Tax::class),['class'=>'form-control']); ?>
<?php echo $form->textFieldRow($model,'opening_stock',['class'=>'form-control','maxlength'=>255]); ?>

</div>
	<div class="col-md-12">
 
<h4 class="page-header">Stock Level</h4>
<?php if($model->max_qty == ''){
	$model->max_qty = 50;
}?>
<?php echo $form->textFieldRow($model,'max_qty',['class'=>'form-control','maxlength'=>255]); ?>
<?php if($model->min_qty == ''){
	$model->min_qty = 10;
}?>
<?php echo $form->textFieldRow($model,'min_qty',['class'=>'form-control','maxlength'=>255]); ?>

<?php if($model->reorder_qty == ''){
	$model->reorder_qty = 10;
}?>
<?php echo $form->textFieldRow($model,'reorder_qty',['class'=>'form-control','maxlength'=>255]); ?>
<?php echo $form->dropDownListRow($model,'unit',$model->getMeasurementTypeOptions(),['class'=>'form-control']); ?>
<div class="form-group">
<label for="inputEmail3" class="control-label col-md-3">
Outlet
</label>
<div class="col-md-9">
<?php echo Html::activeListBox($itemdetail, 'outlet_id', Item::getAllOutlets(), ActiveForm::noUnselect(['class'=>'chosen', 'multiple'=>true, 'data-placeholder'=>'Select Outlet'])) ?>
</div>
</div>	
</div>

<?php 
   
?>
 <?php echo EChosenWidget::widget([
    // the select selector
    'selector'=>'.chosen',
    // Chosen options
]);?>


	<div class="form-actions bttn-wrap">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Save',
		]); ?>
		<?php echo ButtonGroup::widget([
	'buttons'=>$this->context->menu,
	'type'=>'success',
	//'htmlOptions'=>array('class'=> 'pull-right margin10'),
]);
?>
	</div>

<?php ActiveForm::end(); ?>

 
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