<?php
/**
 * Ported from protected/views/item/import2.php.
 */

use Yii;
use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	'Import',
];
?>
<section class="content">
<div class="page-header">
<h1><?php echo 'Import Vendor'; ?>
  <a href="<?php echo '/themes/bar';?>/img/itemvendor.csv" class="btn btn-info">Sample</a></h1>

</div>
<!--  form code start here -->
<div class="form well">


<?php $form = ActiveForm::begin([
	'id' => 'item-category-file-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	
<?php if(Yii::$app->user->hasFlash('success')){ ?>

<div class="alert alert-success"><?php echo Yii::$app->user->getFlash('success'); ?>
</div>
<?php } ?>
<?php if(Yii::$app->user->hasFlash('danger')){ ?>

<div class="alert alert-danger"><?php echo Yii::$app->user->getFlash('danger'); ?>
</div>
<?php } ?>
	<?php //echo $form->errorSummary($model); ?>



<?php echo $form->fileFieldRow($model, 'csv_file'); ?>

	<div class="form-actions">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Save',
		]); ?>
	</div>

<?php ActiveForm::end(); ?>

</div>
<!-- form code ends here -->

</section>