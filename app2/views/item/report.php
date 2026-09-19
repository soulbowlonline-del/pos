<?php
/**
 * Ported from protected/views/item/report.php.
 */

use app\components\Gx;
use app\models\Item;
use app\models\Vendor;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\GridView;
use app\widgets\Menu;
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
  <h1> <?php echo 'Stock Report'; ?> </h1>
  
</section>
<?php    echo Menu::widget([
       'type' => 'pills',
       'stacked' => false,
       'items' => [
        		['label' => 'Export',
        				'url' => ['item/report' ,'exportCSV'=>'1',
        						
        		
        		],
       		
       		],
       ],
   ]);  ?>

<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
     <div class="box">
        <div class="box-header"><h3 class="box-title">Stock Report</h3></div>
          <div class="box-body">
              <?php $form = ActiveForm::begin([
	'id' => 'item-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
<div class="col-md-6">

<?php echo $form->datepickerRow($model, 'start_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
						'options'=>['format'=>'yyyy-mm-dd']])

; ?>
</div>
<div class="col-md-6">
<?php echo $form->datepickerRow($model, 'end_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
								'options'=>['format'=>'yyyy-mm-dd']])

; ?>
</div>

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
	'dataProvider' => $model->reportstocksearch(),
		'pager' => true,
	'filter' => $model,
	'columns' => [
	//	'id',
			[
					'attribute' =>'item_code',
					'value' => function ($data, $key, $index) { return $data->item_code; },
					'filterInputOptions' =>['class'=>'item_item_code_field'],
			
			],
			[
					'header'=>'Bar Code',
					'attribute' =>'bar_code',
					'value' => function ($data, $key, $index) { return $data->getItemBarcodes(); },
						
			],
		'title',
			//'hsn_code',
			/* array(
					'attribute' =>'hsn_code',
					'value' => function ($data, $key, $index) { return $data->hsn_code; },
					'filterInputOptions' =>array('class'=>'item_hsn_code_field'),
			
			), */
			
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
			//'purchase_price',
			 [
					'header'=>'Opening',
					'value' => function ($data, $key, $index) { return $data->getOpeningQuantity(); },
					'htmlOptions'=>['class'=>'item_qty_field'],
			
			],
		 	[
					'header'=>'Adjustment',
					'value' => function ($data, $key, $index) { return $data->getAdjustmentQuantity(); },
					'htmlOptions'=>['class'=>'item_qty_field'],
						
			],
			[
					'header'=>'Purchase/Added',
					'value' => function ($data, $key, $index) { return $data->getAddedQuantity(); },
					'htmlOptions'=>['class'=>'item_qty_field'],
			
			], 
			[
					'header'=>'Purchase Return',
					'value' => function ($data, $key, $index) { return $data->getReturnQuantity(); },
					'htmlOptions'=>['class'=>'item_qty_field'],
						
			],
			[
					'header'=>'Sale/Order',
					'value' => function ($data, $key, $index) { return $data->getSoldQuantity(); },
					'htmlOptions'=>['class'=>'item_qty_field'],
			
			],
			[
					'header'=>'Sale/Order Refund',
					'value' => function ($data, $key, $index) { return $data->getRefundQuantity(); },
					'htmlOptions'=>['class'=>'item_qty_field'],
			
			],
			[
					'header'=>'Expiry',
					'value' => function ($data, $key, $index) { return $data->getExpiryQuantity(); },
					'htmlOptions'=>['class'=>'item_qty_field'],
						
			],
			[
						'header'=>'Total Remain Qty',
					'value' => function ($data, $key, $index) { return $data->getStockRemainingQuantity(); },
					'htmlOptions'=>['class'=>'item_qty_field'],
						
			],
		/*	*/
		
			/* array(
				'visible' => function ($data) { return $data->checkPermission ("itemDetail/admin")=="true"; },
					'header'=>'Vendor',
				'attribute' =>'vendor_id',
					'value' => function ($data, $key, $index) { return $data->getLatestVendorName(); },
				'filter'=>Gx::listData(Vendor::class),
						
			),  */
			
			/* array(
					'attribute' =>'max_qty',
					'value' => function ($data, $key, $index) { return $data->max_qty; },
					'filterInputOptions' =>array('class'=>'item_max_qty_field'),
			
			), */
			
			/* array(
					'attribute' =>'min_qty',
					'value' => function ($data, $key, $index) { return $data->min_qty; },
					'filterInputOptions' =>array('class'=>'item_max_qty_field'),
			
			), */
			
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