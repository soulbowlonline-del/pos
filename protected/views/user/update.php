<?php

$this->breadcrumbs = array(
	$model->label(2) => array('index'),
	GxHtml::valueEx($model) => array('view', 'id' => GxActiveRecord::extractPkValue($model, true)),
	Yii::t('app', 'Update'),
);
?>


<section class="content-header">
  <h1><?php echo Yii::t('app', 'Update') . ' ' . GxHtml::encode($model->label()); ?> </h1>
  </section>



<section class="content">


<div class="box box-info">
            <div class="box-header with-border">
              <h3 class="box-title">Update User</h3>
            </div>
            <div class="box-body">
            <!-- /.box-header -->
            <!-- form start -->      

<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
			'id' => 'user-update-form',
		'type' => 'horizontal',
			'enableClientValidation'=>true,	
			'clientOptions'=>array(
					'validateOnSubmit'=>true
),
//'enableAjaxValidation' => true,
			'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>

<?php //echo $form->errorSummary($model); ?>
<br>

<div class="form-group">
 <?php echo $form->textFieldRow($model,'full_name',array('class'=>'form-control','placeholder'=>'Full Name')); ?>
 </div>

<div class="form-group">
<?php echo $form->textFieldRow($model,'username', array('class' => 'form-control','id'=>'inputEmail3','placeholder'=>'Username ')); ?>

 </div>

<div class="form-group">
<?php echo $form->textFieldRow($model,'email', array('class' => 'form-control','id'=>'inputEmail3','placeholder'=>'E mail ')); ?>

 </div>
 

 <div class="form-group">
<?php 
echo $form->dropDownListRow($model, 'role_id',
			$model->getRoleOptions(),array('class' => 'form-control')); ?>
			</div>
			
			

<div class="form-group">
<?php echo $form->textFieldRow($model,'contact_no', array('class' => 'form-control','id'=>'inputEmail3','placeholder'=>'Contact No  ')); ?>

 </div>

<div class="form-group">
<?php echo $form->dropDownListRow($model,'gender', 
			$model->getGenderOptions(),array('class' => 'form-control span5','id'=>'inputEmail3')); ?>
 </div>

 



<div class="clearfix" style="height:30px;"></div>

<div class="form-group">

<?php $this->widget('bootstrap.widgets.TbButton', array(
				'buttonType'=>'submit',
				'type'=>'primary',
				'label'=>'Save',
				'htmlOptions'=>array('class'=>'btn  btn-orange'),
)); ?>
</div>


<?php $this->endWidget(); ?>
<!-- form code ends here -->

</div>
</div>
</section>