<?php
/**
 * Ported from protected/views/mrs/create.php.
 */

use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	'Create',
];
?>
<section class="content">
<div class="page-header">
<h1><?php echo 'Create' . ' ' . Html::encode($model->label()); ?></h1>
</div>
<?php
echo $this->render('_form', [
		'model' => $model,
		'buttons' => 'create']);
?>
</section>