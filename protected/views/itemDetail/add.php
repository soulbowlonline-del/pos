<?php

$this->breadcrumbs = array(
	$model->label(2) => array('index'),
	Yii::t('app', 'Create'),
);
?>
<section class="content">
<div class="page-header">
<h1><?php echo Yii::t('app', 'Create SubItem'); ?></h1>
</div>
<?php
$this->renderPartial('_form', array(
		'model' => $model,'id'=>$id,
		'buttons' => 'create'));
?>
</section>