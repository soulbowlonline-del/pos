<?php
/**
 * Ported from protected/views/item/admin.php.
 */

use app\components\Access;
use app\components\Gx;
use app\components\Ui;
use app\models\Item;
use app\models\ItemCategory;
use app\models\ItemCompanyCategory;
use app\models\Tax;
use app\models\User;
use app\models\Vendor;
use app\widgets\ActionColumn;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\ButtonGroup;
use app\widgets\EditableColumn;
use app\widgets\GridView;
use app\widgets\Menu;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	'Manage',
];


$this->registerJs("
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
	 Yii::$app->session['item_name'] = $model->name;
 }else{
 	Yii::$app->session['item_name'] = '';
 }?>
<section class="content-header">
  <h1> <?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?> </h1>
  <?php echo ButtonGroup::widget([
	'buttons'=>$this->context->menu,
	'type'=>'success',
	'htmlOptions'=>['class'=> 'pull-right bttn-box'],
]);
?>
</section>
<?php    echo Menu::widget([
       'type' => 'pills',
       'stacked' => false,
       'items' => [
        		['label' => 'Export',
        				'url' => ['item/admin' ,'exportCSV'=>'1',
        						
        		
        		],
						
       		
       		],
					['label' => 'Export Margin',
        				'url' => ['item/export' ,'exportCSV'=>'1',
        						
        		
        		],
					],
					['label' => 'Export New MRP',
        				'url' => ['item/exportnew' ,'exportCSV'=>'1',
        						
        		
        		],
					],
					['label' => 'Import Tax Data',
        				'url' => ['item/importTaxData'],
					],
					['label' => 'Batch Update Prices',
        				'url' => ['item/batchUpdatePrices'],
					],
       ],
   ]);  ?>

<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
     <div class="box">
        <div class="box-header"><h3 class="box-title">Items</h3></div>
          <div class="box-body">
              <?php $form = ActiveForm::begin([
	'id' => 'item-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>

<?php echo $form->textFieldRow($model,'name',['class'=>'form-control','maxlength'=>255]); ?>


	<div class="form-actions">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		]); ?>
	</div>

<?php ActiveForm::end(); ?>



          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">


<?php echo GridView::widget([
	'id' => 'item-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
		'pager' => true,
	'filter' => $model,
	'columns' => [
		'id',
			[
					'visible'=>$model->checkPermission ("item/create")=="true",
					'header'=>'<a>Status</a>',
					'class' => ActionColumn::class,
					'template' => '{update}', //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					'buttons'=>[
							/* 	'view'=>array(
							 'visible' => Access::check('item/view'),
									'url' => function ($data) { return Ui::to("item/view", ["id" => $data->id]); },
									'label'=>'View',
									'options'=>array('class'=>'view'),
			
							), */
							'update'=>[
									'visible' => Access::check('item/create'),
									'url' => function ($data) { return Ui::to("item/create", ["id" => $data->id]); },
									'label'=>'Update',
									'options'=>['class'=>'update'],
										
							]
					]
			],
			
		'title',
			'short_name',
			//'hsn_code',
		
		//'item_code',
			[
					'attribute' =>'mrp',
					'value' => function ($data, $key, $index) { return $data->mrp; },
					'filterInputOptions' =>['class'=>'item_mrp_field'],
			
			],
			[
					'attribute' =>'purchase_price',
					'value' => function ($data, $key, $index) { return $data->purchase_price; },
					'filterInputOptions' =>['class'=>'item_purchase_price_field'],
						
			],
			[
					'header'=>'Bar Code',
					'attribute' =>'bar_code',
					'value' => function ($data, $key, $index) { return $data->getItemBarcodes(); },
			
			],
			/* array(
					'header'=>'Tax',
					'attribute' =>'tax_id',
					'value' => function ($data, $key, $index) { return $data->getMainItemTax(); },
					'filter'=>Gx::listData(Tax::class),
						
			), */
			['header'=>'Tax',
					'class' => EditableColumn::class,
					'attribute' => 'tax_id',
					//  'data_demanded_quantity' => '$data->demanded_quantity',
					//  'data-state_id' => '$data->state_id',
					//  'visible' => function ($data) { return $data->getStateValue($model->id) == 0; },
					'value' => function ($data, $key, $index) { return $data->getMainItemTax(); },
					'headerOptions' => ['style' => 'width: 110px'],
					'editable' => [
							'url'     => Ui::to('item/taxUpdate'),
							'placement'  => 'left',
							'inputclass' => 'span3',
							'attribute' =>'tax_id',
							'type'     => 'select',
							'source' => $model->getTaxList(),
							//  'apply' => '$data->getStateValue('.$model->id.') == "0"'
			
							/*        'validate' => 'js: function(value) {
							 if($.trim(value) > "$data->demanded_quantity") return "Approved Quantity can not be greater than Demanded Quantity";
			}' */
					],
				//	'filter'=>$model->getTaxList(),
			
			],
			[
					'attribute' =>'hsn_code',
					'value' => function ($data, $key, $index) { return $data->hsn_code; },
					'filterInputOptions' =>['class'=>'item_hsn_code_field'],
						
			],
			[
					'attribute' =>'item_code',
					'value' => function ($data, $key, $index) { return $data->item_code; },
					'filterInputOptions' =>['class'=>'item_item_code_field'],
			
			],
				
			//'purchase_price',
			[
						'header'=>'Total Remain Qty',
					'value' => function ($data, $key, $index) { return $data->getTotalRemainingQuantity(); },
					'htmlOptions'=>['class'=>'item_qty_field'],
						
			],
			/* array(
					'attribute' =>'category_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->category); },
					'filter'=>$model->getParentCategorys(),
			), */
			[
					'class' => EditableColumn::class,
					'attribute' => 'category_id',
					//  'data_demanded_quantity' => '$data->demanded_quantity',
					//  'data-state_id' => '$data->state_id',
					//  'visible' => function ($data) { return $data->getStateValue($model->id) == 0; },
					'value' => function ($data, $key, $index) { return Gx::str($data->category); },
					'headerOptions' => ['style' => 'width: 110px'],
					'editable' => [
							'url'     => Ui::to('item/gridUpdate'),
							'placement'  => 'left',
							'inputclass' => 'span3',
							'attribute' =>'category_id',
							'type'     => 'select',
							'source' => $model->getParentCategorys(),
							//  'apply' => '$data->getStateValue('.$model->id.') == "0"'
								
							/*        'validate' => 'js: function(value) {
							 if($.trim(value) > "$data->demanded_quantity") return "Approved Quantity can not be greater than Demanded Quantity";
			}' */
					],
					'filter'=>$model->getParentCategorys(),
						
			],
			/* array(
					'attribute' =>'sub_category_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->subcategory); },
					'filter'=>$model->getSubCategorys(),
			), */
			[
					'class' => EditableColumn::class,
					'attribute' => 'sub_category_id',
					//  'data_demanded_quantity' => '$data->demanded_quantity',
					//  'data-state_id' => '$data->state_id',
					//  'visible' => function ($data) { return $data->getStateValue($model->id) == 0; },
					'value' => function ($data, $key, $index) { return Gx::str($data->subcategory); },
					'headerOptions' => ['style' => 'width: 110px'],
					'editable' => [
							'url'     => Ui::to('item/gridUpdate'),
							'placement'  => 'left',
							'inputclass' => 'span3',
							'attribute' =>'sub_category_id',
							'type'     => 'select',
							'source' =>  $model->getSubCategorys(),
							//  'apply' => '$data->getStateValue('.$model->id.') == "0"'
								
							/*        'validate' => 'js: function(value) {
							 if($.trim(value) > "$data->demanded_quantity") return "Approved Quantity can not be greater than Demanded Quantity";
			}' */
					],
					'filter'=>$model->getSubCategorys(),
						
			],
			[
					'class' => EditableColumn::class,
					'attribute' => 'company_id',
					//  'data_demanded_quantity' => '$data->demanded_quantity',
					//  'data-state_id' => '$data->state_id',
					//  'visible' => function ($data) { return $data->getStateValue($model->id) == 0; },
					'value' => function ($data, $key, $index) { return Gx::str($data->company); },
					'headerOptions' => ['style' => 'width: 110px'],
					'editable' => [
							'url'     => Ui::to('item/gridUpdate'),
							'placement'  => 'left',
							'inputclass' => 'span3',
							'attribute' =>'company_id',
							'type'     => 'select',
							'source' => $model->getParentCompanys(),
							//  'apply' => '$data->getStateValue('.$model->id.') == "0"'
			
							/*        'validate' => 'js: function(value) {
							 if($.trim(value) > "$data->demanded_quantity") return "Approved Quantity can not be greater than Demanded Quantity";
			}' */
					],
					'filter'=>$model->getParentCompanys(),
			
			],
		/* 	array(
					'attribute' =>'company_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->company); },
					'filter'=>$model->getParentCompanys(),
			), */
		/* 	array(
					'attribute' =>'opening_stock',
					'value' => function ($data, $key, $index) { return $data->opening_stock; },
					'filterInputOptions' =>array('class'=>'item_opening_stock_field'),
						
			),
			array(
					'attribute' =>'weight',
					'value' => function ($data, $key, $index) { return $data->weight; },
					'filterInputOptions' =>array('class'=>'item_weight_field'),
						
			), */
			
	//	'image_file:html',
	/* 	array(
				'attribute' => 'item_type',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->item_type); },
				'filter'=>Item::getTypeOptions(),
				), */
		
			[
					'visible'=>$model->checkPermission ("item/active")=="true",
					'class' => EditableColumn::class,
					'attribute' => 'status',
					//  'data_demanded_quantity' => '$data->demanded_quantity',
					//  'data-state_id' => '$data->state_id',
					//  'visible' => function ($data) { return $data->getStateValue($model->id) == 0; },
					'value' => function ($data, $key, $index) { return Item::getStatusOptions($data->status); },
					'headerOptions' => ['style' => 'width: 110px'],
					'editable' => [
							
							'url' =>Ui::to('item/toggle'),
							'placement'  => 'left',
							'inputclass' => 'span3',
							'attribute' =>'status',
							'type'     => 'select',
							'source' => Item::getStatusOptions(),
							//  'apply' => '$data->getStateValue('.$model->id.') == "0"'
			
							/*        'validate' => 'js: function(value) {
							 if($.trim(value) > "$data->demanded_quantity") return "Approved Quantity can not be greater than Demanded Quantity";
			}' */
					],
					'filter'=>Item::getStatusOptions(),
			
			],
			/* array(
					'visible'=>$model->checkPermission ("item/active")=="true",
					'header'=>'<a>Active/InActive</a>',
					'class' => ActionColumn::class,
					'template' => '{Active}{InActive}', //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
							 'Active'=>array(
							 'visible' => function ($data) { return $data->status == Item::STATUS_INACTIVE; },
									'url' => function ($data) { return Ui::to("item/toggle", ["id" => $data->id]); },
									'label'=>'Active',
									'options'=>array('class'=>'view'),
			
							), 
							'InActive'=>array(
									 'visible' => function ($data) { return $data->status == Item::STATUS_ACTIVE; },
									'url' => function ($data) { return Ui::to("item/toggle", ["id" => $data->id]); },
									'label'=>'InActive',
									'options'=>array('class'=>'update'),
										
							)
					)
			), */
		/*
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>Item::getTypeOptions(),
				),
		array(
				'attribute' => 'is_tax',
				'value' => function ($data, $key, $index) { return ($data->is_tax === 0) ? Yii::t('app', 'No') : Yii::t('app', 'Yes'); },
				'filter' => array('0' => 'No', '1' => 'Yes'),
				),
		array(
				'attribute' => 'is_discount',
				'value' => function ($data, $key, $index) { return ($data->is_discount === 0) ? Yii::t('app', 'No') : Yii::t('app', 'Yes'); },
				'filter' => array('0' => 'No', '1' => 'Yes'),
				),
		array(
			'attribute' =>'category_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->category); },
			'filter'=>Gx::listData(ItemCategory::class),
			),
		array(
			'attribute' =>'sub_company_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->subCompany); },
			'filter'=>Gx::listData(ItemCompanyCategory::class),
			),
		
		array(
			'attribute' =>'updated_by',
			'value' => function ($data, $key, $index) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
			[
					'class' => EditableColumn::class,
					'attribute' => 'vendor_id',
					//  'data_demanded_quantity' => '$data->demanded_quantity',
					//  'data-state_id' => '$data->state_id',
					//  'visible' => function ($data) { return $data->getStateValue($model->id) == 0; },
					'value' => function ($data, $key, $index) { return $data->getLatestVendorName(); },
					'headerOptions' => ['style' => 'width: 110px'],
					'editable' => [
							'url'     => Ui::to('item/gridUpdate'),
							'placement'  => 'left',
							'inputclass' => 'span3',
							'attribute' =>'vendor_id',
							'type'     => 'select',
							'source' => Gx::listData(Vendor::class),
							//  'apply' => '$data->getStateValue('.$model->id.') == "0"'
								
							/*        'validate' => 'js: function(value) {
							 if($.trim(value) > "$data->demanded_quantity") return "Approved Quantity can not be greater than Demanded Quantity";
			}' */
					],
					'filter'=>Gx::listData(Vendor::class),
						
			],
			/* array(
				'visible' => Access::check('itemDetail/admin'),
					'header'=>'Vendor',
				'attribute' =>'vendor_id',
					'value' => function ($data, $key, $index) { return $data->getLatestVendorName(); },
				'filter'=>Gx::listData(Vendor::class),
						
			),  */
			[
						
					'header'=>'<a>Details</a>',
					'class' => ActionColumn::class,
					'template' => '{Details}', //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					'buttons'=>[
							'Details'=>[
									'visible' => Access::check('itemDetail/admin'),
									'url' => function ($data) { return Ui::to("itemDetail/admin", ["id" => $data->id]); },
									'label'=>'SubItems',
									'options'=>['class'=>'view'],
			
							],
							
					]
			],
			[
			
					'header'=>'<a>Free </a>',
					'class' => ActionColumn::class,
					'template' => '{Free}', //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					'buttons'=>[
							'Free'=>[
									//'visible' => Access::check('itemDetail/admin'),
									'url' => function ($data) { return Ui::to("freeItem/admin", ["id" => $data->id]); },
									'label'=>'Free Items',
									'options'=>['class'=>'view'],
										
							],
								
					]
			],
			/* array(
					'attribute' =>'max_qty',
					'value' => function ($data, $key, $index) { return $data->max_qty; },
					'filterInputOptions' =>array('class'=>'item_max_qty_field'),
			
			), */
			[
					'class' => EditableColumn::class,
					'attribute' => 'max_qty',
					//  'data_demanded_quantity' => '$data->demanded_quantity',
					//  'data-state_id' => '$data->state_id',
					//  'visible' => function ($data) { return $data->getStateValue($model->id) == 0; },
					'value' => function ($data, $key, $index) { return $data->max_qty; },
					'headerOptions' => ['style' => 'width: 110px'],
					'editable' => [
							'url'     => Ui::to('item/gridUpdate'),
							'placement'  => 'left',
							'inputclass' => 'span3',
							'attribute' =>'max_qty',
							'type'     => 'text',
							//  'apply' => '$data->getStateValue('.$model->id.') == "0"'
			
							/*        'validate' => 'js: function(value) {
							 if($.trim(value) > "$data->demanded_quantity") return "Approved Quantity can not be greater than Demanded Quantity";
			}' */
					]
			
			],
			[
					'class' => EditableColumn::class,
					'attribute' => 'min_qty',
					//  'data_demanded_quantity' => '$data->demanded_quantity',
					//  'data-state_id' => '$data->state_id',
					//  'visible' => function ($data) { return $data->getStateValue($model->id) == 0; },
					'value' => function ($data, $key, $index) { return $data->min_qty; },
					'headerOptions' => ['style' => 'width: 110px'],
					'editable' => [
							'url'     => Ui::to('item/gridUpdate'),
							'placement'  => 'left',
							'inputclass' => 'span3',
							'attribute' =>'min_qty',
							'type'     => 'text',
							//  'apply' => '$data->getStateValue('.$model->id.') == "0"'
								
							/*        'validate' => 'js: function(value) {
							 if($.trim(value) > "$data->demanded_quantity") return "Approved Quantity can not be greater than Demanded Quantity";
			}' */
					]
						
			],
			/* array(
					'attribute' =>'min_qty',
					'value' => function ($data, $key, $index) { return $data->min_qty; },
					'filterInputOptions' =>array('class'=>'item_max_qty_field'),
			
			), */
			[
					'class' => EditableColumn::class,
					'attribute' => 'reorder_qty',
					//  'data_demanded_quantity' => '$data->demanded_quantity',
					//  'data-state_id' => '$data->state_id',
					//  'visible' => function ($data) { return $data->getStateValue($model->id) == 0; },
					'value' => function ($data, $key, $index) { return $data->reorder_qty; },
					'headerOptions' => ['style' => 'width: 110px'],
					'editable' => [
							'url'     => Ui::to('item/gridUpdate'),
							'placement'  => 'left',
							'inputclass' => 'span3',
							'attribute' =>'reorder_qty',
							'type'     => 'text',
							//  'apply' => '$data->getStateValue('.$model->id.') == "0"'
			
							/*        'validate' => 'js: function(value) {
							 if($.trim(value) > "$data->demanded_quantity") return "Approved Quantity can not be greater than Demanded Quantity";
			}' */
					]
			
			],
			/* array(
					'attribute' =>'reorder_qty',
					'value' => function ($data, $key, $index) { return $data->reorder_qty; },
					'filterInputOptions' =>array('class'=>'item_max_qty_field'),
			
			), */
			
		/* 	array(
					'attribute' => 'status',
					'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
					'filter'=>Item::getStatusOptions(),
					'filterInputOptions' =>array('class'=>'item_status_field'),
			), */
			
	],
]); ?>

</div>
</div>
</div>
</div>
</div>
</div>
</div>
</section>