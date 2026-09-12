<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">

				<div class="box-header">
					<h3 class="box-title">Customer</h3>
				</div>


				<div class="box-body">
					<div class="row">
						<div class="col-md-12">


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'customer-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php //echo $form->errorSummary($model); ?>
<div class="col-md-6">
<h2>Basic Info</h2>
<?php echo $form->textFieldRow($model,'name',array('class'=>'form-control','maxlength'=>255)); ?>

<?php echo $form->textFieldRow($model,'opening_balance',array('class'=>'form-control')); ?>


<?php echo $form->textFieldRow($model,'credit_limit',array('class'=>'form-control')); ?>


<?php echo $form->textFieldRow($model,'payment_days',array('class'=>'form-control')); ?>
<h2>Contact Info</h2>
<?php echo $form->textFieldRow($model,'email',array('class'=>'form-control','maxlength'=>255)); ?>


<?php echo $form->textFieldRow($model,'fax',array('class'=>'form-control','maxlength'=>255)); ?>
<?php echo $form->textFieldRow($model,'contact_no',array('class'=>'form-control','maxlength'=>10)); ?>
</div>

<div class="col-md-6">

<h2>Address</h2>



<?php echo $form->textAreaRow($model,'address',  array('class'=>'form-control', 'rows'=>5));; ?>
<?php


if ($model->country_id == null) {
	$country = Country::model ()->findByAttributes ( array (
			'title' => 'India' 
	) );
	if ($country) {
		$model->country_id = $country->id;
	}
}
?>
<?php echo $form->dropDownListRow($model, 'country_id', GxHtml::listDataEx(Country::model()->findAllByAttributes(array('status'=>Country::STATUS_ACTIVE))),array('class'=>'form-control','empty'=>'Select Country')); ?>
<?php echo $form->dropDownListRow($model, 'state_id', GxHtml::listDataEx(State::model()->findAllByAttributes(array('status'=>State::STATUS_ACTIVE))),array('class'=>'form-control','empty'=>'Select State')); ?>



<?php echo $form->dropDownListRow($model, 'city_id', GxHtml::listDataEx(City::model()->findAllByAttributes(array('status'=>City::STATUS_ACTIVE))),array('class'=>'form-control','empty'=>'Select City')); ?>




<?php echo $form->textFieldRow($model,'zip_code',array('class'=>'form-control')); ?>



<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions(),array('class'=>'form-control')); ?>
			</div>
<div class="form-actions">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Save',
		)); ?>
	</div>




	

<?php $this->endWidget(); ?>

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
	       'url': '<?php echo CController::createUrl('country/getStates') ?>',
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
	       'url': '<?php echo CController::createUrl('country/getCities') ?>',
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