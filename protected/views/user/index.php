<?php

$this->breadcrumbs = array(
	User::label(2),
	Yii::t('app', 'Index'),
);
?>

<div class="page-header">
<h1 class="pull-left">
<?php echo GxHtml::encode(User::label(2)); ?>
</h1>
<?php   $this->widget('bootstrap.widgets.TbButtonGroup', array(
	'buttons'=>$this->menu,
	'type'=>'success',
	'htmlOptions'=>array('class'=> 'pull-right btns'),
	));
?>
<div class="clearfix"></div>
</div>

<?php 
$this->renderPartial('_list', array(
		'dataProvider'=>$dataProvider,
));

