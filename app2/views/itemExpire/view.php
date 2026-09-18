<?php
/**
 * Ported from protected/views/itemExpire/view.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Item;
use app\models\ItemDetail;
use app\models\ItemExpire;
use app\models\Outlet;
use app\models\User;
use app\models\Vendor;
use app\widgets\ActionColumn;
use app\widgets\ButtonGroup;
use app\widgets\CJuiDatePicker;
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
'total_amt',

			[
					'attribute' => 'vendor_id',
					'format' => 'raw',
					'value' => $model->vendor !== null ? Html::a(Html::encode(Gx::str($model->vendor)), Gx::url(['vendor/view', 'id' => Gx::pk($model->vendor)])) : null,
			],
[
			'attribute' => 'outlet',
			'format' => 'raw',
			'value' => $model->outlet !== null ? Html::a(Html::encode(Gx::str($model->outlet)), Gx::url(['outlet/view', 'id' => Gx::pk($model->outlet)])) : null,
			],
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
[
			'attribute' => 'createUser',
			'format' => 'raw',
			'value' => $model->createUser !== null ? Html::a(Html::encode(Gx::str($model->createUser)), Gx::url(['user/view', 'id' => Gx::pk($model->createUser)])) : null,
			],
[
			'attribute' => 'updatedBy',
			'format' => 'raw',
			'value' => $model->updatedBy !== null ? Html::a(Html::encode(Gx::str($model->updatedBy)), Gx::url(['user/view', 'id' => Gx::pk($model->updatedBy)])) : null,
			],
	],
]); ?>
<div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
<?php echo GridView::widget([
	'id' => 'item-expire-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $itemExpireItem->search(),
	'filter' => $itemExpireItem,
		'pager'=>true,
		/* 'afterAjaxUpdate'=>"function(){
                                                       $.datepicker.setDefaults($.datepicker.regional['en']);
                                                        $('#Projects_projStart').datepicker({'dateFormat': 'yy-mm-dd'});
		
                                                }", */
	'columns' => [
		//'id',
			[
					'attribute' =>'item_id',
					'header'=>'Item',
					'value' => function ($data) { return Gx::str($data->item); },
					//	'filter'=>Gx::listData(Item::class),
			],
			[
					'attribute' =>'item_detail_id',
					'header'=>'Barcode',
					'value' => function ($data) { return Gx::str($data->itemDetail); },
					//	'filter'=>Gx::listData(ItemDetail::class),
			],
			[
					'attribute' =>'vendor_id',
					'value' => function ($data) { return Gx::str($data->vendor); },
					'filter'=>Gx::listData(Vendor::class),
			],
			[
					'attribute' =>'outlet_id',
					'value' => function ($data) { return Gx::str($data->outlet); },
					'filter'=>Gx::listData(Outlet::class),
			],
			'qty',
		'mrp',
		'sale_rate',
	//	'free',
			[
					'attribute' =>'total_amt',
					'value' => function ($data) { return $data->total_amt; },
					'footer'=>$model->getTotals($itemExpireItem->search()->getKeys(),'total_amt','tbl_item_expire_item'),
			],
			
// 			array(
// 					'header' => '<a>Create Time</a>',
// 					'attribute' => 'create_time',
// 					'value' => function ($data) { return date("Y-m-d",strtotime($data->create_time)); },
// 					'filter' => CJuiDatePicker::widget(// 							array(
// 									'model' => $model,
// 									'attribute' => 'create_time',
// 									'language' => 'en',
// 									'htmlOptions' => array(
// 											'id' => 'Projects_projStart',
// 											'dateFormat' => 'yy-mm-dd',
// 									),
// 									'options' => array(  // (#3)
// 											'showOn' => 'focus',
// 											'dateFormat' => 'yy-mm-dd',
// 											'showOtherMonths' => true,
// 											'selectOtherMonths' => false,
// 											'changeMonth' => false,
// 											'changeYear' => false,
// 									)
// 							),
// 							true),
			
// 			),
		/*
		'qty',
		'total_amt',
		'vendor_id',
		array(
			'attribute' =>'outlet_id',
			'value' => function ($data) { return Gx::str($data->outlet); },
			'filter'=>Gx::listData(Outlet::class),
			),
		array(
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>ItemExpire::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>ItemExpire::getTypeOptions(),
				),
		array(
			'attribute' =>'updated_by',
			'value' => function ($data) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
		/* array(
			'class' => ActionColumn::class,
			'htmlOptions' => array('nowrap'=>'nowrap'),
		), */
			/* array(
						
					'header'=>'<a>Action</a>',
					'class' => ActionColumn::class,
					'template' => '{delete}', //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
								
							'delete'=>array(
			
									'url' => function ($data) { return Ui::to("itemExpireItem/delete", ["id" => $data->id]); },
									'label'=>'Delete',
									'options'=>array('class'=>'update'),
										
							)
					)
			), */
	],
]); ?>
</div>
</div>
</div>
</section>