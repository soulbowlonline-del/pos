<?php
/**
 * Ported from protected/views/itemCompany/subupdate.php.
 */

use app\components\Gx;
use app\widgets\ActiveForm;
use app\widgets\Button;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	Gx::str($model) => ['view', 'id' => Gx::pk($model)],
	'Update',
];
?>
<section class="content">
<div class="page-header">
<h1><?php echo 'Update' . ' ' . Html::encode($model->label()) . ' : ' . Html::encode(Gx::str($model)); ?></h1>

</div>
<!--  form code start here -->
<div class="form well">


<?php $form = ActiveForm::begin([
	'id' => 'item-company-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'title',['class'=>'form-control','maxlength'=>255]); ?>


<?php echo $form->dropDownListRow($model, 'parent_id',
			$model->getCompanyOptions(),['class'=>'form-control','empty'=>'No Parent']); ?>





<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions()); ?>







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