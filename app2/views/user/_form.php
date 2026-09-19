<?php
/**
 * Ported from protected/views/user/_form.php.
 */

use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<!--  form code start here -->

<div class="box-body">

<?php $form = ActiveForm::begin([
			'id' => 'user-form',
		'type' => 'horizontal',
			'enableClientValidation'=>true,	
			'clientOptions'=>[
					'validateOnSubmit'=>true
],
//'enableAjaxValidation' => true,
			'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>

<?php //echo $form->errorSummary($model); ?>


<div class="form-group">
 <?php echo $form->textFieldRow($model,'full_name',['class'=>'form-control','placeholder'=>'Full Name']); ?>
 </div>

<div class="form-group">
<?php echo $form->textFieldRow($model,'username', ['class' => 'form-control','id'=>'inputEmail3','placeholder'=>'Username ']); ?>

 </div>

<div class="form-group">
<?php echo $form->textFieldRow($model,'email', ['class' => 'form-control','id'=>'inputEmail3','placeholder'=>'E mail ']); ?>

 </div>
 
 <div class="form-group">
<?php echo $form->passwordFieldRow($model,'password', ['class' => 'form-control','id'=>'inputEmail3','placeholder'=>'Password ']); ?>

 </div>
<?php if($role_id == null){?>
 <div class="form-group">
<?php 
echo $form->dropDownListRow($model, 'role_id',
			$model->getRoleOptions(),['class' => 'form-control']); ?>
			</div>
			<?php }?>
			

<div class="form-group">
<?php echo $form->textFieldRow($model,'contact_no', ['class' => 'form-control','id'=>'inputEmail3','placeholder'=>'Contact No','maxlength'=>10]); ?>

 </div>

<div class="form-group">
<?php echo $form->dropDownListRow($model,'gender', 
			$model->getGenderOptions(),['class' => 'form-control span5','id'=>'inputEmail3']); ?>
 </div>



<div class="box-footer">

<?php echo Button::widget([
				'buttonType'=>'submit',
				'type'=>'primary',
				'label'=>'Save',
				'htmlOptions'=>['class'=>'btn  btn-orange'],
]); ?>
</div>


<?php ActiveForm::end(); ?>
<!-- form code ends here -->

</div>