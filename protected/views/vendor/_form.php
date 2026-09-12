<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">

				<div class="box-header">
					<h3 class="box-title">Create Vendor</h3>
				</div>


				<div class="box-body">
					<div class="row">
						<div class="col-md-12">

<?php if(Yii::app()->user->hasFlash('success') ){ ?>

<div class="alert alert-success"><?php echo Yii::app()->user->getFlash('success'); ?>
</div>
<?php }else{ ?>
<?php if($flash == true){?>
<div class="alert alert-success">Vendor info is saved sucessfully</div>
<?php }?>
<?php }?>

<?php

$form = $this->beginWidget ( 'bootstrap.widgets.TbActiveForm', array (
		'id' => 'vendor-form',
		'type' => 'horizontal',
		'enableAjaxValidation' => true,
		'htmlOptions' => array (
				'enctype' => 'multipart/form-data' 
		) 
) );
?>
	<p class="help-block">
								Fields with <span class="required">*</span> are required.
							</p>

	<?php echo $form->errorSummary($model); ?>

<div class="col-md-6">
								<h2>Basic Info</h2>
<?php echo $form->textFieldRow($model,'name',array('class'=>'form-control','maxlength'=>255)); ?>
<?php echo $form->textFieldRow($model,'tax_no',array('class'=>'form-control','maxlength'=>255)); ?>

<div class="form-group">
									<label for="inputEmail3" class="control-label col-md-3"> Outlet
									</label>
									<div class="col-md-9">
<?php echo CHtml::activeListBox($model, 'outlet_id', Item::getAllOutlets(), array('class'=>'chosen', 'multiple'=>true, 'data-placeholder'=>'Select Outlet'))?>
</div>
								</div>
<?php echo $form->checkBoxRow($model, 'is_local_vendor'); ?>
<?php echo $form->checkBoxRow($model, 'is_cash'); ?>
<h2>Contact Info</h2>

<?php echo $form->textFieldRow($usermodel,'email',array('class'=>'form-control','maxlength'=>255)); ?>
<?php echo $form->textFieldRow($model,'contact_email',array('class'=>'form-control','maxlength'=>255)); ?>

<?php echo $form->textFieldRow($usermodel,'username',array('class'=>'form-control','maxlength'=>255)); ?>
<?php echo $form->textFieldRow($usermodel,'password',array('class'=>'form-control','maxlength'=>255)); ?>
<?php echo $form->textFieldRow($model,'contact_no',array('class'=>'form-control')); ?>

<?php echo $form->textFieldRow($model,'contact_person',array('class'=>'form-control','maxlength'=>255)); ?>
<?php echo $form->textFieldRow($model,'person_designation',array('class'=>'form-control','maxlength'=>255)); ?>

<?php echo $form->textFieldRow($model,'secondary_contact_no',array('class'=>'form-control')); ?>
<?php echo $form->textFieldRow($model,'whatsapp_no',array('class'=>'form-control', 'placeholder' => 'Enter WhatsApp No (without country code)')); ?>
<h2>Account Info</h2>

<?php echo $form->textFieldRow($model,'opening_balance',array('class'=>'form-control')); ?>


</div>

							<div class="col-md-6">
<?php echo $form->textFieldRow($model,'payment_days',array('class'=>'form-control','maxlength'=>255)); ?>
<?php echo $form->checkBoxRow($model, 'is_advance_payment'); ?>
<h2>Account Details</h2>

<?php echo $form->textFieldRow($model,'bank_name',array('class'=>'form-control')); ?>

<?php echo $form->textFieldRow($model,'acc_no',array('class'=>'form-control')); ?>
<?php echo $form->textFieldRow($model,'ifsc',array('class'=>'form-control')); ?>
<h2>Business Address</h2>




<?php echo $form->textAreaRow($model,'primary_address',array('class'=>'form-control')); ?>

<?php echo $form->textAreaRow($model,'secondary_address',array('class'=>'form-control')); ?>
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



<?php echo $form->dropDownListRow($model, 'parent_id', GxHtml::listDataEx(Vendor::model()->findAllByAttributes(array('status'=>Vendor::STATUS_ACTIVE))),array('class'=>'form-control','empty'=>'Parent Vendor')); ?>


<?php

echo $form->dropDownListRow ( $model, 'status', $model->getStatusOptions (), array (
		'class' => 'form-control' 
) );
?>



<?php echo $form->textAreaRow($model,'description',array('class'=>'form-control')); ?>

</div>
<?php

Yii::import ( 'application.extensions.widgets.yii-chosen.EChosenWidget' );

?>
 <?php
	
$this->widget ( 'EChosenWidget', array (
			// the select selector
			'selector' => '.chosen' 
	)
	// Chosen options
	 );
	?>
	<div class="clearfix"></div>
	<div class="form-actions">
		<?php
		
$this->widget ( 'bootstrap.widgets.TbButton', array (
				'buttonType' => 'submit',
				'type' => 'primary',
				'label' => 'Save' 
		) );
		?>
	</div>

<?php $this->endWidget(); ?>


</div>
					</div>
				</div>


			</div>
		</div>
	</div>
</section>

<script>

     $( document ).ready(function() {
    	 var country = $('#Vendor_country_id').val();
    		checkStates(country);
    	/* 	 */
    	});
$('#Vendor_country_id').change(function(){
	var country = $('#Vendor_country_id').val();
	checkStates(country);
});

function checkStates(country){
	 jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo CController::createUrl('country/getStates') ?>',
	       'data': {'country': country},
	       'success': function (data) {
	    	   $("#Vendor_state_id").empty();
	    	   $("#Vendor_city_id").empty();
	          $('#Vendor_state_id').html(data);
	          var state = $('#Vendor_state_id').val();
	  	 		console.log(state);
	  	 		checkCities(state);
	  	 		<?php if($model->state_id != ''){?>
	  	 		var state = "<?php echo $model->state_id ;?>";
	  	 	 $('#Vendor_state_id').val(state);
	  	 	
    		checkCities(state);
	  	 		<?php }?>
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
$('#Vendor_state_id').change(function(){
	var state = $('#Vendor_state_id').val();
	checkCities(state);
});

function checkCities(state){
	 jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo CController::createUrl('country/getCities') ?>',
	       'data': {'state': state},
	       'success': function (data) {
	    	   $("#Vendor_city_id").empty();
	          $('#Vendor_city_id').html(data);
	          <?php if($model->city_id != ''){?>
	  	 		var city = "<?php echo $model->city_id ;?>";
	  	 		console.log('city'+city);
	  	 	 $('#Vendor_city_id').val(city);
	  	 		<?php }?>
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