<?php
/**
 * Ported from protected/views/purchaseOrder/create.php.
 */

use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	'Create',
];
?>
<div class="page-header">
<h1><?php echo 'Create' . ' ' . Html::encode($model->label()); ?></h1>
</div>
<?php
echo $this->render('_form', [
		'model' => $model,
		'buttons' => 'create']);
?>