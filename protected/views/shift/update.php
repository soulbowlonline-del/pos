<?php

$this->breadcrumbs = array(
	$model->label(2) => array('index'),
	GxHtml::valueEx($model) => array('view', 'id' => GxActiveRecord::extractPkValue($model, true)),
	Yii::t('app', 'Update'),
);
?>

<section class="content-header">

<h1><?php echo Yii::t('app', 'Update Details'); ?></h1>
</section>

<?php
$this->renderPartial('_form', array(
		'model' => $model));
?>
