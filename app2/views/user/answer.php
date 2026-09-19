<?php
/**
 * Ported from protected/views/user/answer.php.
 */

use app\components\Ui;
use app\widgets\ActiveForm;
use app\widgets\Button;
use yii\helpers\Html;
?>
<!--  form code start here -->
<section class="content">
<div class="page-header">
<h1><?php //echo 'Create' . ' ' . Html::encode($model->label()); ?></h1>
</div>
<div class="form well">

<?php $form = ActiveForm::begin([
		'id' => 'user-question-form',
		'type' => 'horizontal',
		'enableClientValidation'=>true,
		'clientOptions'=>[
				'validateOnSubmit'=>true
		],
		//'enableAjaxValidation' => true,
		'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
<?php if(Yii::$app->user->hasFlash('success')){ ?>

<div class="alert alert-success"><?php echo Yii::$app->user->getFlash('success'); ?>
</div>
<?php } ?>
<?php if(Yii::$app->user->hasFlash('error')){ ?>

<div class="alert alert-danger"><?php echo Yii::$app->user->getFlash('error'); ?>
</div>
<?php } ?>
<?php //echo $form->errorSummary($model); ?>

<?php for($i = 1;$i<3;$i++){?>
<div class="form-group">
 <?php 
echo $form->dropDownListRow($model, 'question_id[]',
			$model->getUserQuestionOptions($id),['class' => 'form-control','id'=>'UserQuestion_question_id'.$i]); ?>
 </div>

<div class="form-group">
<?php echo $form->textFieldRow($model,'answer[]', ['class' => 'form-control','id'=>'UserQuestion_answer'.$i,'placeholder'=>'Answer ']); ?>

 </div>
 <?php }?>



<div class="form-group text-center">

<?php echo Button::widget([
				'buttonType'=>'submit',
				'type'=>'primary',
				'label'=>'Save',
				'htmlOptions'=>['class'=>'btn  btn-orange'],
]); ?>
</div>


<?php ActiveForm::end(); ?>
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
	var id = "<?php echo $id;?>";
	if($('#UserQuestion_answer1').val() == ''){
	 jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('user/ajaxuserquestion') ?>/id/'+id,
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
	var id = "<?php echo $id;?>";
	if($('#UserQuestion_answer2').val() == ''){
	 jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('user/ajaxuserquestion') ?>/id/'+id,
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
