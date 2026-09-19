<?php
/**
 * Ported from protected/views/itemDetail/add.php.
 */


?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	'Create',
];
?>
<section class="content">
<div class="page-header">
<h1><?php echo 'Create SubItem'; ?></h1>
</div>
<?php
echo $this->render('_form', [
		'model' => $model,'id'=>$id,
		'buttons' => 'create']);
?>
</section>