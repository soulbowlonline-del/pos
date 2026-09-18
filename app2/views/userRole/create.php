<?php
/**
 * Ported from protected/views/userRole/create.php.
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
              <h3 class="box-title">Add New User Roles</h3>
            </div>
            <!-- /.box-header -->
            <!-- form start -->         
                   
<?php
echo $this->render('_form', [
		'model' => $model,
		'buttons' => 'create']);
?>
</section>