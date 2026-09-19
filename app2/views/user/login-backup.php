<?php
/**
 * Ported from protected/views/user/login-backup.php.
 */

use app\components\Ui;
use app\widgets\ActiveForm;
use app\widgets\Button;
use yii\helpers\Html;
?>

<div class="login_container">
<div class="row-fluid">
<div class="auth-form-header">Login</div>

<?php $form = ActiveForm::begin([
				'id' => 'login-form',
				'enableClientValidation'=>true,
        //     'action'=>Ui::to('api/user/login'),
				'focus'=>[$model,'username'],
				'clientOptions'=>[
			'validateOnSubmit'=>true,

]]);
?>
<div class="auth-form-body"><?php echo $form->errorSummary($model); ?>
<div class="row-fluid"><?php
echo $form->textField($model,'username',['maxlength'=>128,'placeholder'=>'Email','class'=>'input-xlarge span12']);
?></div>
<div class="row-fluid"><?php echo $form->passwordField($model,'password',['maxlength'=>512,'placeholder'=>'Password','class'=>'input-xlarge span12']); ?>
</div>

<div class="row-fluid"><?php //echo $form->textField($model,'ph_no',array('maxlength'=>512,'placeholder'=>'Phone','class'=>'input-xlarge span12')); ?>
</div>
<div class="row-fluid">
<div class="pull-left"><?php echo $form->checkBoxRow($model,'rememberMe',['style'=>'']); ?>
</div>
<div class="frgt-pswrd pull-right">
<p><?php echo Html::a('Forgot Password?',Ui::to('user/recover'),['style'=>'']); ?>
</p>
</div>
</div>


<div class="form-actions "><?php echo Button::widget([
						'buttonType'=>'submit',
						'type'=>'primary',
						'label'=>'Login',
]); ?> 

</div>
</div>


<?php ActiveForm::end(); ?></div>

</div>

