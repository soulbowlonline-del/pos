<?php
/**
 * Ported from protected/views/bill/update.php.
 */

use app\components\Gx;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	Gx::str($model) => ['view', 'id' => Gx::pk($model)],
	'Update',
];
?>


<section class="content-header">
  <h1><?php echo 'Update' . ' ' . Html::encode($model->label()) . ' : ' . Html::encode(Gx::str($model)); ?></h1>

  </section>



<section class="content">


<div class="box box-info">
            <div class="box-header with-border">
              <h3 class="box-title"><?php echo 'Update' . ' ' . Html::encode($model->label()) . ' : ' . Html::encode(Gx::str($model)); ?></h3>
            </div>
            <!-- /.box-header -->
            <!-- form start -->    
<?php
echo $this->render('_form', [
		'model' => $model]);
?>
</div>
</section>