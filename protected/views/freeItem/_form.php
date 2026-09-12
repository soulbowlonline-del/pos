<!--  form code start here -->
<!--  form code start here -->
<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">

				<div class="box-header">
					<h3 class="box-title">Create Free Item</h3>
				</div>


				<div class="box-body">
					<div class="row">
						<div class="col-md-12">


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'free-item-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php //echo $form->errorSummary($model); ?>

<div class ="row">

<div class="col-md-6">
	<div class="form-group">
<label for="inputEmail3" class="control-label col-md-3">
Title
<span class="required">*</span>
</label>
<div class="col-md-9">
<input type="hidden" name="FreeItem[title]" id="free_item_title">

   <?php 
   
   $this->widget('ext.typeahead.TbTypeAhead',array(
              'model' => $model,
              'attribute' => 'name',
              'enableHogan' => true,
              
              'options' => array(
                           array(
                                         'limit' => 10,
                                         'name' => 'name',
                                         'valueKey' => 'bar_code',
                                         'remote' => array(
                                                       'url' => Yii::app()->createUrl('/freeItem/itemList') . '?term=%QUERY',
                                         ),
                           		'template' => '<p>{{bar_code}}<strong> [ {{name}} ] </strong></p>',
                                  //     'template' => '<p>{{name}}<strong> [ {{username}} ] </strong> - {{user_id}}</p>',
                                         'engine' => new CJavaScriptExpression('Hogan'),
                           )
              ),
              
               'events' => array(
                           'selected' => new CJavascriptExpression("function(obj, datum, name) {
                    var    catid = datum.category_id;
                           		    var    item_detail_id = datum.item_detail_id;
                           		  var    compid = datum.company_id;
                           		   $('#free_item_title').val(name);
                           $('#FreeItem_item_category_id').val(catid);
                           		 $('#FreeItem_item_company_id').val(compid);
                           			 $('#FreeItem_item_detail_id').val(item_detail_id);
                           		
          
         }")
              ), 
   ));
   
   
   ?>
   <?php echo $form->error($model,'title');?></div>
</div>
<?php //echo $form->textFieldRow($model,'title',array('class'=>'form-control','maxlength'=>255)); ?>


<?php echo $form->dropDownListRow($model, 'item_detail_id',$model->getItemOptions(),array('class'=>'form-control')); ?>


<?php echo $form->dropDownListRow($model, 'item_category_id', GxHtml::listDataEx(ItemCategory::model()->findAllByAttributes(array('status'=>ItemCategory::STATUS_ACTIVE))),array('class'=>'form-control')); ?>


</div>
<div class="col-md-6">
<?php echo $form->dropDownListRow($model, 'item_company_id', GxHtml::listDataEx(ItemCompany::model()->findAllByAttributes(array('status'=>ItemCompany::STATUS_ACTIVE))),array('class'=>'form-control')); ?>

<?php echo $form->textFieldRow($model,'qty',array('class'=>'form-control')); ?>


<?php echo $form->textFieldRow($model,'stock_qty',array('class'=>'form-control')); ?>
<?php  $user = Yii::app()->user->model;
            $role = UserRole::model()->findByAttributes(array('title'=>'Admin'));
            if($user->role_id == $role->id ){?>
            <?php echo $form->dropDownListRow($model, 'status',$model->getStatusOptions(),array('class'=>'form-control')); ?>
            <?php }?>
</div>




	<div class="form-actions">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Save',
		)); ?>
	</div>
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