<?php

$this->breadcrumbs = array(
	$model->label(2) => array('index'),
	Yii::t('app', 'Create'),
);
?>
<section class="content-header">
<div class="row">
<div class="col-md-12 margin10">
<a href="<?php echo Yii::app()->createUrl('vendor/create',array('id'=>$id));?>" class="btn btn-primary">Vendor Info</a>
<a href="<?php echo Yii::app()->createUrl('vendor/item',array('id'=>$id));?>" class="btn btn-primary">Vendor Product Info</a>

</div>
</div>

<h1><?php echo Yii::t('app', 'Create') . ' ' . GxHtml::encode($model->label()); ?></h1>
</section>
<?php
$this->renderPartial('_form', array(
		'model' => $model,
		'usermodel' => $usermodel,'flash'=>$flash,
		'buttons' => 'create'));
?>
