<?php

$this->breadcrumbs = array(
	$model->label(2) => array('index'),
	Yii::t('app', 'Manage'),
);


Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$.fn.yiiGridView.update('mrs-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content-header">
  <h1> <?php echo Yii::t('app', 'Manage') ;?> <?php echo GxHtml::encode($model->label(2))?> </h1>
  
</section>
<section class="content">

  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
        <div class="box-header"><h3 class="box-title">Mrs</h3></div>
        <div class="box-body">
          <div class="row">
          <!--  form code start here -->

            <div class="col-md-12">
<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'item-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
<?php echo $form->dropdownListRow($model, 'vendor_id',$model->getMrsVendorOptions(),array('class'=>'form-control')); ?>
<?php $this->endWidget(); ?>
<div class="table-responsive">

<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'mrs-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => array(
			array(
					'class'           => 'CCheckBoxColumn',
					'selectableRows'  => 100,
					'value'           => '$data["id"]',
					'checkBoxHtmlOptions' => array("name" =>"idList[]"),
						
			),
		'id',
			array(
			'name'=>'outlet_id',
			'value'=>'GxHtml::valueEx($data->outlet)',
			'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
			array(
					'name'=>'vendor_id',
					'value'=>'GxHtml::valueEx($data->vendor)',
					'filter'=>GxHtml::listDataEx(Vendor::model()->findAllAttributes(null, true)),
			),
		/* array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>Mrs::getStatusOptions(),
				), */
		/*
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>Mrs::getTypeOptions(),
				),
		'remarks:html',
		'update_time',
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'outlet_id',
			'value'=>'GxHtml::valueEx($data->outlet)',
			'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'organization_id',
			'value'=>'GxHtml::valueEx($data->organization)',
			'filter'=>GxHtml::listDataEx(Organization::model()->findAllAttributes(null, true)),
			),
		*/
			array(
			
					'header'=>'<a>Actions</a>',
					'class'=>'FaButtonColumn',
					'template' => '{view}', //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
							'view'=>array(
										
									'url' =>'Yii::app()->controller->createUrl("mrs/view", array("id" => $data->id))',
									'label'=>'View',
									'options'=>array('class'=>'view'),
										
							),
							
								
					)
			),
	),
)); ?>
</div>
<input type="button" value="Merge Mrs" onclick="act();" />
<br>
  </div>
            </div>
          </div>
    
        </div>
      </div>
    </div>

</section>
<script>
function act()
{
	
	var idList = [];
	var vendor_id = $('#Mrs_vendor_id').val();
	
	$('input[type=checkbox]:checked').each(function() {
		idList.push(this.value); 
		
		
	});
	console.log(idList);
	

        if($('#mrs-grid_c0_all').prop("checked") == true){

            var all_check = $('#mrs-grid_c0_all').val();
            var all_check_arr = jQuery.makeArray( all_check );
            var idList = $(idList).not(all_check_arr).get();
        }

    

  
	//var selected = item-detail-grid_c0_all
//var idList    = $("input[type=checkbox]:checked").serialize();
var url = "<?php echo CController::createUrl('mrs/merge') ?>";
jQuery.ajax({
    'type': 'POST',
    'url': '<?php echo CController::createUrl('mrs/merge') ?>',
  //  'dataType':"json",
    'data': {'idList': idList,'vendor_id':vendor_id},
    'success': function (data) {
    	   
        	location.reload();
     
     
  
    },
    'cache': false
 }
 );	
console.log(idList);
}
</script>
 