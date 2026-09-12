<div class="wide form">

<?php 	$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
	'action' => Yii::app()->createUrl($this->route),
	'method' => 'get',
	'id' => 'shift-form',
	'type'=>'horizontal',		
)); ; 
?>

	<div class="row">
		<?php echo $form->label($model, 'id'); ?>
		<?php echo $form->textField($model, 'id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'title'); ?>
		<?php echo $form->textField($model, 'title', array('maxlength' => 255)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'description'); ?>
		<?php $this->richTextEditor($model,'description'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'start_time'); ?>
		<?php $form->widget('ext.widgets.clockpick.EClockpick', array(
			'model' => $model,
			'attribute' => 'start_time',
			'value' => $model->start_time,
			'options'          =>array(
			'starthour'    => 8,
			'endhour'      => 20,
			'showminutes'  => TRUE,
			'minutedivisions'   => 12,
			'military'      => TRUE,
			),
			'htmlOptions'      => array('size'=>5,'maxlength'=>5)
			));
; ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'end_time'); ?>
		<?php $form->widget('ext.widgets.clockpick.EClockpick', array(
			'model' => $model,
			'attribute' => 'end_time',
			'value' => $model->end_time,
			'options'          =>array(
			'starthour'    => 8,
			'endhour'      => 20,
			'showminutes'  => TRUE,
			'minutedivisions'   => 12,
			'military'      => TRUE,
			),
			'htmlOptions'      => array('size'=>5,'maxlength'=>5)
			));
; ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'status'); ?>
		<?php 
			$this->widget('ext.widgets.CJuiRadioButtonList', array(
			'model'=>$model,
			'attribute'=>'status',
			'data'=>$model->getStatusOptions(),
			)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'type_id'); ?>
		<?php 
			$this->widget('ext.widgets.CJuiRadioButtonList', array(
			'model'=>$model,
			'attribute'=>'type_id',
			'data'=>$model->getTypeOptions(),
			)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'create_time'); ?>
		<?php $form->widget('zii.widgets.jui.CJuiDatePicker', array(
			'model' => $model,
			'attribute' => 'create_time',
			'value' => $model->create_time,
			'options' => array(
			'showButtonPanel' => true,
			'changeYear' => true,
			'dateFormat' => 'yy-mm-dd',
			),
			));
; ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'create_user_id'); ?>
		<?php echo $form->dropDownList($model, 'create_user_id', GxHtml::listDataEx(User::model()->findAllAttributes(null, true)), array('prompt' => Yii::t('app', 'All'))); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model, 'updated_by'); ?>
		<?php echo $form->dropDownList($model, 'updated_by', GxHtml::listDataEx(User::model()->findAllAttributes(null, true)), array('prompt' => Yii::t('app', 'All'))); ?>
	</div>


	<div class="form-actions">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		)); ?>
	</div>
<?php $this->endWidget(); ?>

</div><!-- search-form -->
