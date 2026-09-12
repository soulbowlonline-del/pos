<?php

$this->breadcrumbs = array(
	$model->label(2) => array('index'),
	Yii::t('app', 'Create'),
);
?>
<section class="content">
<a href="<?php echo Yii::app()->createUrl('item/create',array('id'=>$id));?>" class="btn btn-primary">Product Info</a>
<a href="<?php echo Yii::app()->createUrl('item/extra',array('id'=>$id));?>" class="btn btn-primary">Extra Info</a>
<a href="<?php echo Yii::app()->createUrl('item/vendor',array('id'=>$id));?>" class="btn btn-primary">Vendor Information</a>
<a href="<?php echo Yii::app()->createUrl('stockLog/admin',array('id'=>$id));?>" class="btn btn-primary">Movement History</a>
<a href="<?php echo Yii::app()->createUrl('orderItem/index',array('id'=>$id));?>" class="btn btn-primary">Order History</a>
<a href="<?php echo Yii::app()->createUrl('itemDetail/create',array('id'=>$id));?>" class="btn btn-primary">Add Subitem</a>
<a href="<?php echo Yii::app()->createUrl('vendorSchemes/add',array('id'=>$id));?>" class="btn btn-primary">Add Vendor Scheme</a>
<div class="page-header">
<h1><?php echo Yii::t('app', 'Create') . ' ' . GxHtml::encode($model->label()); ?></h1>
</div>
<?php
$this->renderPartial('_form', array(
		'model' => $model,
		'buttons' => 'create'));
?></section>