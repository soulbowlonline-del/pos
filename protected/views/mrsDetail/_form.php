<!--  form code start here -->
<div class="form well">


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'mrs-detail-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>
<?php echo $form->dropDownListRow($model, 'mrs_id', $model->getMrsOptions($id),array('class'=>'form-control')); ?>


<?php echo $form->dropDownListRow($model, 'item_id', GxHtml::listDataEx(Item::model()->findAllAttributes(null, true)),array('class'=>'form-control')); ?>

<div class="form-group">
<div class="col-md-3"><?php echo $form->label($model, 'item_detail_id');?></div>
<div class="col-md-9">

<div id="item_detail_data">

</div>

<?php echo $form->error($model, 'item_detail_id');?>
</div>
</div>


<?php echo $form->textFieldRow($model,'req_qty',array('class'=>'form-control')); ?>


<?php echo $form->textFieldRow($model,'approved_qty',array('class'=>'form-control')); ?>





<?php  echo $form->textAreaRow($model,'remarks',  array('class'=>'form-control', 'rows'=>5)); ?>


<?php  echo $form->textFieldRow($model,'mrp',  array('class'=>'form-control')); ?>
<?php  echo $form->textFieldRow($model,'price',  array('class'=>'form-control')); ?>
<?php  echo $form->textFieldRow($model,'sale_rate',  array('class'=>'form-control')); ?>
<?php  echo $form->textFieldRow($model,'discount',  array('class'=>'form-control')); ?>
<?php  echo $form->textFieldRow($model,'discount_amt',  array('class'=>'form-control')); ?>
<?php  echo $form->textFieldRow($model,'vat',  array('class'=>'form-control')); ?>
<?php  echo $form->textFieldRow($model,'other_charge',  array('class'=>'form-control')); ?>
<?php  echo $form->textFieldRow($model,'amount',  array('class'=>'form-control')); ?>
	<div class="form-actions">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Save',
		)); ?>
	</div>

<?php $this->endWidget(); ?>

</div>
<!-- form code ends here -->

<script>
$(document).ready(function () {
	checkBarcodes();
    $('#MrsDetail_item_id').change(function () {  
    	checkBarcodes();
    });
   

 });

function checkBarcodes(){
	 var item_id = $('#MrsDetail_item_id').val();
	    jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo CController::createUrl('mrnDetail/ajaxItems') ?>',
	       'data': {'item_id': item_id},
	       'success': function (data) {
	           $('#item_detail_data').html('');
	           $('#item_detail_data').html(data);
	         
	       },
	       'cache': false
	    }
	    );	
}
</script>