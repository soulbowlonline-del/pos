<?php
$this->breadcrumbs = array(
		$model->label(2) => array('index'),
		Yii::t('app', 'Create'),
	
);
?>
<div class="page-header">
<h1 class="pull-left">
		<?php echo Yii::t('app', ' Sign Up') ; ?>
	</h1>


<?php   $this->widget('bootstrap.widgets.TbButtonGroup', array(
	'buttons'=>$this->menu,
	'type'=>'success',
	'htmlOptions'=>array('class'=> 'pull-right'),
	));
?>
<div class="clearfix"></div>
</div>


<?php if(Yii::app()->user->hasFlash('register')): ?>
<div class="alert alert-success">
	<?php echo Yii::app()->user->getFlash('register'); ?>
</div>
<?php else : ?>

<div class="row-fluid">


		<?php
		$this->renderPartial('_form', array(
		'model' => $model,
		'buttons' => 'create'));

?>

</div>
	<?php endif;?>