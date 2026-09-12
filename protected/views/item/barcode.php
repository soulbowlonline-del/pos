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
	$.fn.yiiGridView.update('item-detail-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content">
<div class="page-header">
<h1 class="pull-left"><?php echo Yii::t('app', 'Print Barcodes'); ?></h1>
<?php //echo CHtml::link('Delete',array('user/empty'));?>
<?php $this->widget('bootstrap.widgets.TbButtonGroup', array(
	'buttons'=>$this->menu,
	'type'=>'success',
	'htmlOptions'=>array('class'=> 'pull-right'),
));
?>
<div class="clearfix"></div>
</div>
<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'item-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	

	<?php //echo $form->errorSummary($model); ?>


<?php echo $form->dropDownListRow($model, 'company_id', $model->getParentCompanys(),array('class'=>'form-control')); ?>






	<div class="form-actions">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		)); ?>
	</div>

<?php $this->endWidget(); ?>
<br>

<input type="button" value="Print Barcode" onclick="act();" />
<br>
 <select id="check_expiry">
  <option value="2">Without Expiry</option>  
  <option value="1">With Expiry</option>
 
</select> 

<div class="table-responsive customgridwidth">
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'item-detail-grid',
	'type'=>'striped bordered condensed',
		'pager'=>true,
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => array(
	//	'id',
			
			array(
					'class'           => 'CCheckBoxColumn',
					'selectableRows'  => 100,
					'value'           => '$data["id"]',
					'checkBoxHtmlOptions' => array("name" =>"idList[]"),
			
			),
			
		
				
	array(
					'name'=>'item_id',
					'value'=>'GxHtml::valueEx($data->item)',
				//	'filter'=>$model->getItemOptions(),
			),
		'bar_code',
			array(
					'header'=>'MRP',
					'name'=>'mrp',
					'value'=>'$data->getItemDetailMrp()',
					
			),
			array(
					'header'=>'Purchase Price',
					'name'=>'purchase_price',
					'value'=>'isset($data->item)?$data->item->purchase_price:""',
					'filterHtmlOptions'=>array('class'=>'item_purchase_price_field'),
						
			),
			array(
					'header'=>'HSN Code',
					'name'=>'hsn_code',
					'value'=>'isset($data->item)?$data->item->hsn_code:""',
					'filterHtmlOptions'=>array('class'=>'item_hsn_code_field'),
						
			),
			
			array(
					'header'=>'Product Code',
					'name'=>'product_code',
					'value'=>'isset($data->item)?$data->item->item_code:""',
					'filterHtmlOptions'=>array('class'=>'item_item_code_field'),
			
			),
			array(
					'class' => 'bootstrap.widgets.TbEditableColumn',
					'name' => 'expiry_date',
					//  'data_demanded_quantity' => '$data->demanded_quantity',
					//  'data-state_id' => '$data->state_id',
					//  'visible'=>'$data->getStateValue('.$model->id.') == 0',
				 'value' => "date('Y-m-d')",
					'headerHtmlOptions' => array('style' => 'width: 110px'),
					'editable' => array(
							//'url'     => $this->createUrl('demandVoucherItem/updated'),
							'placement'  => 'left',
							'inputclass' => 'span3',
							'attribute' =>'expiry_date',
							'type'     => 'text',
							//  'apply' => '$data->getStateValue('.$model->id.') == "0"'
			
							/*        'validate' => 'js: function(value) {
							 if($.trim(value) > "$data->demanded_quantity") return "Approved Quantity can not be greater than Demanded Quantity";
			}' */
					)
			
			),
			array(
					'class' => 'bootstrap.widgets.TbEditableColumn',
					'name' => 'packing_date',
					//  'data_demanded_quantity' => '$data->demanded_quantity',
					//  'data-state_id' => '$data->state_id',
					//  'visible'=>'$data->getStateValue('.$model->id.') == 0',
					'value' => "",
					'headerHtmlOptions' => array('style' => 'width: 110px'),
					'editable' => array(
							//'url'     => $this->createUrl('demandVoucherItem/updated'),
							'placement'  => 'left',
							'inputclass' => 'span3',
							
							'attribute' =>'packing_date',
							'type'     => 'text',
							//  'apply' => '$data->getStateValue('.$model->id.') == "0"'
								
							/*        'validate' => 'js: function(value) {
							 if($.trim(value) > "$data->demanded_quantity") return "Approved Quantity can not be greater than Demanded Quantity";
			}' */
					)
						
			),
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>ItemDetail::getStatusOptions(),
				),
			array(
					'name'=>'tax_id',
					'value'=>'GxHtml::valueEx($data->tax)',
					'filter'=>GxHtml::listDataEx(Tax::model()->findAllAttributes(null, true)),
			),
			
		/* 	array(
					'name'=>'item_id',
					'value'=>'GxHtml::valueEx($data->item)',
					'filter'=>$model->getItemOptions(),
			), */
		/*'reorder_qty',
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>ItemDetail::getTypeOptions(),
				),
		array(
			'name'=>'tax_id',
			'value'=>'GxHtml::valueEx($data->tax)',
			'filter'=>GxHtml::listDataEx(Tax::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			),
		*/
		/* 	array(
			
					'header'=>'<a>Status</a>',
					'class'=>'CButtonColumn',
					'template' => '{view}{update}', //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
							'view'=>array(
									'visible'=>'$data->checkPermission ("itemDetail/view")=="true"',
									'url' =>'Yii::app()->controller->createUrl("itemDetail/view", array("id" => $data->id))',
									'label'=>'View',
									'options'=>array('class'=>'view'),
										
							),
							'update'=>array(
									'visible'=>'$data->checkPermission ("itemDetail/update")=="true"',
									'url' =>'Yii::app()->controller->createUrl("itemDetail/update", array("id" => $data->id))',
									'label'=>'Update',
									'options'=>array('class'=>'update'),
			
							)
					)
			), */
	),
)); ?>


</div>
</section>
<script>
function act()
{
	
	var idList = [];
	var date_list = [];
	var packing_date_list = [];
	var expiry_val = 1;
	$('input[type=checkbox]:checked').each(function() {
		idList.push(this.value); 
		date_list.push($('#ItemDetail_expiry_date_'+this.value).text()); 
		packing_date_list.push($('#ItemDetail_packing_date_'+this.value).text()); 
	});
	console.log(idList);
	console.log(date_list);

        if($('#item-detail-grid_c0_all').prop("checked") == true){

            var all_check = $('#item-detail-grid_c0_all').val();
            var all_check_arr = jQuery.makeArray( all_check );
            var idList = $(idList).not(all_check_arr).get();
           var all_checkk = $('#ItemDetail_expiry_date_'+all_check).text();
            var all_checkk_arr = jQuery.makeArray( all_checkk );
            var date_list = $(date_list).not(all_checkk_arr).get();
            var all_checkkk = $('#ItemDetail_packing_date_'+all_check).text();
            var all_checkkk_arr = jQuery.makeArray( all_checkkk );
            var packing_date_list = $(packing_date_list).not(all_checkkk_arr).get();
        }

        expiry_val = $('#check_expiry').val();

       // console.log(expiry_date_list);
	//var selected = item-detail-grid_c0_all
//var idList    = $("input[type=checkbox]:checked").serialize();
var url = "<?php echo CController::createUrl('item/printBarcode') ?>";
jQuery.ajax({
    'type': 'POST',
    'url': '<?php echo CController::createUrl('itemDetail/print') ?>',
  //  'dataType':"json",
    'data': {'idList': idList,'date_list':date_list,'expiry_val':expiry_val,'packing_date_list':packing_date_list},
    'success': function (data) {
    	   console.log(data);
           console.log(url);
      if(data == 'success'){
        	window.open(url,'_blank');
     
        }else{
        	alert('Select Items');
        } 
  
    },
    'cache': false
 }
 );	
console.log(idList);
}
</script>