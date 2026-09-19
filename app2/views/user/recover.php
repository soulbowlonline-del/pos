<?php
/**
 * Ported from protected/views/user/recover.php.
 */

use app\components\Ui;
use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<div class="login-box">
<div class="login-logo">
    <a href="#">DAS POS</a>
  </div>
  


<div class="login-box-body">
        <p class="login-box-msg">Recover Your Account</p>
 
	


<?php $form = ActiveForm::begin([
		    'id' => 'recover-form',
				'enableClientValidation'=>true,
        //     'action'=>Ui::to('api/user/login'),
				'focus'=>[$model,'email'],
				'clientOptions'=>[
			'validateOnSubmit'=>true,

]]);
?>
<?php if(Yii::$app->user->hasFlash('recover')){ ?>

<div class="alert alert-success"><?php echo Yii::$app->user->getFlash('recover'); ?>
</div>
<?php } ?>
<?php if(Yii::$app->user->hasFlash('error')){ ?>

<div class="alert alert-danger"><?php echo Yii::$app->user->getFlash('error'); ?>
</div>
<?php } ?>

<div class="auth-form-body"><?php //echo $form->errorSummary($model); ?>


<div class="form-group has-feedback"> 
	
<?php echo $form->textField($model,'email',['placeholder'=>'Email','class'=>'form-control']); ?>

<span class="glyphicon glyphicon-envelope form-control-feedback"></span>

<?php echo $form->error($model,'email');?>
</div>



</div>


<div class="clearfix" style="height:10px;"></div>
<div class="form-group text-center ">
	<?php echo Button::widget([
						'buttonType'=>'submit',
						'type'=>'primary',
						'label'=>'Recover',
						
]); ?> 

</div>


</div>

</div>
    
 




<?php ActiveForm::end(); ?>











