<!--  form code start here -->

<div class="form well">

<div class="row">
<div class="span8"><?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
			'id' => 'user-form',
			'type'=>'horizontal',
			'enableClientValidation'=>true,	
			'clientOptions'=>array(
					'validateOnSubmit'=>true
),
//'enableAjaxValidation' => true,
			'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
<p class="help-block pull-left">Fields with <span class="required">*</span>
are required.</p>
<div style="clear: both"></div>
<br>
<?php echo $form->errorSummary($model); ?> <?php echo $form->textFieldRow($model,'full_name',array('class'=>'span5')); ?>

<?php echo $form->textFieldRow($model,'username', array('class' => 'form-control','id'=>'inputEmail3','placeholder'=>'username ')); ?>

<?php echo $form->textFieldRow($model,'email', array('class' => 'form-control','id'=>'inputEmail3','placeholder'=>'E mail ')); ?>


<?php echo $form->textFieldRow($model,'contact_no', array('class' => 'form-control','id'=>'inputEmail3','placeholder'=>'Conatact No  ')); ?>


<?php echo $form->textFieldRow($model,'address', array('class' => 'form-control','id'=>'inputEmail3','placeholder'=>'Conatact No  ')); ?>


<?php echo $form->datepickerRow($model, 'date_of_birth',
array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>','options'=>array(
							 'format'=>'yyyy-mm-dd',

)))
; ?> <?php echo $form->checkBoxRow($model,'is_passenger',array('class'=>'')); ?>


<?php echo $form->checkBoxRow($model,'is_driver',array('class'=>'')); ?>


<div class="form-group">

<div class="col-sm-12"><?php $this->widget('bootstrap.widgets.TbButton', array(
				'buttonType'=>'submit',
				'type'=>'primary',
				'label'=>'Save',
				'htmlOptions'=>array('class'=>'btn  col-sm-12 btn-orange'),
)); ?></div>
</div>

<?php $this->endWidget(); ?></div>
<!-- form code ends here -->
</div>
</div>