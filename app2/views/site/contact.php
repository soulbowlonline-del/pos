<?php
/**
 * Ported from protected/views/site/contact.php.
 */

use app\components\Ui;
use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<?php
if(Yii::$app->user->hasFlash('contact')) { ?>
<div class="alert alert-success">
<?php echo Yii::$app->user->getFlash('contact');?>
</div>
<?php }else {?>
<div class="text-center margin-top-30">
<h1>Contact Us</h1>
<p>If you have any business inquiries or other questions, please feel free to contact us.</p>
</div>
<hr />
<div class="form">

	<?php $form = ActiveForm::begin([
			'id'=>'contact-form',
			'enableClientValidation'=>true,
			'clientOptions'=>[
		'validateOnSubmit'=>true,
],
]); ?>


	<?php echo $form->errorSummary($model); ?>
<div class="col-md-5">
	<div class="form-group">
		<?php echo $form->textField($model,'name',['class'=>'form-control','placeholder'=>'Name']); ?>
		<?php echo $form->error($model,'name'); ?>
	</div>

	<div class="form-group">
		<?php echo $form->textField($model,'email',['class'=>'form-control','placeholder'=>'Email']); ?>
		<?php echo $form->error($model,'email'); ?>
	</div>

	<div class="form-group">
		<?php echo $form->textField($model,'subject',['class'=>'form-control','placeholder'=>'Subject']); ?>
		<?php echo $form->error($model,'subject'); ?>
	</div>
	
	<?php if(function_exists('imagecreatetruecolor')): ?>
	<div class="form-group">
		<?php echo $form->labelEx($model,'verifyCode'); ?>
		<div>
			<?php echo \yii\captcha\Captcha::widget(["name" => 'verifyCode', 'captchaAction' => Ui::toYii2Id('site') . '/captcha', 'template' => '{image}']); ?>
			<?php echo $form->textField($model,'verifyCode'); ?>
		</div>
		<?php echo $form->error($model,'verifyCode'); ?>
	</div>
	<?php endif; ?>

</div>

<div class="col-md-7">
	<div class="form-group">
		<?php echo $form->textArea($model,'body',['placeholder'=>'Message','class'=>'form-control contact-body']); ?>
		<?php echo $form->error($model,'body'); ?>
	</div>
</div>
<div class="clearfix"></div>
	<div class="form-group text-center">
<?php echo Button::widget([
'buttonType'=>'submit',
'type'=>'primary',
'label'=>'Submit',
]); ?>
</div>
</div>
	<?php ActiveForm::end(); ?>
<h4 class="text-center margin-top-30"><span class="highlight">Ride4Ride.com</span> is operated by Whn LLC</h4>

<!-- form -->
<?php } ?>
