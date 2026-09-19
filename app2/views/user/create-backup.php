<?php
/**
 * Ported from protected/views/user/create-backup.php.
 */

use app\widgets\ButtonGroup;
?>
<?php
$this->params['breadcrumbs'] = [
		$model->label(2) => ['index'],
		'Create',
	
];
?>
<div class="page-header">
<h1 class="pull-left">
		<?php echo ' Sign Up' ; ?>
	</h1>


<?php   echo ButtonGroup::widget([
	'buttons'=>$this->context->menu,
	'type'=>'success',
	'htmlOptions'=>['class'=> 'pull-right'],
	]);
?>
<div class="clearfix"></div>
</div>


<?php if(Yii::$app->user->hasFlash('register')): ?>
<div class="alert alert-success">
	<?php echo Yii::$app->user->getFlash('register'); ?>
</div>
<?php else : ?>

<div class="row-fluid">


		<?php
		echo $this->render('_form', [
		'model' => $model,
		'buttons' => 'create']);

?>

</div>
	<?php endif;?>