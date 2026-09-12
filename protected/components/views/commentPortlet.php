
<?php foreach($this->getRecentComments() as $data){?>
<div class="comment">
	<div class="avatar">
		<small> <b><?php echo GxHtml::encode(GxHtml::valueEx($data->createUser)); ?>
		</b> <?php echo ' on date' ?>: <?php echo GxHtml::encode($data->create_time); ?>
			<?php if ( $data->create_user_id == Yii::app()->user->id) echo CHtml::link('Updated', $data->getUrl('update'), array('class'=>'btn btn-mini btn-info pull-right','icon'=>'align-left'));?>
			<?php echo CHtml::link('Delete','#',
					array('class'=>'btn btn-mini btn-warning pull-right',
		 'submit' => $data->getUrl('delete'),
					'confirm'=>'Are you sure you want to delete this item?')
		 );?>
		</small>
	</div>
	<div class="well content">
		<?php
		$this->beginWidget('CMarkdown', array('purifyOutput'=>true));
		echo $data->purifyHtml($data->comment);
		$this->endWidget();
		?>
	</div>
</div>

<?php } ?>
<?php if(Yii::app()->user->hasFlash('commentSubmitted')): ?>
<div class="alert in alert-block fade alert-success">
	<?php echo Yii::app()->user->getFlash('commentSubmitted'); ?>
</div>
<?php endif; ?>

<div class="form">

	<?php 	
	$comment =  new Comment();
	$form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
		'id' => 'comment-form',
	//	'type'=>'horizontal',
	//	'enableAjaxValidation' => true,
		//'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	<?php echo $form->errorSummary($comment); ?>

	<?php $code = 1 ;

	if ($code == 1) echo $form->html5EditorRow($comment,'comment', array('class'=>'span4', 'rows'=>5, 'height'=>'100', 'width'=>'100%', 'options'=>array('color'=>true)));

	else if ($code == 2) echo $form->redactorRow($comment,'comment', array('class'=>'span4', 'rows'=>5));

	else if ($code == 3) echo $form->ckEditorRow($comment,'comment', array('options'=>array('fullpage'=>'js:true', 'width'=>'640', 'resize_maxWidth'=>'640','resize_minWidth'=>'320')));

	else echo $form->textAreaRow($comment,'comment',  array('class'=>'span4', 'rows'=>5));
	?>
	<br />
	<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'success',
			//'size' => 'large',
			'label'=>'Add Comment',
		)); ?>


	<?php $this->endWidget(); ?>

</div>
<!-- form -->
