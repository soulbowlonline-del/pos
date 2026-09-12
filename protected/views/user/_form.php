<!--  form code start here -->

<div class="box-body">

<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
			'id' => 'user-form',
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
<?php echo $form->passwordFieldRow($model,'password', array('class' => 'form-control','id'=>'inputEmail3','placeholder'=>'Password ')); ?>

 </div>
<?php if($role_id == null){?>
 <div class="form-group">
<?php 
echo $form->dropDownListRow($model, 'role_id',
			$model->getRoleOptions(),array('class' => 'form-control')); ?>
			</div>
			<?php }?>
			

<div class="form-group">
<?php echo $form->textFieldRow($model,'contact_no', array('class' => 'form-control','id'=>'inputEmail3','placeholder'=>'Contact No','maxlength'=>10)); ?>

 </div>

<div class="form-group">
<?php echo $form->dropDownListRow($model,'gender', 
			$model->getGenderOptions(),array('class' => 'form-control span5','id'=>'inputEmail3')); ?>
 </div>



<div class="box-footer">

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