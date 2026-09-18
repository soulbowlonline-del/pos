<?php
/**
 * Ported from protected/views/shift/update.php.
 */

use app\components\Gx;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	Gx::str($model) => ['view', 'id' => Gx::pk($model)],
	'Update',
];
?>

<section class="content-header">

<h1><?php echo 'Update Details'; ?></h1>
</section>

<?php
echo $this->render('_form', [
		'model' => $model]);
?>
