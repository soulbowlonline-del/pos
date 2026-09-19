<?php
/**
 * Ported from protected/views/user/_form-backup.php.
 */

use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<!--  form code start here -->

<div class="form well">

<div class="row">
<div class="span8"><?php $form = ActiveForm::begin([
			'id' => 'user-form',
			'type'=>'horizontal',
			'enableClientValidation'=>true,	
			'clientOptions'=>[
					'validateOnSubmit'=>true
],
//'enableAjaxValidation' => true,
			'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
<p class="help-block pull-left">Fields with <span class="required">*</span>
are required.</p>
<div style="clear: both"></div>
<br>
<?php echo $form->errorSummary($model); ?> <?php echo $form->textFieldRow($model,'full_name',['class'=>'span5']); ?>

<?php echo $form->textFieldRow($model,'username', ['class' => 'form-control','id'=>'inputEmail3','placeholder'=>'username ']); ?>

<?php echo $form->textFieldRow($model,'email', ['class' => 'form-control','id'=>'inputEmail3','placeholder'=>'E mail ']); ?>


<?php echo $form->textFieldRow($model,'contact_no', ['class' => 'form-control','id'=>'inputEmail3','placeholder'=>'Conatact No  ']); ?>


<?php echo $form->textFieldRow($model,'address', ['class' => 'form-control','id'=>'inputEmail3','placeholder'=>'Conatact No  ']); ?>


<?php echo $form->datepickerRow($model, 'date_of_birth',
['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>','options'=>[
							 'format'=>'yyyy-mm-dd',

]])
; ?> <?php echo $form->checkBoxRow($model,'is_passenger',['class'=>'']); ?>


<?php echo $form->checkBoxRow($model,'is_driver',['class'=>'']); ?>


<div class="form-group">

<div class="col-sm-12"><?php echo Button::widget([
				'buttonType'=>'submit',
				'type'=>'primary',
				'label'=>'Save',
				'htmlOptions'=>['class'=>'btn  col-sm-12 btn-orange'],
]); ?></div>
</div>

<?php ActiveForm::end(); ?></div>
<!-- form code ends here -->
</div>
</div>