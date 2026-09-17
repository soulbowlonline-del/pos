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
        <div class="box-header"><h3 class="box-title">Grn</h3></div>
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
<?php echo $form->dropdownListRow($model, 'vendor_id',GxHtml::listDataEx(Vendor::model()->findAllByAttributes(array('status'=>Vendor::STATUS_ACTIVE))),array('class'=>'form-control')); ?>
<?php $this->endWidget(); ?>
<div class="table-responsive">
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'purchase-bill-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->listsearch($val = true),
	'filter' => $model,
		'pager'=>true,
		'afterAjaxUpdate'=>"function(){
                                                       $.datepicker.setDefaults($.datepicker.regional['en']);
                                                        $('#Projects_projStart').datepicker({'dateFormat': 'yy-mm-dd'});
		
                                                }",
	'columns' => array(
			array(
					'class'           => 'CCheckBoxColumn',
					'selectableRows'  => 100,
					'value'           => '$data["id"]',
					'checkBoxHtmlOptions' => array("name" =>"idList[]"),
			
			),
		'id',
		//	'bill_no',
			//'start_date',
			array('name'=>'start_date',
					'value'=>'$data->start_date',
					'filter' => $this->widget('zii.widgets.jui.CJuiDatePicker',
							array(
									'model' => $model,
									'attribute' => 'start_date',
									'language' => 'en',
									'htmlOptions' => array(
											'id' => 'Projects_projStart',
											'dateFormat' => 'yy-mm-dd',
									),
									'options' => array(  // (#3)
											'showOn' => 'focus',
											'dateFormat' => 'yy-mm-dd',
											'showOtherMonths' => true,
											'selectOtherMonths' => false,
											'changeMonth' => false,
											'changeYear' => false,
									)
							),
							true),
								
),
			'end_date',
			/* array(
					'name' => 'total_amount',
					'value'=>'isset($data->total_amount)?$data->total_amount:""',
			
			), */
			array(
					'name'=>'vendor_id',
					'value'=>'GxHtml::valueEx($data->vendor)',
					'filter'=>GxHtml::listDataEx(Vendor::model()->findAllAttributes(null, true)),
			),
			array(
					'name'=>'outlet_id',
					'value'=>'GxHtml::valueEx($data->outlet)',
					'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
			array(
						
					'header'=>'<a>Status</a>',
					'class'=>'FaButtonColumn',
					'template' => '{view}',  //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
							'view'=>array(
								//	'visible'=>'$data->state_id=='.User::STATUS_INACTIVE,
									'url' =>'Yii::app()->controller->createUrl("purchaseBill/view", array("id" => $data->id))',
									'label'=>'view',
									'options'=>array('class'=>'view'),
			
							),
							
					)
			),
		
		
	),
)); ?>

</div>
<input type="button" value="Merge Grn" onclick="act();" />
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
	var vendor_id = $('#PurchaseBill_vendor_id').val();
	
	$('input[type=checkbox]:checked').each(function() {
		idList.push(this.value); 
		
		
	});
	console.log('idList'+idList);
	

        if($('#purchase-bill-grid_c0_all').prop("checked") == true){

            var all_check = $('#purchase-bill-grid_c0_all').val();
            var all_check_arr = jQuery.makeArray( all_check );
            var idList = $(idList).not(all_check_arr).get();
        }

    

  
	//var selected = item-detail-grid_c0_all
//var idList    = $("input[type=checkbox]:checked").serialize();
var url = "<?php echo CController::createUrl('purchaseBill/merge') ?>";
jQuery.ajax({
    'type': 'POST',
    'url': '<?php echo CController::createUrl('purchaseBill/merge') ?>',
  //  'dataType':"json",
    'data': {'idList': idList,'vendor_id':vendor_id},
    'success': function (data) {
    	   console.log('data'+data);
        location.reload();
     
     
  
    },
    'cache': false
 }
 );	
console.log(idList);
}
</script>
 