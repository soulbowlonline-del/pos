<?php

$this->breadcrumbs = array(
	$model->label(2) => array('index'),
	Yii::t('app', 'Create'),
);
?>
<section class="content-header">
<div class="row">
<div class="col-md-12 margin10">
<a href="<?php echo Yii::app()->createUrl('item/create',array('id'=>$id));?>" class="btn btn-primary">Product Info</a>
<a href="<?php echo Yii::app()->createUrl('item/extra',array('id'=>$id));?>" class="btn btn-primary">Extra Info</a>
<a href="<?php echo Yii::app()->createUrl('item/vendor',array('id'=>$id));?>" class="btn btn-primary">Vendor Information</a>
<a href="<?php echo Yii::app()->createUrl('stockLog/admin',array('id'=>$id));?>" class="btn btn-primary">Movement History</a>
<a href="<?php echo Yii::app()->createUrl('orderItem/index',array('id'=>$id));?>" class="btn btn-primary">Order History</a>
<a href="<?php echo Yii::app()->createUrl('itemDetail/create',array('id'=>$id));?>" class="btn btn-primary">Add Subitem</a>
<a href="<?php echo Yii::app()->createUrl('vendorSchemes/add',array('id'=>$id));?>" class="btn btn-primary">Add Vendor Scheme</a>

</div>
</div>
<h1 class="pull-left"><?php echo Yii::t('app', 'Create SubItem'); ?>
	<a href="<?php echo Yii::app()->createUrl('itemDetail/admin',array('id'=>$id));?>" class="btn btn-info pull-right" >List</a>
</h1>
</section>
<?php
$this->renderPartial('_form', array(
		'model' => $model,'id'=>$id,
		'buttons' => 'create'));
?>
