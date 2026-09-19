<?php
/**
 * Ported from protected/views/user/changepassword.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\ButtonGroup;
use yii\helpers\Html;
?>
<section class="content">

<div class="page-header">
<h1><?php echo 'Update' . ' ' . Html::encode($model->label()) . ' : ' . Html::encode(Gx::str($model)); ?></h1>
</div>

<div class="form well">

<?php   echo ButtonGroup::widget([
	'buttons'=>$this->context->menu,
	'type'=>'success',
	'htmlOptions'=>['class'=> 'pull-right'],
]);
?>
<div class="clearfix"></div>

<?php if(Yii::$app->user->hasFlash('password')){ ?>

<div class="alert alert-success"><?php echo Yii::$app->user->getFlash('password'); ?>
</div>
<?php } ?>
<?php if(Yii::$app->user->hasFlash('error')){ ?>

<div class="alert alert-danger"><?php echo Yii::$app->user->getFlash('error'); ?>
</div>
<?php } ?>
<div class="form"><?php $form = ActiveForm::begin([
			'id' => 'user-change-form',
			'enableClientValidation'=>true,	
			'clientOptions'=>[
					'validateOnSubmit'=>true
],
//	'action'=>Ui::to('api/user/changepassword'),
			'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>

<?php //echo $form->errorSummary($model); ?> 
 <div class="form-group">
<?php echo $form->passwordFieldRow($model,'password', ['class' => 'form-control','id'=>'inputEmail3','placeholder'=>'Password ']); ?>

 </div>

 <div class="form-group">
<?php echo $form->passwordFieldRow($model,'password_2', ['class' => 'form-control','id'=>'inputEmail3','placeholder'=>'Password ']); ?>

 </div>


<div class="form-actions"><?php echo Button::widget([
				'buttonType'=>'submit',
				'type'=>'primary',
				'label'=>'Save',
]); ?> <?php
ActiveForm::end();
?></div>
</div>
<!-- form -->

</div>
</section>