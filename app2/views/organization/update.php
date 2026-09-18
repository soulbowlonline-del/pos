<?php
/**
 * Ported from protected/views/organization/update.php.
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
<?php
echo $this->render('_form', [
		'model' => $model]);
?>
