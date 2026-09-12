<?php if(Yii::app()->user->hasFlash('contact')) { ?>
<div class="alert alert-success">
<?php echo Yii::app()->user->getFlash('contact');?>
</div>
<?php }else {?>
<div class="text-center margin-top-30">
<h1>Contact Us</h1>
<p>If you have any business inquiries or other questions, please feel free to contact us.</p>
</div>
<hr />
<div class="form">

	<?php $form=$this->beginWidget('CActiveForm', array(
			'id'=>'contact-form',
			'enableClientValidation'=>true,
			'clientOptions'=>array(
		'validateOnSubmit'=>true,
),
)); ?>


	<?php echo $form->errorSummary($model); ?>
<div class="col-md-5">
	<div class="form-group">
		<?php echo $form->textField($model,'name',array('class'=>'form-control','placeholder'=>'Name')); ?>
		<?php echo $form->error($model,'name'); ?>
	</div>

	<div class="form-group">
		<?php echo $form->textField($model,'email',array('class'=>'form-control','placeholder'=>'Email')); ?>
		<?php echo $form->error($model,'email'); ?>
	</div>

	<div class="form-group">
		<?php echo $form->textField($model,'subject',array('class'=>'form-control','placeholder'=>'Subject')); ?>
		<?php echo $form->error($model,'subject'); ?>
	</div>
	
	<?php if(CCaptcha::checkRequirements()): ?>
	<div class="form-group">
		<?php echo $form->labelEx($model,'verifyCode'); ?>
		<div>
			<?php $this->widget('CCaptcha'); ?>
			<?php echo $form->textField($model,'verifyCode'); ?>
		</div>
		<?php echo $form->error($model,'verifyCode'); ?>
	</div>
	<?php endif; ?>

</div>

<div class="col-md-7">
	<div class="form-group">
		<?php echo $form->textArea($model,'body',array('placeholder'=>'Message','class'=>'form-control contact-body')); ?>
		<?php echo $form->error($model,'body'); ?>
	</div>
</div>
<div class="clearfix"></div>
	<div class="form-group text-center">
<?php $this->widget('bootstrap.widgets.TbButton', array(
'buttonType'=>'submit',
'type'=>'primary',
'label'=>'Submit',
)); ?>
</div>
</div>
	<?php $this->endWidget(); ?>
<h4 class="text-center margin-top-30"><span class="highlight">Ride4Ride.com</span> is operated by Whn LLC</h4>

<!-- form -->
<?php } ?>
