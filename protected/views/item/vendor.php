<?php
$this->breadcrumbs = array (
		$model->label ( 2 ) => array (
				'index' 
		),
		Yii::t ( 'app', 'Create' ) 
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


		<h1 class="pull-left"><?php echo Yii::t('app', 'Vendor Info'); ?></h1>
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


<?php

$form = $this->beginWidget ( 'bootstrap.widgets.TbActiveForm', array (
		'id' => 'item-vendor-form',
		'type' => 'horizontal',
		//'enableAjaxValidation' => true,
		'htmlOptions' => array (
				'enctype' => 'multipart/form-data' 
		) 
) );
?>
<?php if(Yii::app()->user->hasFlash('success')){ ?>

<div class="alert alert-success"><?php echo Yii::app()->user->getFlash('success'); ?>
</div>
<?php } ?>
<?php if(Yii::app()->user->hasFlash('error')){ ?>

<div class="alert alert-danger"><?php echo Yii::app()->user->getFlash('error'); ?>
</div>
<?php } ?>
	<p class="help-block">
			Fields with <span class="required">*</span> are required.
		</p>

	<?php echo $form->errorSummary($vendor); ?>
	<div class="col-md-6">
	<div class="form-group">
<label for="inputEmail3" class="control-label col-md-3">
Vendor
<span class="required">*</span>
</label>
<div class="col-md-9">
<input type="hidden" name="ItemVendor[vendor_id]" id="item_vendor_id">

   <?php 
   
   $this->widget('ext.typeahead.TbTypeAhead',array(
              'model' => $model,
              'attribute' => 'to_user_id',
              'enableHogan' => true,
              
              'options' => array(
                           array(
                                         'limit' => 10,
                                         'name' => 'to_user_id',
                                         'valueKey' => 'name',
                                         'remote' => array(
                                                       'url' => Yii::app()->createUrl('/item/vendorList') . '?id='.$id.'&& term=%QUERY',
                                         ),
                           		'template' => '<p>{{name}}</p>',
                                  //     'template' => '<p>{{name}}<strong> [ {{username}} ] </strong> - {{user_id}}</p>',
                                         'engine' => new CJavaScriptExpression('Hogan'),
                           )
              ),
              
               'events' => array(
                           'selected' => new CJavascriptExpression("function(obj, datum, name) {
                    var    uid = datum.user_id;
                           $('#item_vendor_id').val(uid);
          
         }")
              ), 
   ));
   
   
   ?>
   <?php echo $form->error($model,'vendor_id');?></div>
</div>
	
	
	<?php
	
/* echo $form->dropDownListRow ( $vendor, 'vendor_id', Item::getAllVendors ($id), array (
			'class' => 'form-control' 
	) ); */
	?>
			<?php  if($model->sale_price != ''){
				$vendor->vendor_price = $model->purchase_price;
			}
			if( $vendor->item_code == '')
			$vendor->item_code = User::randomBarcode('5');?>	
<?php echo $form->textFieldRow($vendor,'item_code',array('class'=>'form-control','maxlength'=>255)); ?>
<?php echo $form->textFieldRow($vendor,'vendor_price',array('class'=>'form-control','maxlength'=>255)); ?>
<div class="form-actions">
		<?php
		
$this->widget ( 'bootstrap.widgets.TbButton', array (
				'buttonType' => 'submit',
				'type' => 'primary',
				'label' => 'Save' 
		) );
		?>
		<?php


$this->widget ( 'bootstrap.widgets.TbButtonGroup', array (
		'buttons' => $this->menu,
		'type' => 'success',
		/* 'htmlOptions' => array (
				'class' => 'pull-right' 
		) */ 
) );
?>
	</div>
		</div>







	

<?php $this->endWidget(); ?>

</div>
</div>
</div>
            
            
            </div>
        </div>    
     </div>


<?php
if($id){
 $this->StartPanel(); ?>
<?php  $this->AddPanel($model->getRelationLabel('itemVendors'), $model->getRelatedDataProvider('itemVendors'),	'itemVendors','itemVendor');?>

<?php  $this->EndPanel(); 
}?>

</section>