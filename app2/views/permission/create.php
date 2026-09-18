<?php
/**
 * Ported from protected/views/permission/create.php.
 */

use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	'Create',
];
?>



<section class="content-header">
  <h1><?php echo 'Create' . ' ' . Html::encode($model->label()); ?> </h1>
  </section>



<section class="content">


<div class="box box-info">
            <div class="box-header with-border">
              <h3 class="box-title">Add Permission</h3>
            </div>
            <!-- /.box-header -->
            <!-- form start -->         
<?php
echo $this->render('_form', [
		'model' => $model,
		'buttons' => 'create']);
?>
</section>