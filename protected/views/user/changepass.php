

<div class="login-box">
<div class="login-logo">
<a href="#">POS</a>
</div>

<?php   $this->widget('bootstrap.widgets.TbButtonGroup', array(
		'buttons'=>$this->menu,
		'type'=>'success',
		'htmlOptions'=>array('class'=> 'pull-right'),
));
?>

<div class="login-box-body">
        <p class="login-box-msg">Change your Password</p>
 
	


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
			'id' => 'user-change-form',
			'enableClientValidation'=>true,	
			'clientOptions'=>array(
					'validateOnSubmit'=>true
),
//	'action'=>Yii::app()->createUrl('api/user/changepassword'),
			'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>

<?php if(Yii::app()->user->hasFlash('password')){ ?>

<div class="alert alert-success"><?php echo Yii::app()->user->getFlash('password'); ?>
</div>
<?php } ?>
<?php if(Yii::app()->user->hasFlash('error')){ ?>

<div class="alert alert-danger"><?php echo Yii::app()->user->getFlash('error'); ?>
</div>
<?php }?>
<div class="auth-form-body"><?php //echo $form->errorSummary($model); ?>


<div class="form-group has-feedback">
 <?php echo $form->passwordFieldRow($model,'password', array('class' => 'form-control','id'=>'inputEmail3','placeholder'=>'Password ')); ?>
<span class="glyphicon glyphicon-envelope form-control-feedback"></span>

<?php echo $form->error($model,'password');?>
</div>



<div class="form-group has-feedback"> 
<?php echo $form->passwordFieldRow($model,'password_2', array('class' => 'form-control','id'=>'inputEmail3','placeholder'=>'Password ')); ?>
<span class="glyphicon glyphicon-lock form-control-feedback"></span>
<?php echo $form->error($model,'password_2');?>
</div>



</div>


<div class="clearfix" style="height:10px;"></div>
<div class="form-group text-center ">
	<?php $this->widget('bootstrap.widgets.TbButton', array(
						'buttonType'=>'submit',
						'type'=>'primary',
						'label'=>'Save',
						
)); ?> 

</div>


</div>

</div>
    
 




<?php $this->endWidget(); ?>