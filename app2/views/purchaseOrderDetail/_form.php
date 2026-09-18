<?php
/**
 * Ported from protected/views/purchaseOrderDetail/_form.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Item;
use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<!--  form code start here -->
<div class="form well">


<?php $form = ActiveForm::begin([
	'id' => 'mrs-detail-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>
<?php echo $form->dropDownListRow($model, 'purchase_order_id', $model->getPOOptions($id),['class'=>'form-control']); ?>


<?php echo $form->dropDownListRow($model, 'item_id', Gx::listData(Item::class),['class'=>'form-control']); ?>

<div class="form-group">
<div class="col-md-3"><?php echo $form->label($model, 'item_detail_id');?></div>
<div class="col-md-9">

<div id="item_detail_data">

</div>

<?php echo $form->error($model, 'item_detail_id');?>
</div>
</div>


<?php echo $form->textFieldRow($model,'req_qty',['class'=>'form-control']); ?>


<?php echo $form->textFieldRow($model,'approved_qty',['class'=>'form-control']); ?>


<?php //echo $form->textFieldRow($model,'bal_qty',array('class'=>'form-control')); ?>



<?php  echo $form->textAreaRow($model,'remarks',  ['class'=>'form-control', 'rows'=>5]); ?>


<?php  echo $form->textFieldRow($model,'mrp',  ['class'=>'form-control']); ?>
<?php  echo $form->textFieldRow($model,'price',  ['class'=>'form-control']); ?>
<?php  echo $form->textFieldRow($model,'sale_rate',  ['class'=>'form-control']); ?>
<?php  echo $form->textFieldRow($model,'discount',  ['class'=>'form-control']); ?>
<?php  echo $form->textFieldRow($model,'discount_amt',  ['class'=>'form-control']); ?>
<?php  echo $form->textFieldRow($model,'vat',  ['class'=>'form-control']); ?>
<?php  echo $form->textFieldRow($model,'other_charge',  ['class'=>'form-control']); ?>
<?php  echo $form->textFieldRow($model,'amount',  ['class'=>'form-control']); ?>
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

<script>
$(document).ready(function () {
	checkBarcodes();
    $('#PurchaseOrderDetail_item_id').change(function () {  
    	checkBarcodes();
    });
   

 });

function checkBarcodes(){
	 var item_id = $('#PurchaseOrderDetail_item_id').val();
	    jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('purchaseOrderDetail/ajaxItems') ?>',
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