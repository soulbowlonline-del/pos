<?php
/**
 * Ported from protected/views/mrsDetail/assign.php.
 */

use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	'Create',
];
?>
<section class="content">
<div class="page-header">
<h1><?php echo 'Assign Vendor' ?></h1>
</div>
<!--  form code start here -->
<div class="form well">


<?php $form = ActiveForm::begin([
	'id' => 'mrs-assign-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>

<?php echo $form->dropDownListRow($model, 'vendor_id',$model->getVendorOptions(),['class'=>'form-control']); ?>







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