<?php
/**
 * Ported from protected/views/customer/sendemail.php.
 */

use Yii;
use app\components\Ui;
use app\widgets\ActiveForm;
use app\widgets\Button;
use yii\helpers\Html;
?>
<section class="content-header">

<h1><?php echo 'Send Email' . ' ' . Html::encode($model->label()); ?></h1>

</section>

<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">

				<div class="box-header">
					<h3 class="box-title">Email</h3>
				</div>


				<div class="box-body">
					<div class="row">
						<div class="col-md-12">

<?php if(Yii::$app->user->hasFlash('success')){ ?>

<div class="alert alert-success"><?php echo Yii::$app->user->getFlash('success'); ?>
</div>
<?php } ?>
<?php if(Yii::$app->user->hasFlash('error')){ ?>

<div class="alert alert-danger"><?php echo Yii::$app->user->getFlash('error'); ?>
</div>
<?php } ?>
<?php $form = ActiveForm::begin([
	'id' => 'customer-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>

	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php //echo $form->errorSummary($model); ?>
<div class="col-md-6">
<?php echo $form->textFieldRow($model,'subject',['class'=>'form-control','maxlength'=>255]); ?>
<?php echo $form->ckEditorRow($model,'message', ['options'=>['fullpage'=>'js:true', 'width'=>'640', 'resize_maxWidth'=>'640','resize_minWidth'=>'320']]);?>
<?php echo $form->fileFieldRow($model,'attach_file'); ?>

</div>

<div class="col-md-6">

			</div>
			<div class="clearfix"></div>
<div class="form-actions">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Send',
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
<script>

     $( document ).ready(function() {
    	 var country = $('#Customer_country_id').val();
    		checkStates(country);
    		
    	});
$('#Customer_country_id').change(function(){
	var country = $('#Customer_country_id').val();
	checkStates(country);
});

function checkStates(country){
	 jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('country/getStates') ?>',
	       'data': {'country': country},
	       'success': function (data) {
	    	   $("#Customer_state_id").empty();
	    	   $("#Customer_city_id").empty();
	          $('#Customer_state_id').html(data);
	          var state = $('#Customer_state_id').val();
	    		checkCities(state);
	          <?php
											/*
											 * if($model->section_id != ''){?>
											 * var selected_class_id = <?php echo $model->class_id;?>;
											 * var section_id = <?php echo $model->section_id;?>;
											 * if(selected_class_id == class_id)
											 * $('input[type=checkbox][value='+section_id+']').attr('checked',true);
											 * <?php }
											 */
											?>
	       },
	       'cache': false
	    }
	    );
}
$('#Customer_state_id').change(function(){
	var state = $('#Customer_state_id').val();
	checkCities(state);
});

function checkCities(state){
	 jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('country/getCities') ?>',
	       'data': {'state': state},
	       'success': function (data) {
	    	   $("#Customer_city_id").empty();
	          $('#Customer_city_id').html(data);
	          <?php
											/*
											 * if($model->section_id != ''){?>
											 * var selected_class_id = <?php echo $model->class_id;?>;
											 * var section_id = <?php echo $model->section_id;?>;
											 * if(selected_class_id == class_id)
											 * $('input[type=checkbox][value='+section_id+']').attr('checked',true);
											 * <?php }
											 */
											?>
	       },
	       'cache': false
	    }
	    );
}


     </script>