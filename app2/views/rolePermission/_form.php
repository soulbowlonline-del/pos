<?php
/**
 * Ported from protected/views/rolePermission/_form.php.
 */

use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<!--  form code start here -->
<div class="box-body">


<?php $form = ActiveForm::begin([
	'id' => 'role-permission-form',
	'type'=>'vertical',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

<div class="form-group">
<?php echo $form->dropDownListRow($model, 'role_id', $model->getRoleOptions(),['class'=>'form-control']); ?>
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
			$model->getStatusOptions(),['class'=>'form-control']); ?>
</div>






	<div class="box-footer">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Save',
		]); ?>
	</div>

<?php ActiveForm::end(); ?>

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