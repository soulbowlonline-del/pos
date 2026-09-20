<?php
/**
 * Ported from protected/views/item/_form.php.
 */

use app\components\Ui;
use app\models\Item;
use app\models\User;
use app\models\UserRole;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\ButtonGroup;
use app\widgets\EChosenWidget;
use app\widgets\TbTypeAhead;
use yii\helpers\Html;
?>
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
<?php if(Yii::$app->user->hasFlash('success')){ ?>

<div class="alert alert-success"><?php echo Yii::$app->user->getFlash('success'); ?>
</div>
<?php }else{ ?>
<?php if($flash == true){?>
<div class="alert alert-success">Item info is saved sucessfully</div>
<?php }?>
<?php }?>

<?php

$form = ActiveForm::begin([
		'id' => 'item-form',
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
	<div class="col-md-6">
								<h2>Basic Info</h2>

<?php echo $form->textFieldRow($model,'title',['class'=>'form-control','maxlength'=>255]); ?>

<?php echo $form->textFieldRow($model,'short_name',['class'=>'form-control','maxlength'=>20]); ?>
<?php  if( $model->item_code == '')
	$model->item_code = User::randomBarcode('5');?>	
<?php echo $form->hiddenField($model,'item_code',['class'=>'form-control','maxlength'=>255]); ?>
<div class="form-group">
<label for="inputEmail3" class="control-label col-md-3">
HSN Code

</label>
<div class="col-md-9">
<input type="hidden" name="Item[hsn_code]" id="item_hsn_code">

   <?php 
   
   echo TbTypeAhead::widget([
              'model' => $model,
              'attribute' => 'hsn_code',
              'enableHogan' => true,
              
              'options' => [
                           [
                                         'limit' => 10,
                                         'attribute' => 'hsn_code',
                                         'valueKey' => 'name',
                                         'remote' => [
                                                       'url' => Ui::to('/item/hsnCodeList') . '?term=%QUERY',
                                         ],
                           		'template' => '<p>{{name}}</p>',
                                  //     'template' => '<p>{{name}}<strong> [ {{username}} ] </strong> - {{user_id}}</p>',
                                         'engine' => new \yii\web\JsExpression('Hogan'),
                           ]
              ],
              
               /* 'events' => array(
                           'selected' => new \yii\web\JsExpression("function(obj, datum, name) {
                    var    uid = datum.user_id;
                           $('#item_vendor_id').val(uid);
          
         }")
              ), */ 
   ]);
   
   
   ?>
   <?php echo $form->error($model,'hsn_code');?></div>
</div>

<?php //echo $form->textAreaRow($model,'description',array('class'=>'form-control','maxlength'=>255)); ?>


<?php echo $form->dropDownListRow($model, 'category_id', $model->getParentCategorys(),['class'=>'form-control']); ?>
<div class="form-group">
		<div class="control-label col-md-3"><?php echo $form->label($model, 'sub_category_id');?></div>
		<div class="col-md-9">
			<div id="subcategorydisplay"></div>
<?php echo $form->error($model, 'sub_category_id');?>
</div>
	</div>
<?php echo $form->dropDownListRow($model, 'company_id', $model->getParentCompanys(),['class'=>'form-control']); ?>

<div class="form-group">
		<div class="control-label col-md-3"><?php echo $form->label($model, 'sub_company_id');?></div>
		<div class="col-md-9">
			<div id="subcompanydisplay"></div>
<?php echo $form->error($model, 'sub_company_id');?>
</div>
	</div>

<?php echo $form->dropDownListRow($model,'item_type',$model->getTypeOptions(),['class'=>'form-control']); ?>

</div>
							<div class="col-md-6">
								<h2>Sale Info</h2>
<?php echo $form->checkBoxRow($model, 'is_discount',['checked'=>'checked']); ?>
<?php echo $form->checkBoxRow($model, 'is_coupon'); ?>
<?php echo $form->textFieldRow($model,'purchase_price',['class'=>'form-control','maxlength'=>255]); ?>
<?php echo $form->textFieldRow($model,'mrp',['class'=>'form-control','maxlength'=>255]); ?>
<?php echo $form->textFieldRow($model,'sale_price',['class'=>'form-control','maxlength'=>255]); ?>
<?php echo $form->textFieldRow($model,'weight',['class'=>'form-control','maxlength'=>255]); ?>

<?php echo $form->textFieldRow($model,'whole_sale',['class'=>'form-control','maxlength'=>255]); ?>
	<h2>Image</h2>
	<?php echo $form->fileFieldRow($model, 'image_file'); ?>
	<?php
	
/* $role = UserRole::find()->where(array (
			'title' => 'Admin'))->orderBy(['id' => SORT_DESC])->one();
	$user = Yii::$app->user->model;
	if ($user->role_id == $role->id) { */
		?>
		 <?php if($model->checkPermission('item/active')){?>
	<?php
		
echo $form->dropDownListRow ( $model, 'status', $model->getStatusOptions (), [
				'class' => 'form-control' 
		] );
		?>
		<?php }?>
			<?php /* } */?>


</div>





<?php //echo $form->checkBoxRow($model, 'is_tax'); ?>






<div style="display: none">
<?php echo $form->textFieldRow($model,'min_qty',['class'=>'form-control','maxlength'=>255]); ?>
<?php echo $form->textFieldRow($model,'max_qty',['class'=>'form-control','maxlength'=>255]); ?>
<?php echo $form->textFieldRow($model,'reorder_qty',['class'=>'form-control','maxlength'=>255]); ?>


<div class="form-group">
									<label for="inputEmail3" class="control-label col-md-3"> Vendor
									</label>

<?php echo Html::activeListBox($model, 'vendor_id', Item::getAllVendors(), ActiveForm::noUnselect(['class'=>'chosen', 'multiple'=>true, 'data-placeholder'=>'Select']))?>

</div>

		

<?php


?>
 <?php
	
echo EChosenWidget::widget([
			// the select selector
			'selector' => '.chosen' 
	]
	// Chosen options
	 );
	?>
 </div>


							<div class="form-actions">
		<?php
		
echo Button::widget([
				'buttonType' => 'submit',
				'type' => 'primary',
				'label' => 'Save' 
		] );
		?>
		<?php
		
echo ButtonGroup::widget([
				'buttons' => $this->context->menu,
				'type' => 'success' 
		]
		// 'htmlOptions'=>array('class'=> 'pull-right'),
		 );
		?>
	</div>

<?php ActiveForm::end(); ?>

</div>
					</div>
				</div>


			</div>
		</div>
	</div>
	<!-- form code ends here -->
	<script type="text/javascript">
$('#Item_title').on('change', function(){
	
	getShortName();

});

function getShortName(){
	var title = $('#Item_title').val();

	if(title != ''){
		var shortText = "<?php echo $model->short_name?>";
		console.log('shortText'+shortText);
		if(shortText == ''){
		var shortText = jQuery.trim(title).substring(0, 25);
		}
	    
	    console.log(shortText);
		$('#Item_short_name').val(shortText);
		$('#Item_description').val(shortText);
		
	}
}
$('#Item_mrp').on('change', function(){
	var mrp = $('#Item_mrp').val();
	if(mrp != ''){
	
	    $('#Item_sale_price').val(mrp);
	   
	  
	    }
	});
$('#Item_sale_price').on('change', function(){
	var sale_price = $('#Item_sale_price').val();
	var mrp = $('#Item_mrp').val();
	if(sale_price != '' && mrp != ''){
		if(mrp < sale_price){
	  alert('MRP can not be less than sale price');
	    $('#Item_sale_price').val(mrp);
	 
	  
	    }
	}
});
$(document).ready(function () {
	
     getShortName();
    
     
	checkSubCategory();
	checkSubCompany();
    $('#Item_category_id').change(function () {  
    	checkSubCategory();
    });
    $('#Item_company_id').change(function () {  
    	checkSubCompany();
    });
    
 });
function checkSubCompany()
{
    var company_id = $('#Item_company_id').val();
    jQuery.ajax({
       'type': 'POST',
       'url': '<?php echo Ui::to('item/ajaxcompany') ?>',
       'data': {'company_id': company_id},
       'success': function (data) {
          $('#subcompanydisplay').html('');
          $('#subcompanydisplay').html(data);
          <?php if($model->sub_company_id){?>
          $('#Item_sub_company_id').val(<?php echo $model->sub_company_id; ?>);
          <?php }?>
          <?php 
/*
							       * if($model->section_id != ''){?>
							       * var selected_class_id = <?php echo $model->class_id;?>;
							       * var section_id = <?php echo $model->section_id;?>;
							       * if(selected_class_id == class_id)
							       * $('input[type=checkbox][value='+section_id+']').attr('checked',true);
							       * <?php }
							       */
										?>
       },
       'cache': false
    }
    );	
}
function checkSubCategory()
{
    var category_id = $('#Item_category_id').val();
    jQuery.ajax({
       'type': 'POST',
       'url': '<?php echo Ui::to('item/ajaxcategory') ?>',
       'data': {'category_id': category_id},
       'success': function (data) {
          $('#subcategorydisplay').html('');
          $('#subcategorydisplay').html(data);
          <?php if($model->sub_category_id){?>
          $('#Item_sub_category_id').val(<?php echo $model->sub_category_id; ?>);
          <?php }?>
          <?php 
/*
							       * if($model->section_id != ''){?>
							       * var selected_class_id = <?php echo $model->class_id;?>;
							       * var section_id = <?php echo $model->section_id;?>;
							       * if(selected_class_id == class_id)
							       * $('input[type=checkbox][value='+section_id+']').attr('checked',true);
							       * <?php }
							       */
										?>
       },
       'cache': false
    }
    );	
}

</script>
<?php 
/*
       * ?>
       * <script>
       * $('#Item_is_free_item').on('change', function(){
       * if(this.checked==true){
       * $('#select_free_item').show();
       * $('#Item_mrp').val('0.00');
       * $('#Item_sale_price').val('0.00');
       * $('#Item_purchase_price').val('0.00');
       * $('#Item_whole_sale').val('0.00');
       * }else{
       * $('#select_free_item').hide();
       * $('#Item_mrp').val('');
       * $('#Item_sale_price').val('');
       * $('#Item_purchase_price').val('');
       * $('#Item_whole_sale').val('');
       * }
       * });
       *
       * </script>
       */
?>
		</section>