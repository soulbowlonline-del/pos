<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">

				<div class="box-header">
					<h3 class="box-title">Create Bill</h3>
				</div>


				<div class="box-body">
					<div class="row">
						<div class="col-md-12">
<?php if(Yii::app()->user->hasFlash('success')){ ?>

<div class="alert alert-success"><?php echo Yii::app()->user->getFlash('success'); ?>
</div>
<?php } ?>
<?php if(Yii::app()->user->hasFlash('error')){ ?>

<div class="alert alert-danger"><?php echo Yii::app()->user->getFlash('error'); ?>
</div>
<?php } ?>
<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'bill-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php //echo $form->errorSummary($model); ?>
<div class="col-md-6">

<?php echo $form->textFieldRow($model,'bill_no',array('class'=>'span5','maxlength'=>255)); ?>


<?php  echo $form->fileFieldRow($model,'image_file1'); ?>
</div>
<div class="col-md-6">
<?php  echo $form->fileFieldRow($model,'image_file2'); ?>

</div>




<div class="col-md-12">

	<div class="form-actions">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Save',
		)); ?>
	</div>
</div>
<?php $this->endWidget(); ?>
</div>
</div>
</div>
</div>
</div>
</div>
</section>
<!-- form code ends here -->