<div class="breadcrum_container row">
	<div class="container">
		<h1 class="pull-left">User Profile</h1>
	</div>
</div>

<div class="clearfix mar_top2"></div>


<div class="container">

	<div class="">

		<div class="heading">
			<h1>User Details</h1>
			<?php   $this->widget('bootstrap.widgets.TbButtonGroup', array(
					'buttons'=>$this->actions,
					'type'=>'success',
					'htmlOptions'=>array('class'=> 'pull-right'),
			));

			?>
			<span></span>
		</div>

	</div>

	<div class="clearfix mar_top5"></div>

	<div class="row">

		<div class="col-md-5">
			<!--  form code start here -->


			<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
					'id' => 'update-form',
					'type'=>'horizontal',
					'action'=> Yii::app()->createUrl('api/user/updateDriver',array('id'=>Yii::app()->user->id)),
					'enableAjaxValidation' => true,
					'htmlOptions'=>array('enctype'=>'multipart/form-data'),
			));
			?>

			<?php echo $form->errorSummary($model); ?>

			
			<!--  Driver table -->
			<?php //$driver_model = new Driver();?>
			<div class="form-group">
			
				<div class="col-sm-12">

					<?php echo $form->textFieldRow($driver_model,'car_model',array('class'=>'col-md-12','maxlength'=>256)); ?>

				</div>
			</div>
			

			<!--  User table -->
			<?php //echo $form->dropDownList($model, 'passenger_id', GxHtml::listDataEx(User::model()->findAllAttributes(null, true))); ?>
			<div class="form-group">

				<div class="col-sm-12">

					<?php //echo $form->textFieldRow($model,'full_name',array('class'=>'col-md-12','maxlength'=>256)); ?>

				</div>
			</div>


			<?php //echo $form->textFieldRow($model,'from_latitude',array('class'=>'col-md-12','maxlength'=>32)); ?>

			<?php //echo $form->textFieldRow($model,'from_longitude',array('class'=>'col-md-12','maxlength'=>32)); ?>


			<div class="form-group">

				<div class="col-sm-12">
					<?php echo $form->textFieldRow($model,'email',array('class'=>'col-md-12','maxlength'=>128)); ?>

				</div>
			</div>
			<?php //echo $form->textFieldRow($model,'to_latitude',array('class'=>'col-md-12','maxlength'=>32)); ?>

			<?php //echo $form->textFieldRow($model,'to_longitude',array('class'=>'col-md-12','maxlength'=>32)); ?>
			<div class="form-group">

				<div class="col-sm-12">

					<?php echo $form->datepickerRow($model, 'date_of_birth',
							array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>','options'=>array(
		      'format'=>'yyyy-mm-dd',
              'endDate' => '-10y',
			 'startDate' =>'-55y'

	)))
; ?>
				</div>
			</div>
			<div class="form-group">

				<div class="col-sm-12">

					<?php echo $form->datepickerRow($model, 'anniversary',
							array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>','options'=>array(
		      'format'=>'yyyy-mm-dd',
            'endDate' => '-10y',
			 'startDate' =>'-55y'

	)))
; ?>
				</div>
			</div>
			<div class="form-group">

				<div class="col-sm-12">
					<?php echo $form->passwordField($model,'password',array('class'=>'col-md-12')); ?>
				</div>
			</div>

			<div class="form-group">

				<div class="col-sm-12">
					<?php echo $form->textFieldRow($model,'contact_no',array('class'=>'col-md-12')); ?>
				</div>
			</div>

			<div class="form-group">

				<div class="col-sm-12">
					<?php echo $form->textFieldRow($model,'address',array('class'=>'col-md-12'));  ?>
				</div>
			</div>


			<div class="form-group">

				<div class="col-sm-12">
					<?php  echo $form->textFieldRow($model,'postal_code',array('class'=>'col-md-12'));  ?>
				</div>
			</div>
			<div class="form-group">

				<div class="col-sm-12">
					<?php echo $form->textFieldRow($model,'lang',array('class'=>'col-md-12'));  ?>

				</div>
			</div>


		</div>
		<!-- ----- -->

		<div class="col-md-5">


			<div class="form-group">

				<div class="col-sm-12">
					<?php echo  '';$code = $this->richTextEditor() ;

					if ($code == 1) echo $form->html5EditorRow($model,'about_me', array('class'=>'span4', 'rows'=>5, 'height'=>'200', 'options'=>array('color'=>true)));

					else if ($code == 2) echo $form->redactorRow($model,'about_me', array('class'=>'span4', 'rows'=>5));

					else if ($code == 3) echo $form->ckEditorRow($model,'about_me', array('options'=>array('fullpage'=>'js:true', 'width'=>'640', 'resize_maxWidth'=>'640','resize_minWidth'=>'320')));

					else echo $form->textAreaRow($model,'about_me',  array('class'=>'span4', 'rows'=>5));; ?>

				</div>
			</div>

			<div class="form-group">

				<div class="col-sm-12">
					<?php echo $form->fileFieldRow($model, 'image_file');  ?>

				</div>
			</div>
			<?php //echo $form->textFieldRow($model,'cancel_reason_id',array('class'=>'span5')); ?>


			<?php //echo $form->textFieldRow($model,'amountpaid',array('class'=>'span5')); ?>


			<?php //echo $form->textFieldRow($model,'comment',array('class'=>'col-md-12','maxlength'=>512)); ?>


			<?php //echo $form->textFieldRow($model,'paidamount_to_dispatcher',array('class'=>'span5')); ?>


			<?php //echo $form->textFieldRow($model,'payment_info',array('class'=>'span5','maxlength'=>256)); ?>


			<?php //echo $form->checkBoxRow($model, 'email_sent'); ?>


			<div class="clearfix mar_top2"></div>


			<div class="form-group">
				<div class=" col-sm-12">
					<?php $this->widget('bootstrap.widgets.TbButton', array(
							'buttonType'=>'submit',
							'type'=>'primary',
							'label'=>'Update',
		)); ?>
				</div>
			</div>
			<?php $this->endWidget(); ?>

			<!-- form code ends here -->

		</div>

		<div class="clearfix mar_top3"></div>

	</div>
	<!----  row  ------>


</div>
<!----  container  ------>


<?php 


/*  echo $form->datepickerRow($model, 'date_of_birth',
 array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>','options'=>array(
		      'format'=>'yyyy-mm-dd',
                'endDate' => '-10 y',
				 'startDate' =>'-55y'

	)))
; */ ?>

<?php /* echo $form->datepickerRow($model, 'anniversary',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>'))
;  */?>


<!-- form code ends here -->