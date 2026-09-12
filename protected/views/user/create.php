<?php

$this->breadcrumbs = array(
	$model->label(2) => array('index'),
	Yii::t('app', 'Create'),
);
?>


<section class="content-header">
  <h1><?php echo Yii::t('app', 'Create') . ' ' . GxHtml::encode($model->label()); ?> </h1>
  </section>



<section class="content">


<div class="box box-info">
            <div class="box-header with-border">
              <h3 class="box-title">Add New User</h3>
            </div>
            <!-- /.box-header -->
            <!-- form start -->         
<?php
$this->renderPartial('_form', array(
		'model' => $model,'role_id'=>$role_id,
		'buttons' => 'create'));
?>
</section>