<?php
/**
 * Ported from protected/views/itemReturn/view.php.
 */

use app\components\Gx;
use app\models\Item;
use app\models\ItemDetail;
use app\models\ItemReturn;
use app\models\ItemReturnItem;
use app\widgets\ButtonGroup;
use app\widgets\DetailView;
use app\widgets\GridView;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	Gx::str($model),
];


?>

<section class="content">
<div class="page-header">
<h1 class="pull-left"><?php echo Html::encode(Gx::str($model)); ?></h1>


<?php   echo ButtonGroup::widget([
	'buttons'=>$this->context->menu,
	'type'=>'success',
	'htmlOptions'=>['class'=> 'pull-right'],
	]);

	?>
<div class="clearfix"></div>


</div>

<?php echo DetailView::widget([
	'data' => $model,
	'attributes' => [
'id',
	'gross_amt',
			'tax_amt',
		'discount_amt',
//'other_charge',
'total_amt',
[
			'attribute' => 'outlet_id',
			'format' => 'raw',
			'value' => $model->outlet !== null ? Html::a(Html::encode(Gx::str($model->outlet)), Gx::url(['outlet/view', 'id' => Gx::pk($model->outlet)])) : null,
			],
[
			'attribute' => 'vendor_id',
			'format' => 'raw',
			'value' => $model->vendor !== null ? Html::a(Html::encode(Gx::str($model->vendor)), Gx::url(['vendor/view', 'id' => Gx::pk($model->vendor)])) : null,
			],
			// array(
			// 		'attribute' => 'credit_note_id',
			// 		'format' => 'raw',
			// 		'value' => $model->creditNote !== null ? Html::a(Html::encode(Gx::str($model->creditNote)), array('creditNote/view', 'id' => Gx::pk($model->creditNote))) : null,
			// ),
[
				'attribute' => 'status',
				'format' => 'raw',
				'value'=>$model->getStatusOptions($model->status),
				],
/* array(
				'attribute' => 'type_id',
				'format' => 'raw',
				'value'=>$model->getTypeOptions($model->type_id),
				), */
'create_time',
'credit_note_no',
'credit_note_date',
'create_user_id',
'updated_by',
	],
]); ?>
 <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
<?php echo GridView::widget([
	'id' => 'item-return-item-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $itemReturnItem->search(),
	'filter' => $itemReturnItem,
	'columns' => [
		//'id',
			[
					'attribute' =>'item_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->item); },
					'filter'=>Gx::listData(Item::class),
			],
			[
					'attribute' =>'item_detail_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->itemDetail); },
					'filter'=>Gx::listData(ItemDetail::class),
			],
			[
			'attribute' =>'type_id',
				'header' => 'Sent In Api',
				'value' => function ($data, $key, $index) { return $data->getAPIOptions($data->type_id); },
				'filter'=>ItemReturnItem::getAPIOptions(),
				],
			'mrp',
			'price',
			'sale_rate',
				
		
		//	 'free',
			'qty',
			'discount',
			'discount_amt',
			'discount1',
			'discount_amt1',
			[
					'header'=>'Tax',
					'value' => function ($data, $key, $index) { return Gx::str($data->tax); },
			
			],
			[
    				'visible'=>$itemReturnItem->getGSTTrue($itemReturnItem->vendor_id,$itemReturnItem->outlet_id) == true,
    				'attribute' =>'cgst_per',
    				'value' => function ($data, $key, $index) { return $data->cgst_per; },
    					
    		],
			[
					'visible'=>$itemReturnItem->getGSTTrue($itemReturnItem->vendor_id,$itemReturnItem->outlet_id) == true,
					'attribute' =>'sgst_per',
					'value' => function ($data, $key, $index) { return $data->sgst_per; },
						
			],
			[
					'visible'=>$itemReturnItem->getGSTTrue($itemReturnItem->vendor_id,$itemReturnItem->outlet_id) == true,
					'attribute' =>'cess_per',
					'value' => function ($data, $key, $index) { return $data->cess_per; },
			
			],
			
			[
					'visible'=>$itemReturnItem->getGSTTrue($itemReturnItem->vendor_id,$itemReturnItem->outlet_id) == true,
					'attribute' =>'cgst_amt',
					'value' => function ($data, $key, $index) { return $data->cgst_amt; },
			
			],
			[
					'visible'=>$itemReturnItem->getGSTTrue($itemReturnItem->vendor_id,$itemReturnItem->outlet_id) == true,
					'attribute' =>'sgst_amt',
					'value' => function ($data, $key, $index) { return $data->sgst_amt; },
						
			],
			[
					'visible'=>$itemReturnItem->getGSTTrue($itemReturnItem->vendor_id,$itemReturnItem->outlet_id) == true,
					'attribute' =>'cess_amt',
					'value' => function ($data, $key, $index) { return $data->cess_amt; },
			
			],
			[
					'visible'=>$itemReturnItem->getGSTTrue($itemReturnItem->vendor_id,$itemReturnItem->outlet_id) == false,
					'attribute' =>'igst_per',
					'value' => function ($data, $key, $index) { return $data->igst_per; },
			
			],
			[
					'visible'=>$itemReturnItem->getGSTTrue($itemReturnItem->vendor_id,$itemReturnItem->outlet_id) == false,
					'attribute' =>'igst_amt',
					'value' => function ($data, $key, $index) { return $data->igst_amt; },
						
			],
			
			
		//	'other_charge',
			'total_amt',
		//	'vendor_id',
		//	'outlet_id',
		/*
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>ItemReturn::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>ItemReturn::getTypeOptions(),
				),
		'credit_note_id',
		'updated_by',
		*/
			
	],
]); ?>
</div>
</div>
</div>
</section>