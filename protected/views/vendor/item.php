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
<a href="<?php echo Yii::app()->createUrl('vendor/create',array('id'=>$id));?>" class="btn btn-primary">Vendor Info</a>
<a href="<?php echo Yii::app()->createUrl('vendor/item',array('id'=>$id));?>" class="btn btn-primary">Vendor Product Info</a>

</div>
</div>

<h1><?php echo Yii::t('app', 'Create') . ' ' . GxHtml::encode($model->label()); ?></h1>
<?php


$this->widget ( 'bootstrap.widgets.TbButtonGroup', array (
		'buttons' => $this->menu,
		'type' => 'success',
		'htmlOptions' => array (
				'class' => 'pull-right' 
		) 
) );
?>
</section>


	<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
        	<div class="box">
            
            <div class="box-header"><h3 class="box-title">Vendor Products</h3></div>
            
            
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
	<?php echo $form->dropDownListRow ( $vendor, 'item_detail_id', Vendor::getAllItems($id), array ('class' => 'form-control' 	) );
	?>
				
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
if($id != null){
 $this->StartPanel(); ?>
<?php  $this->AddPanel($model->getRelationLabel('itemVendors'), $model->getRelatedDataProvider('itemVendors'),	'itemVendors','itemVendor');?>

<?php  $this->EndPanel();} ?>

</section>