<?php
/**
 * Ported from protected/views/user/update.php.
 */

use app\components\Gx;
use app\widgets\ActiveForm;
use app\widgets\Button;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	Gx::str($model) => ['view', 'id' => Gx::pk($model)],
	'Update',
];
?>


<section class="content-header">
  <h1><?php echo 'Update' . ' ' . Html::encode($model->label()); ?> </h1>
  </section>



<section class="content">


<div class="box box-info">
            <div class="box-header with-border">
              <h3 class="box-title">Update User</h3>
            </div>
            <div class="box-body">
            <!-- /.box-header -->
            <!-- form start -->      

<?php $form = ActiveForm::begin([
			'id' => 'user-update-form',
		'type' => 'horizontal',
			'enableClientValidation'=>true,	
			'clientOptions'=>[
					'validateOnSubmit'=>true
],
//'enableAjaxValidation' => true,
			'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>

<?php //echo $form->errorSummary($model); ?>
<br>

<div class="form-group">
 <?php echo $form->textFieldRow($model,'full_name',['class'=>'form-control','placeholder'=>'Full Name']); ?>
 </div>

<div class="form-group">
<?php echo $form->textFieldRow($model,'username', ['class' => 'form-control','id'=>'inputEmail3','placeholder'=>'Username ']); ?>

 </div>

<div class="form-group">
<?php echo $form->textFieldRow($model,'email', ['class' => 'form-control','id'=>'inputEmail3','placeholder'=>'E mail ']); ?>

 </div>
 

 <div class="form-group">
<?php 
echo $form->dropDownListRow($model, 'role_id',
			$model->getRoleOptions(),['class' => 'form-control']); ?>
			</div>
			
			

<div class="form-group">
<?php echo $form->textFieldRow($model,'contact_no', ['class' => 'form-control','id'=>'inputEmail3','placeholder'=>'Contact No  ']); ?>

 </div>

<div class="form-group">
<?php echo $form->dropDownListRow($model,'gender', 
			$model->getGenderOptions(),['class' => 'form-control span5','id'=>'inputEmail3']); ?>
 </div>

 



<div class="clearfix" style="height:30px;"></div>

<div class="form-group">

<?php echo Button::widget([
				'buttonType'=>'submit',
				'type'=>'primary',
				'label'=>'Save',
				'htmlOptions'=>['class'=>'btn  btn-orange'],
]); ?>
</div>


<?php ActiveForm::end(); ?>
<!-- form code ends here -->

</div>
</div>
</section>