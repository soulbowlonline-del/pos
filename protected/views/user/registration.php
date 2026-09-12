   
   
        	<?php $form = $this->beginWidget('GxActiveForm', array(
					'id' => 'user-form1',
				//	'enableAjaxValidation' => true,
					//'action'=> Yii::app()->createUrl( 'api/user/signup'),
					'htmlOptions'=>array('enctype'=>'multipart/form-data',
					'class'=>'form-horizontal',							
),
			));
			?>
			
			
  <div class="form-group">
    
    <div class="col-sm-12">
    <!--   <input type="email" class="form-control" id="inputEmail3" placeholder="Email"> -->
      
			<?php //echo $form->labelEx($model,'full_name',array('class'=>'label_input')); ?>
			<?php echo $form->textField($model, 'full_name', array('class' =>'form-control','placeholder'=>'Full Name')); ?>
			<?php echo $form->error($model,'full_name'); ?>
    </div>
  </div>
  <div class="form-group">
  
    <div class="col-sm-12">
  <!--     <input type="password" class="form-control" id="inputPassword3" placeholder="Password"> -->
      	<?php //echo $form->labelEx($model,'email',array('class'=>'label_input')); ?>
			<?php echo $form->textField($model, 'email', array('class' => 'form-control','placeholder'=>'Email')); ?>
			<?php echo $form->error($model,'email'); ?>
    </div>
  </div>
  
  
    <div class="form-group">
  
    <div class="col-sm-12">
 <!--      <input type="password" class="form-control" id="inputPassword3" placeholder="Password"> -->
   	<?php 
		
		//echo $form->labelEx($model,'password',array('class' => 'label_input')); ?>
			<?php echo $form->textField($model,'contact_no', array('class' => 'form-control','placeholder'=>'Contact Number')); ?>
			<?php echo $form->error($model,'contact_no'); ?>
    </div>
  </div>
 
  
  <div class="form-group">
    <div class=" col-sm-12">
   <!--    <button type="submit" class="btn  col-sm-12 btn-orange">Sign in</button> -->
      <?php
			echo GxHtml::submitButton(Yii::t('app', 'Sign Up'),array('class'=>'btn  col-sm-12 btn-orange'));

			?>
    </div>
  </div>
  	
<?php $this->endWidget(); ?>
<!-- form -->





<script type="text/javascript">
$("#user-form1").submit(function(event) {
	 event.preventDefault();
	    var values = $(this).serialize();      
	      $.ajax({
		        url: "<?php echo Yii::app()->createUrl("api/user/signup")?>",
		        type: "POST",
		        data: values,
		        success: function(response){
			         if(response.status=='Successfully Registered')
			        {
			       // alert('Successfully Registered');
			       $("#reg_response").html(response);
			        location.reload();
			        
			        }
			        else{
			        	 alert(response.error);
			        }
						          			          	
		        },
		        error:function(){
		            		        }
		    });
    }


);
   </script>

