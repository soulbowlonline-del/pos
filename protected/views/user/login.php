
 
	 
    
    <div class="login-box">
<div class="login-logo">
    <a href="#">DAS POS</a>
  </div>
  


<div class="login-box-body">
        <p class="login-box-msg">Sign in to start your session</p>
 
	


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
				'id' => 'login-form',
				'enableClientValidation'=>true,
        //     'action'=>$this->createUrl('api/user/login'),
				'focus'=>array($model,'username'),
				'clientOptions'=>array(
			'validateOnSubmit'=>true,

)));
?>


<div class="auth-form-body"><?php //echo $form->errorSummary($model); ?>


<div class="form-group has-feedback"> 
<?php
echo $form->textField($model,'username',array('maxlength'=>128,'placeholder'=>'Email','class'=>'form-control'));
?>
<span class="glyphicon glyphicon-envelope form-control-feedback"></span>

<?php echo $form->error($model,'username');?>
</div>



<div class="form-group has-feedback"> 
<?php echo $form->passwordField($model,'password',array('maxlength'=>512,'placeholder'=>'Password','class'=>'form-control')); ?>
<span class="glyphicon glyphicon-lock form-control-feedback"></span>
<?php echo $form->error($model,'password');?>
</div>



<div class="form-group"> 

<div class="pull-left">
<?php echo $form->checkBoxRow($model,'rememberMe',array('style'=>'')); ?>
</div>

<div class="frgt-pswrd pull-right">
<p class="checkbox"><?php echo CHtml::link('Forgot Password?',$this->createUrl('user/recover'),array('style'=>'')); ?>
</p>

</div>


</div>

</div>


<div class="clearfix" style="height:10px;"></div>
<div class="form-group text-center ">
	<?php $this->widget('bootstrap.widgets.TbButton', array(
						'buttonType'=>'submit',
						'type'=>'primary',
						'label'=>'Login',
						
)); ?> 

</div>


</div>

</div>
    
 




<?php $this->endWidget(); ?>


