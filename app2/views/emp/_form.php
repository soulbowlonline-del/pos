<?php
/**
 * Ported from protected/views/emp/_form.php.
 */

use Yii;
use app\components\Gx;
use app\components\Ui;
use app\models\City;
use app\models\Country;
use app\models\Designation;
use app\models\Outlet;
use app\models\State;
use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<script src="<?php  echo '/themes/bar'; ?>/js/jquery-ui.js"></script>
<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">

				<div class="box-header">
					<h3 class="box-title">Create Emp</h3>
				</div>


				<div class="box-body">
					<div class="row">
						<div class="col-md-12">


<?php $form = ActiveForm::begin([
	'id' => 'emp-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php //echo $form->errorSummary($model); ?>
<?php if(Yii::$app->user->hasFlash('error')){ ?>

<div class="alert alert-danger"><?php echo Yii::$app->user->getFlash('error'); ?>
</div>
<?php } ?>
<div class="col-md-6">
<h2>Basic Info</h2>
<?php echo $form->textFieldRow($model,'code',['class'=>'form-control']); ?>


<?php echo $form->textFieldRow($model,'name',['class'=>'form-control','maxlength'=>255]); ?>


<?php echo $form->textFieldRow($model,'email',['class'=>'form-control','maxlength'=>255]); ?>

<?php echo $form->textFieldRow($model,'username',['class'=>'form-control','maxlength'=>255]); ?>
<?php echo $form->passwordFieldRow($model,'password',['class'=>'form-control','maxlength'=>255]); ?>
<?php echo $form->textFieldRow($model,'contact_no',['class'=>'form-control','maxlength'=>10]); ?>


<?php echo $form->dropDownListRow($model, 'gender_id',$model->getGenderOptions(),['class'=>'form-control']); ?>

<?php echo $form->checkBoxListRow($model, 'role_id',$model->getRoleOptions()); ?>

<?php echo $form->datepickerRow($model, 'date_of_birth',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
							'options'=>['format'=>'yyyy-mm-dd']])
; ?>



<h2>Employee Info</h2>
<?php echo $form->dropDownListRow($model, 'outlet_id', Gx::listData(Outlet::findAll(['status'=>Outlet::STATUS_ACTIVE])),['class'=>'form-control']); ?>
<?php echo $form->checkBoxListRow($model, 'shift_id', $model->getShiftOptions()); ?>
<?php echo $form->datepickerRow($model, 'date_of_joining',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
							'options'=>['format'=>'yyyy-mm-dd']])
; ?>




</div>
<div class="col-md-6">
<h2>Permanent Address</h2>
<?php  echo $form->textAreaRow($model,'permanent_address',  ['class'=>'form-control', 'rows'=>5]);; ?>
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


<h2>Temporary Address</h2>


<?php echo $form->textAreaRow($model,'temp_address',  ['class'=>'form-control', 'rows'=>5]);; ?>
<?php


if ($model->temp_country_id == null) {
	$country = Country::findOne( [
			'title' => 'India' 
	] );
	if ($country) {
		$model->temp_country_id = $country->id;
	}
}
?>
<?php echo $form->dropDownListRow($model, 'temp_country_id', Gx::listData(Country::findAll(['status'=>Country::STATUS_ACTIVE])),['class'=>'form-control','empty'=>'Select Country']); ?>
<?php echo $form->dropDownListRow($model, 'temp_state_id', Gx::listData(State::findAll(['status'=>State::STATUS_ACTIVE])),['class'=>'form-control','empty'=>'Select State']); ?>



<?php echo $form->dropDownListRow($model, 'temp_city_id', Gx::listData(City::findAll(['status'=>City::STATUS_ACTIVE])),['class'=>'form-control','empty'=>'Select City']); ?>




<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions(),['class'=>'form-control']); ?>


<?php echo $form->dropDownListRow($model, 'designation_id', Gx::listData(Designation::findAll(['status'=>Designation::STATUS_ACTIVE])),['class'=>'form-control']); ?>





</div>




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

<script>

     $( document ).ready(function() {
    	 var country = $('#Emp_country_id').val();
    		checkStates(country);
    		
    		 var temp_country = $('#Emp_temp_country_id').val();
     		checktempStates(temp_country);
     	
    		
    	});
$('#Emp_country_id').change(function(){
	var country = $('#Emp_country_id').val();
	checkStates(country);
});
$('#Emp_temp_country_id').change(function(){
	var country = $('#Emp_temp_country_id').val();
	checktempStates(country);
});
function checktempStates(country){
	 jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('country/getStates') ?>',
	       'data': {'country': country},
	       'success': function (data) {
	    	   $("#Emp_temp_state_id").empty();
	    	   $("#Emp_temp_city_id").empty();
	          $('#Emp_temp_state_id').html(data);
	      	var temp_state = $('#Emp_temp_state_id').val();
     		checktempCities(temp_state);
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
function checkStates(country){
	 jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('country/getStates') ?>',
	       'data': {'country': country},
	       'success': function (data) {
	    	   $("#Emp_state_id").empty();
	    	   $("#Emp_city_id").empty();
	          $('#Emp_state_id').html(data);
	          var state = $('#Emp_state_id').val();
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
$('#Emp_state_id').change(function(){
	var state = $('#Emp_state_id').val();
	checkCities(state);
});
$('#Emp_temp_state_id').change(function(){
	var state = $('#Emp_temp_state_id').val();
	checktempCities(state);
});
function checktempCities(state){
	 jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('country/getCities') ?>',
	       'data': {'state': state},
	       'success': function (data) {
	    	   $("#Emp_temp_city_id").empty();
	          $('#Emp_temp_city_id').html(data);
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
function checkCities(state){
	 jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('country/getCities') ?>',
	       'data': {'state': state},
	       'success': function (data) {
	    	   $("#Emp_city_id").empty();
	          $('#Emp_city_id').html(data);
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
$('#Emp_date_of_birth').datepicker({
    autoclose: true
    
});
$('#Emp_date_of_joining').datepicker({
    autoclose: true
});

     </script>