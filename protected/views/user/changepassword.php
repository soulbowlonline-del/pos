<section class="content">

<div class="page-header">
<h1><?php echo Yii::t('app', 'Update') . ' ' . GxHtml::encode($model->label()) . ' : ' . GxHtml::encode(GxHtml::valueEx($model)); ?></h1>
</div>

<div class="form well">

<?php   $this->widget('bootstrap.widgets.TbButtonGroup', array(
	'buttons'=>$this->menu,
	'type'=>'success',
	'htmlOptions'=>array('class'=> 'pull-right'),
));
?>
<div class="clearfix"></div>

<?php if(Yii::app()->user->hasFlash('password')){ ?>

<div class="alert alert-success"><?php echo Yii::app()->user->getFlash('password'); ?>
</div>
<?php } ?>
<?php if(Yii::app()->user->hasFlash('error')){ ?>

<div class="alert alert-danger"><?php echo Yii::app()->user->getFlash('error'); ?>
</div>
<?php } ?>
<div class="form"><?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
			'id' => 'user-change-form',
			'enableClientValidation'=>true,	
			'clientOptions'=>array(
					'validateOnSubmit'=>true
),
//	'action'=>Yii::app()->createUrl('api/user/changepassword'),
			'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>

<?php //echo $form->errorSummary($model); ?> 
 <div class="form-group">
<?php echo $form->passwordFieldRow($model,'password', array('class' => 'form-control','id'=>'inputEmail3','placeholder'=>'Password ')); ?>

 </div>

 <div class="form-group">
<?php echo $form->passwordFieldRow($model,'password_2', array('class' => 'form-control','id'=>'inputEmail3','placeholder'=>'Password ')); ?>

 </div>


<div class="form-actions"><?php $this->widget('bootstrap.widgets.TbButton', array(
				'buttonType'=>'submit',
				'type'=>'primary',
				'label'=>'Save',
)); ?> <?php
$this->endWidget();
?></div>
</div>
<!-- form -->

</div>
</section>