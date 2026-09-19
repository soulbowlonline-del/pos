<?php
/**
 * Ported from protected/views/itemStock/_form.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Item;
use app\models\Tax;
use app\models\User;
use app\models\Vendor;
use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<!--  form code start here -->
<div class="form well">


<?php

$form = ActiveForm::begin([
		'id' => 'item-stock-form',
		'type' => 'horizontal',
		'enableAjaxValidation' => true,
		'htmlOptions' => [
				'enctype' => 'multipart/form-data' 
		] 
] );
?>
	<p class="help-block">
		Fields with <span class="required">*</span> are required.
	</p>

	<?php echo $form->errorSummary($model); ?>

<?php echo $form->dropdownListRow($model, 'item_id',Item::getActiveItems(),['class'=>'form-control']); ?>


<div class="form-group ">
		<label for="ItemStock_item_id" class="control-label col-md-3 required">Bar Code
			</label>
		<div class="col-md-9">
			<div id="item_detail_data"></div>
		</div>
	</div>
	<div class="form-group ">
		<label for="ItemStock_item_id" class="control-label col-md-3 required">
			</label>
		<div class="col-md-9">
			<?php echo $form->checkboxRow($model,'is_company_batch_no',['id'=>'is_company_batch_no','checked'=>'checked']); ?>
		</div>
	</div>

<?php echo $form->textFieldRow($model,'batch_number',['class'=>'form-control','maxlength'=>255]); ?>


<?php echo $form->dropdownListRow($model, 'type_id', $model->getTypeOptions(),['class'=>'form-control']); ?>

<?php echo $form->textFieldRow($model,'purchase_qty',['class'=>'form-control']); ?>
<?php //echo $form->textFieldRow($model,'balance_qty',array('class'=>'form-control')); ?>

<?php echo $form->textFieldRow($model,'base_price',['class'=>'form-control']); ?>


<?php echo $form->textFieldRow($model,'mrp',['class'=>'form-control']); ?>


<?php echo $form->dropdownListRow($model, 'tax_id', Gx::listData(Tax::find()->where(['status'=>Tax::STATUS_ACTIVE])->all()),['class'=>'form-control']); ?>

<?php echo $form->dropdownListRow($model, 'outlet_id',Item::getAllOutlets(),['class'=>'form-control']); ?>
<?php //echo $form->dropdownListRow($model, 'vendor_id', Gx::listData(Vendor::class),array('class'=>'form-control')); ?>
<div class="form-group ">
		<label for="ItemStock_vendor_id"
			class="control-label col-md-3 required">Vendor</label>
		<div class="col-md-9">
			<div id="vendor_detail_data"></div>
		</div>
	</div>




	<div class="form-actions">
		<?php
		
echo Button::widget([
				'buttonType' => 'submit',
				'type' => 'primary',
				'label' => 'Save' 
		] );
		?>
	</div>

<?php ActiveForm::end(); ?>

</div>
<!-- form code ends here -->

	<script>
var batch = "<?php echo User::randomBarcode('5');?>";
$('#ItemStock_batch_number').val(batch);

$('#is_company_batch_no').on('change', function(){
	if(this.checked==false){
	     $('#ItemStock_batch_number').val('');
	    }else{
	    	$('#ItemStock_batch_number').val(batch);
	    }
});

</script>
<script>
$(document).ready(function () {
	checkBarcodes();
	checkVendors();
	BarCodeData();
    $('#ItemStock_item_id').change(function () {  
    	checkBarcodes();
    	checkVendors();
    });
   
    

 });

function BarCodeData(){
	 var item_detail_id = $('#ItemStock_item_detail_id').val();
	    jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('itemStock/ajaxItemDetail') ?>',
	       'data': {'item_detail_id': item_detail_id},
	       dataType: "json",
	       'success': function (data) {
		        if(data.batch_number != ''){
	    	   $('#ItemStock_batch_number').val(data.batch_number);
		       }
		       if(data.outlet_id != ''){
		    	   $('#ItemStock_outlet_id').val(data.outlet_id);
			       }  
		       if(data.tax_id != ''){
		    	   $('#ItemStock_tax_id').val(data.tax_id);
			       }  
	         
	       },
	       'cache': false
	    }
	    );	
}
function checkBarcodes(){
	 var item_id = $('#ItemStock_item_id').val();
	    jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('itemStock/ajaxItems') ?>',
	       'data': {'item_id': item_id},
	       dataType: "json",
	       'success': function (data) {
		     
	           $('#item_detail_data').html('');
	           $('#item_detail_data').html(data.option);
	           $('#ItemStock_mrp').val(data.mrp);
	           $('#ItemStock_base_price').val(data.base_price);
	         
	       },
	       'cache': false
	    }
	    );	
}
function checkVendors(){
	 var item_id = $('#ItemStock_item_id').val();
	    jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('itemStock/ajaxVendors') ?>',
	       'data': {'item_id': item_id},
	       'success': function (data) {
	           $('#vendor_detail_data').html('');
	           $('#vendor_detail_data').html(data);
	         
	       },
	       'cache': false
	    }
	    );	
}
</script>