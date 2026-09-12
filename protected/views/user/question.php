<!--  form code start here -->
<section class="content">
<div class="page-header">
<h1><?php echo Yii::t('app', 'Create') . ' ' . GxHtml::encode($model->label()); ?></h1>
</div>
<div class="form well">

<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
		'id' => 'user-question-form',
		'type' => 'horizontal',
		'enableClientValidation'=>true,
		'clientOptions'=>array(
				'validateOnSubmit'=>true
		),
		//'enableAjaxValidation' => true,
		'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
<?php if(Yii::app()->user->hasFlash('success')){ ?>

<div class="alert alert-success"><?php echo Yii::app()->user->getFlash('success'); ?>
</div>
<?php } ?>
<?php if(Yii::app()->user->hasFlash('error')){ ?>

<div class="alert alert-danger"><?php echo Yii::app()->user->getFlash('error'); ?>
</div>
<?php } ?>
<?php //echo $form->errorSummary($model); ?>

<?php for($i = 1;$i<3;$i++){?>
<div class="form-group">
 <?php 
echo $form->dropDownListRow($model, 'question_id[]',
			$model->getQuestionOptions(),array('class' => 'form-control','id'=>'UserQuestion_question_id'.$i)); ?>
 </div>

<div class="form-group">
<?php echo $form->textFieldRow($model,'answer[]', array('class' => 'form-control','id'=>'UserQuestion_answer'.$i,'placeholder'=>'Answer ')); ?>

 </div>
 <?php }?>



<div class="form-group text-center">

<?php $this->widget('bootstrap.widgets.TbButton', array(
				'buttonType'=>'submit',
				'type'=>'primary',
				'label'=>'Save',
				'htmlOptions'=>array('class'=>'btn  btn-orange'),
)); ?>
</div>


<?php $this->endWidget(); ?>
<!-- form code ends here -->

</div>
</section>

<script>
$(document).ready(function () {
	checkQuestion();
});
$('#UserQuestion_question_id1').change(function(){
	checkQuestion();
});
function checkQuestion(){
	var question1 = $('#UserQuestion_question_id1').val();
	if($('#UserQuestion_answer1').val() == ''){
	 jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo CController::createUrl('user/ajaxquestion') ?>',
	       'data': {'selected': question1},
	       'success': function (data) {
	    	   $
	           $('#UserQuestion_question_id2').html('');
	           $('#UserQuestion_question_id2').html(data);
	         
	       },
	       'cache': false
	    }
	    );
	}
}

$('#UserQuestion_question_id2').change(function(){
	var question1 = $('#UserQuestion_question_id2').val();
	if($('#UserQuestion_answer2').val() == ''){
	 jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo CController::createUrl('user/ajaxquestion') ?>',
	       'data': {'selected': question1},
	       'success': function (data) {
	    	   $
	           $('#UserQuestion_question_id1').html('');
	           $('#UserQuestion_question_id1').html(data);
	         
	       },
	       'cache': false
	    }
	    );
	}
});
</script>
