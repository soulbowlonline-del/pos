<?php
/**
 * Ported from protected/views/organization/_form.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\City;
use app\models\Country;
use app\models\State;
use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<!--  form code start here -->
<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">

				<div class="box-header">
					<h3 class="box-title">Organization</h3>
				</div>


				<div class="box-body">
					<div class="row">
						<div class="col-md-12">


<?php $form = ActiveForm::begin([
	'id' => 'organization-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>


<?php echo $form->textFieldRow($model,'title',['class'=>'form-control','maxlength'=>255]); ?>


<?php echo $form->textFieldRow($model,'link',['class'=>'form-control','maxlength'=>255]); ?>


<?php echo $form->textFieldRow($model,'email',['class'=>'form-control','maxlength'=>255]); ?>


<?php echo $form->textFieldRow($model,'contact_no',['class'=>'form-control','maxlength'=>10]); ?>
<?php echo $form->textFieldRow($model,'secondary_contact_no',['class'=>'form-control','maxlength'=>10]); ?>

<?php echo $form->textAreaRow($model,'address',['class'=>'form-control']); ?>
<?php echo $form->textFieldRow($model,'tax_no',['class'=>'form-control']); ?>
<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions(),['class'=>'form-control']); ?>

<?php


if ($model->country_id == null) {
	$country = Country::findOne( [
			'title' => 'India' 
	] );
	if ($country) {
		$model->country_id = $country->id;
	}
}
?>
<?php echo $form->dropDownListRow($model, 'country_id', Gx::listData(Country::findAll(['status'=>Country::STATUS_ACTIVE])),['class'=>'form-control','empty'=>'Select Country']); ?>
<?php echo $form->dropDownListRow($model, 'state_id', Gx::listData(State::findAll(['status'=>State::STATUS_ACTIVE])),['class'=>'form-control','empty'=>'Select State']); ?>



<?php echo $form->dropDownListRow($model, 'city_id', Gx::listData(City::findAll(['status'=>City::STATUS_ACTIVE])),['class'=>'form-control','empty'=>'Select City']); ?>



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
<script>

     $( document ).ready(function() {
    	 var country = $('#Organization_country_id').val();
    		checkStates(country);
    		
    	});
$('#Organization_country_id').change(function(){
	var country = $('#Organization_country_id').val();
	checkStates(country);
});

function checkStates(country){
	 jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('country/getStates') ?>',
	       'data': {'country': country},
	       'success': function (data) {
	    	   $("#Organization_state_id").empty();
	    	   $("#Organization_city_id").empty();
	          $('#Organization_state_id').html(data);
	          var state = $('#Organization_state_id').val();
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

$('#Organization_state_id').change(function(){
	var state = $('#Organization_state_id').val();
	checkCities(state);
});

function checkCities(state){
	 jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('country/getCities') ?>',
	       'data': {'state': state},
	       'success': function (data) {
	    	   $("#Organization_city_id").empty();
	          $('#Organization_city_id').html(data);
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