<!--  form code start here -->
<!--  form code start here -->
<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
        	<div class="box">
            
            <div class="box-header"><h3 class="box-title">Create SubItem</h3></div>
            
            
            <div class="box-body">
          <div class="row">
            <div class="col-md-12">

<?php if(Yii::app()->user->hasFlash('success')){ ?>

<div class="alert alert-success"><?php echo Yii::app()->user->getFlash('success'); ?>
</div>
<?php } ?>
<?php if(Yii::app()->user->hasFlash('error')){ ?>

<div class="alert alert-danger"><?php echo Yii::app()->user->getFlash('error'); ?>
</div>
<?php } ?>
<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'item-detail-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->dropDownListRow($model, 'item_id', Item::getActiveItems(),array('class'=>'form-control')); ?>

<?php echo $form->checkboxRow($model,'company_bar_code',array('id'=>'item_is_bar_code')); ?>

<?php echo $form->textFieldRow($model,'bar_code',array('class'=>'form-control','maxlength'=>255)); ?>
<?php echo $form->textFieldRow($model,'mrp',array('class'=>'form-control','maxlength'=>255)); ?>

<?php echo $form->textFieldRow($model,'open_stock_qty',array('class'=>'form-control')); ?>

<div class="form-group">
<label for="inputEmail3" class="control-label col-md-3">
Outlet
</label>
<div class="col-md-9">
<?php echo CHtml::activeListBox($model, 'outlet_id', Item::getAllOutlets(), array('class'=>'chosen', 'multiple'=>true, 'data-placeholder'=>'Select')) ?>
</div>
</div>

<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions(),array('class'=>'form-control'),array('class'=>'form-control')); ?>

<?php if($id != ''){
	$item = Item::model()->findByPk($id);
	if($item){
		$criteria = new CDbCriteria();
		$criteria->addCondition('item_id ='.$item->id);
		$criteria->order = 'id asc';
		$subitem = ItemDetail::model()->find($criteria);
		
		if($subitem)
		$model->tax_id = $subitem->tax_id;
	}
	
}?>

<?php echo $form->dropDownListRow($model, 'tax_id', GxHtml::listDataEx(Tax::model()->findAllByAttributes(array('status'=>Tax::STATUS_ACTIVE))),array('class'=>'form-control')); ?>
<?php Yii::import('application.extensions.widgets.yii-chosen.EChosenWidget');
   
?>
 <?php $this->widget('EChosenWidget',array(
    // the select selector
    'selector'=>'.chosen',
    // Chosen options
));?>
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
</section>
<!-- form code ends here -->

<script>
<?php if($id != null){?>

var id = "<?php echo $id;?>";
$('#ItemDetail_item_id').val(id);
<?php }?>

$('#item_is_bar_code').on('change', function(){
	if(this.checked==true){
	      var barcode = "<?php echo User::randomBarcode();?>";
	    $('#ItemDetail_bar_code').val(barcode);
	    }
});

</script>