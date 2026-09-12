<?php

$this->breadcrumbs = array(
	$model->label(2) => array('index'),
	Yii::t('app', 'Import'),
);
?>
<section class="content">
<div class="page-header">
<h1><?php echo Yii::t('app', 'Import') . ' ' . GxHtml::encode($model->label()); ?>
  <a href="<?php echo Yii::app()->theme->baseUrl;?>/img/designation.csv" class="btn btn-info">Sample</a></h1>

</div>
<!--  form code start here -->
<div class="form well">


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'item-category-file-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	
<?php if(Yii::app()->user->hasFlash('success')){ ?>

<div class="alert alert-success"><?php echo Yii::app()->user->getFlash('success'); ?>
</div>
<?php } ?>
<?php if(Yii::app()->user->hasFlash('danger')){ ?>

<div class="alert alert-danger"><?php echo Yii::app()->user->getFlash('danger'); ?>
</div>
<?php } ?>
	<?php //echo $form->errorSummary($model); ?>



<?php echo $form->fileFieldRow($model, 'csv_file'); ?>

	<div class="form-actions">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Save',
		)); ?>
	</div>

<?php $this->endWidget(); ?>

</div>
<!-- form code ends here -->

</section>