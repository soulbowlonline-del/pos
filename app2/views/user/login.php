<?php
/**
 * Ported from protected/views/user/login.php.
 */

use app\components\Ui;
use app\widgets\ActiveForm;
use app\widgets\Button;
use yii\helpers\Html;
?>

 
	 
    
    <div class="login-box">
<div class="login-logo">
    <a href="#">DAS POS</a>
  </div>
  


<div class="login-box-body">
        <p class="login-box-msg">Sign in to start your session</p>
 
	


<?php $form = ActiveForm::begin([
				'id' => 'login-form',
				'enableClientValidation'=>true,
        //     'action'=>Ui::to('api/user/login'),
				'focus'=>[$model,'username'],
				'clientOptions'=>[
			'validateOnSubmit'=>true,

]]);
?>


<div class="auth-form-body"><?php //echo $form->errorSummary($model); ?>


<div class="form-group has-feedback"> 
<?php
echo $form->textField($model,'username',['maxlength'=>128,'placeholder'=>'Email','class'=>'form-control']);
?>
<span class="glyphicon glyphicon-envelope form-control-feedback"></span>

<?php echo $form->error($model,'username');?>
</div>



<div class="form-group has-feedback"> 
<?php echo $form->passwordField($model,'password',['maxlength'=>512,'placeholder'=>'Password','class'=>'form-control']); ?>
<span class="glyphicon glyphicon-lock form-control-feedback"></span>
<?php echo $form->error($model,'password');?>
</div>



<div class="form-group"> 

<div class="pull-left">
<?php echo $form->checkBoxRow($model,'rememberMe',['style'=>'']); ?>
</div>

<div class="frgt-pswrd pull-right">
<p class="checkbox"><?php echo Html::a('Forgot Password?',Ui::to('user/recover'),['style'=>'']); ?>
</p>

</div>


</div>

</div>


<div class="clearfix" style="height:10px;"></div>
<div class="form-group text-center ">
	<?php echo Button::widget([
						'buttonType'=>'submit',
						'type'=>'primary',
						'label'=>'Login',
						
]); ?> 

</div>


</div>

</div>
    
 




<?php ActiveForm::end(); ?>


