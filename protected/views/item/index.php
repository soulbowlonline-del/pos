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
	$.fn.yiiGridView.update('item-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<?php if($model->name != ''){
	 Yii::app()->session['item_name'] = $model->name;
 }else{
 	Yii::app()->session['item_name'] = '';
 }?>
<section class="content-header">
  <h1> <?php echo Yii::t('app', 'Manage') . ' : ' . GxHtml::encode($model->label(2)); ?> </h1>
  <?php $this->widget('bootstrap.widgets.TbButtonGroup', array(
	'buttons'=>$this->menu,
	'type'=>'success',
	'htmlOptions'=>array('class'=> 'pull-right bttn-box'),
));
?>
</section>
<?php    /* $this->widget('bootstrap.widgets.TbMenu', array(
       'type' => 'pills',
       'stacked' => false,
       'items' => array(
        		array('label' => 'Export',
        				'url' => array('item/admin' ,'exportCSV'=>'1',
        						
        		
        		),
       		
       		),
       ),
   )); */  ?>

<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
     <div class="box">
        <div class="box-header"><h3 class="box-title">Items</h3></div>
          <div class="box-body">
              <?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'item-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>

<?php echo $form->textFieldRow($model,'name',array('class'=>'form-control','maxlength'=>255)); ?>


	<div class="form-actions">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		)); ?>
	</div>

<?php $this->endWidget(); ?>



          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">


<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'item-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
		'pager' => true,
	'filter' => $model,
	'columns' => array(
		'id',
			array(
					'visible'=>$model->checkPermission ("item/create")=="true",
					'header'=>'<a>Status</a>',
					'class'=>'CButtonColumn',
					'template' => '{update}', //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
							/* 	'view'=>array(
							 'visible'=>'$data->checkPermission ("item/view")=="true"',
									'url' =>'Yii::app()->controller->createUrl("item/view", array("id" => $data->id))',
									'label'=>'View',
									'options'=>array('class'=>'view'),
			
							), */
							'update'=>array(
									'visible'=>'$data->checkPermission ("item/create")=="true"',
									'url' =>'Yii::app()->controller->createUrl("item/create", array("id" => $data->id))',
									'label'=>'Update',
									'options'=>array('class'=>'update'),
										
							)
					)
			),
			
		'title',
			'short_name',
			//'hsn_code',
		
		//'item_code',
			array(
					'name'=>'mrp',
					'value'=>'$data->mrp',
					'filterHtmlOptions'=>array('class'=>'item_mrp_field'),
			
			),
			array(
					'name'=>'purchase_price',
					'value'=>'$data->purchase_price',
					'filterHtmlOptions'=>array('class'=>'item_purchase_price_field'),
						
			),
			array(
					'header'=>'Bar Code',
					'name'=>'bar_code',
					'value'=>'$data->getItemBarcodes()',
			
			),
			/* array(
					'header'=>'Tax',
					'name'=>'tax_id',
					'value'=>'$data->getMainItemTax()',
					'filter'=>GxHtml::listDataEx(Tax::model()->findAllAttributes(null, true)),
						
			), */
			array('header'=>'Tax',
				////	'class' => 'bootstrap.widgets.TbEditableColumn',
					'name' => 'tax_id',
					//  'data_demanded_quantity' => '$data->demanded_quantity',
					//  'data-state_id' => '$data->state_id',
					//  'visible'=>'$data->getStateValue('.$model->id.') == 0',
					'value' => '$data->getMainItemTax()',
					'headerHtmlOptions' => array('style' => 'width: 110px'),
					
				//	'filter'=>$model->getTaxList(),
			
			),
			array(
					'name'=>'hsn_code',
					'value'=>'$data->hsn_code',
					'filterHtmlOptions'=>array('class'=>'item_hsn_code_field'),
						
			),
			array(
					'name'=>'item_code',
					'value'=>'$data->item_code',
					'filterHtmlOptions'=>array('class'=>'item_item_code_field'),
			
			),
				
			//'purchase_price',
			array(
						'header'=>'Total Remain Qty',
					'value'=>'$data->getTotalRemainingQuantity()',
					'htmlOptions'=>array('class'=>'item_qty_field'),
						
			),
			/* array(
					'name'=>'category_id',
					'value'=>'GxHtml::valueEx($data->category)',
					'filter'=>$model->getParentCategorys(),
			), */
			array(
					//'class' => 'bootstrap.widgets.TbEditableColumn',
					'name' => 'category_id',
					//  'data_demanded_quantity' => '$data->demanded_quantity',
					//  'data-state_id' => '$data->state_id',
					//  'visible'=>'$data->getStateValue('.$model->id.') == 0',
					'value' => 'GxHtml::valueEx($data->category)',
					'headerHtmlOptions' => array('style' => 'width: 110px'),
					
					'filter'=>$model->getParentCategorys(),
						
			),
			/* array(
					'name'=>'sub_category_id',
					'value'=>'GxHtml::valueEx($data->subcategory)',
					'filter'=>$model->getSubCategorys(),
			), */
			array(
				//	'class' => 'bootstrap.widgets.TbEditableColumn',
					'name' => 'sub_category_id',
					//  'data_demanded_quantity' => '$data->demanded_quantity',
					//  'data-state_id' => '$data->state_id',
					//  'visible'=>'$data->getStateValue('.$model->id.') == 0',
					'value' => 'GxHtml::valueEx($data->subcategory)',
					'headerHtmlOptions' => array('style' => 'width: 110px'),
				
					'filter'=>$model->getSubCategorys(),
						
			),
			array(
				//	'class' => 'bootstrap.widgets.TbEditableColumn',
					'name' => 'company_id',
					//  'data_demanded_quantity' => '$data->demanded_quantity',
					//  'data-state_id' => '$data->state_id',
					//  'visible'=>'$data->getStateValue('.$model->id.') == 0',
					'value' => 'GxHtml::valueEx($data->company)',
					'headerHtmlOptions' => array('style' => 'width: 110px'),
					
					'filter'=>$model->getParentCompanys(),
			
			),
		/* 	array(
					'name'=>'company_id',
					'value'=>'GxHtml::valueEx($data->company)',
					'filter'=>$model->getParentCompanys(),
			), */
		/* 	array(
					'name'=>'opening_stock',
					'value'=>'$data->opening_stock',
					'filterHtmlOptions'=>array('class'=>'item_opening_stock_field'),
						
			),
			array(
					'name'=>'weight',
					'value'=>'$data->weight',
					'filterHtmlOptions'=>array('class'=>'item_weight_field'),
						
			), */
			
	//	'image_file:html',
	/* 	array(
				'name' => 'item_type',
				'value'=>'$data->getTypeOptions($data->item_type)',
				'filter'=>Item::getTypeOptions(),
				), */
		
			array(
				//	'visible'=>$model->checkPermission ("item/active")=="true",
				//	'class' => 'bootstrap.widgets.TbEditableColumn',
					'name' => 'status',
					//  'data_demanded_quantity' => '$data->demanded_quantity',
					//  'data-state_id' => '$data->state_id',
					//  'visible'=>'$data->getStateValue('.$model->id.') == 0',
					'value' => 'Item::getStatusOptions($data->status)',
					'headerHtmlOptions' => array('style' => 'width: 110px'),
					
					'filter'=>Item::getStatusOptions(),
			
			),
			/* array(
					'visible'=>$model->checkPermission ("item/active")=="true",
					'header'=>'<a>Active/InActive</a>',
					'class'=>'CButtonColumn',
					'template' => '{Active}{InActive}', //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
							 'Active'=>array(
							 'visible'=>'$data->status == '.Item::STATUS_INACTIVE,
									'url' =>'Yii::app()->controller->createUrl("item/toggle", array("id" => $data->id))',
									'label'=>'Active',
									'options'=>array('class'=>'view'),
			
							), 
							'InActive'=>array(
									 'visible'=>'$data->status == '.Item::STATUS_ACTIVE,
									'url' =>'Yii::app()->controller->createUrl("item/toggle", array("id" => $data->id))',
									'label'=>'InActive',
									'options'=>array('class'=>'update'),
										
							)
					)
			), */
		/*
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>Item::getTypeOptions(),
				),
		array(
				'name' => 'is_tax',
				'value' => '($data->is_tax === 0) ? Yii::t(\'app\', \'No\') : Yii::t(\'app\', \'Yes\')',
				'filter' => array('0' => Yii::t('app', 'No'), '1' => Yii::t('app', 'Yes')),
				),
		array(
				'name' => 'is_discount',
				'value' => '($data->is_discount === 0) ? Yii::t(\'app\', \'No\') : Yii::t(\'app\', \'Yes\')',
				'filter' => array('0' => Yii::t('app', 'No'), '1' => Yii::t('app', 'Yes')),
				),
		array(
			'name'=>'category_id',
			'value'=>'GxHtml::valueEx($data->category)',
			'filter'=>GxHtml::listDataEx(ItemCategory::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'sub_company_id',
			'value'=>'GxHtml::valueEx($data->subCompany)',
			'filter'=>GxHtml::listDataEx(ItemCompanyCategory::model()->findAllAttributes(null, true)),
			),
		
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			),
		*/
			
			/* array(
				'visible'=>'$data->checkPermission ("itemDetail/admin")=="true"',
					'header'=>'Vendor',
				'name'=>'vendor_id',
					'value'=>'$data->getLatestVendorName()',
				'filter'=>GxHtml::listDataEx(Vendor::model()->findAllAttributes(null, true)),
						
			),  */
			array(
						
					'header'=>'<a>Details</a>',
					'class'=>'CButtonColumn',
					'template' => '{Details}', //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
							'Details'=>array(
									'visible'=>'$data->checkPermission ("itemDetail/admin")=="true"',
									'url' =>'Yii::app()->controller->createUrl("itemDetail/admin", array("id" => $data->id))',
									'label'=>'SubItems',
									'options'=>array('class'=>'view'),
			
							),
							
					)
			),
			
			/* array(
					'name'=>'max_qty',
					'value'=>'$data->max_qty',
					'filterHtmlOptions'=>array('class'=>'item_max_qty_field'),
			
			), */
			
			
			/* array(
					'name'=>'min_qty',
					'value'=>'$data->min_qty',
					'filterHtmlOptions'=>array('class'=>'item_max_qty_field'),
			
			), */
			
			/* array(
					'name'=>'reorder_qty',
					'value'=>'$data->reorder_qty',
					'filterHtmlOptions'=>array('class'=>'item_max_qty_field'),
			
			), */
			
		/* 	array(
					'name' => 'status',
					'value'=>'$data->getStatusOptions($data->status)',
					'filter'=>Item::getStatusOptions(),
					'filterHtmlOptions'=>array('class'=>'item_status_field'),
			), */
			
	),
)); ?>

</div>
</div>
</div>
</div>
</div>
</div>
</div>
</section>