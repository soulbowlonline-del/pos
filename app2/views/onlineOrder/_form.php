<?php
/**
 * Ported from protected/views/onlineOrder/_form.php.
 */

use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<!--  form code start here -->
<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">

				<div class="box-header">
					<h3 class="box-title">Online Order</h3>
				</div>


				<div class="box-body">
					<div class="row">
						<div class="col-md-12">


<?php $form = ActiveForm::begin([
	'id' => 'online-order-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>



<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions(),['class'=>'form-control']); ?>




	<div class="form-actions">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Save',
		]); ?>
	</div>

<?php ActiveForm::end(); ?>

</div>
					</div>
				</div>


			</div>
		</div>
	</div>
</section>
<!-- form code ends here -->