<!--  form code start here -->
<div class="box-body">


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'role-permission-form',
	'type'=>'vertical',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

<div class="form-group">
<?php echo $form->dropDownListRow($model, 'role_id', $model->getRoleOptions(),array('class'=>'form-control')); ?>
</div>
<div class="form-group">
<?php echo $form->label($model, 'permission_id');?>

<div class="col-md-12 select-all"><label class="checkbox-inline" ><input type="checkbox" class="checkBox" id="globalCheckbox">Select All</label></div>
<div id="permissiondisplay">

</div>
<?php echo $form->error($model, 'permission_id');?>
<div class="clearfix"></div>
</div>

<div class="form-group">
<?php echo $form->dropDownListRow($model, 'status',
			$model->getStatusOptions(),array('class'=>'form-control')); ?>
</div>






	<div class="box-footer">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Save',
		)); ?>
	</div>

<?php $this->endWidget(); ?>

</div>
<!-- form code ends here -->

<script>

$(document).ready(function () {
	checkPermissions();
    $('#RolePermission_role_id').change(function () {  
    	checkPermissions();
    });
   

 });
$('#globalCheckbox').click(function(){
    if($(this).prop("checked")) {
        $(".checkBox").prop("checked", true);
    } else {
        $(".checkBox").prop("checked", false);
    }                
});
function checkPermissions()
{
    var role_id = $('#RolePermission_role_id').val();
    jQuery.ajax({
       'type': 'POST',
       'url': '<?php echo CController::createUrl('rolePermission/ajaxUpdate') ?>',
       'data': {'role_id': role_id},
       'success': function (data) {
    	   $("#globalCheckbox").prop("checked", false);
           $('#permissiondisplay').html('');
           $('#permissiondisplay').html(data);
         
       },
       'cache': false
    }
    );	
}


</script>