<?php
/**
 * Ported from protected/views/vendor/create.php.
 */

use app\components\Ui;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	'Create',
];
?>
<section class="content-header">
<div class="row">
<div class="col-md-12 margin10">
<a href="<?php echo Ui::to('vendor/create',array('id'=>$id));?>" class="btn btn-primary">Vendor Info</a>
<a href="<?php echo Ui::to('vendor/item',array('id'=>$id));?>" class="btn btn-primary">Vendor Product Info</a>

</div>
</div>

<h1><?php echo 'Create' . ' ' . Html::encode($model->label()); ?></h1>
</section>
<?php
echo $this->render('_form', [
		'model' => $model,
		'usermodel' => $usermodel,'flash'=>$flash,
		'buttons' => 'create']);
?>
