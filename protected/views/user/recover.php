<div class="login-box">
<div class="login-logo">
    <a href="#">DAS POS</a>
  </div>
  


<div class="login-box-body">
        <p class="login-box-msg">Recover Your Account</p>
 
	


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
		    'id' => 'recover-form',
				'enableClientValidation'=>true,
        //     'action'=>$this->createUrl('api/user/login'),
				'focus'=>array($model,'email'),
				'clientOptions'=>array(
			'validateOnSubmit'=>true,

)));
?>
<?php if(Yii::app()->user->hasFlash('recover')){ ?>

<div class="alert alert-success"><?php echo Yii::app()->user->getFlash('recover'); ?>
</div>
<?php } ?>
<?php if(Yii::app()->user->hasFlash('error')){ ?>

<div class="alert alert-danger"><?php echo Yii::app()->user->getFlash('error'); ?>
</div>
<?php } ?>

<div class="auth-form-body"><?php //echo $form->errorSummary($model); ?>


<div class="form-group has-feedback"> 
	
<?php echo $form->textField($model,'email',array('placeholder'=>'Email','class'=>'form-control')); ?>

<span class="glyphicon glyphicon-envelope form-control-feedback"></span>

<?php echo $form->error($model,'email');?>
</div>



</div>


<div class="clearfix" style="height:10px;"></div>
<div class="form-group text-center ">
	<?php $this->widget('bootstrap.widgets.TbButton', array(
						'buttonType'=>'submit',
						'type'=>'primary',
						'label'=>'Recover',
						
)); ?> 

</div>


</div>

</div>
    
 




<?php $this->endWidget(); ?>











