<?php
/**
 * Ported from protected/views/discount/create.php.
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

<h1><?php echo 'Create' . ' ' . Html::encode($model->label()); ?></h1>

</section>
<?php
echo $this->render('_form', [
		'model' => $model,
		'buttons' => 'create']);
?>
