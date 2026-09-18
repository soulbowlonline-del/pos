<?php
/**
 * Ported from protected/views/stockLog/admin.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Item;
use app\models\ItemDetail;
use app\models\Outlet;
use app\models\StockLog;
use app\models\Vendor;
use app\widgets\ActionColumn;
use app\widgets\GridView;
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
	$.fn.yiiGridView.update('stock-log-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content-header">
<div class="row">
<div class="col-md-12 margin10">
<a href="<?php echo Ui::to('item/create',array('id'=>$id));?>" class="btn btn-primary">Product Info</a>
<a href="<?php echo Ui::to('item/extra',array('id'=>$id));?>" class="btn btn-primary">Extra Info</a>
<a href="<?php echo Ui::to('item/vendor',array('id'=>$id));?>" class="btn btn-primary">Vendor Information</a>
<a href="<?php echo Ui::to('stockLog/admin',array('id'=>$id));?>" class="btn btn-primary">Movement History</a>
<a href="<?php echo Ui::to('orderItem/index',array('id'=>$id));?>" class="btn btn-primary">Order History</a>
<a href="<?php echo Ui::to('itemDetail/create',array('id'=>$id));?>" class="btn btn-primary">Add Subitem</a>
<a href="<?php echo Ui::to('vendorSchemes/add',array('id'=>$id));?>" class="btn btn-primary">Add Vendor Scheme</a>

</div>
</div>
 
<h1><?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?></h1>
<?php //echo Html::a('Delete',array('user/empty'));?>

</section>

<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
         <div class="box-header"><h3 class="box-title"><?php echo  Html::encode($model->label(2));?></h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customsmallgridwidth">
<?php echo GridView::widget([
	'id' => 'stock-log-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'pager'=>true,
	'filter' => $model,
	'columns' => [
		//'id',
		[
				'header'=>'<a>Bar Code</a>',
			'attribute' =>'item_detail_id',
			'value' => function ($data) { return Gx::str($data->itemDetail); },
			'filter'=>Gx::listData(ItemDetail::class),
			],
		[
			'attribute' =>'item_id',
			'value' => function ($data) { return Gx::str($data->item); },
			'filter'=>Gx::listData(Item::class),
			],
		'batch_no','previous_qty',
			'current_qty',
		'Qty',
			[
					'attribute' => 'type_id',
					'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
					'filter'=>StockLog::getTypeOptions(),
			],
		[
			'attribute' =>'outlet_id',
			'value' => function ($data) { return Gx::str($data->outlet); },
			'filter'=>Gx::listData(Outlet::class),
			],
			[
					'attribute' =>'vendor_id',
					'value' => function ($data) { return Gx::str($data->vendor); },
					'filter'=>Gx::listData(Vendor::class),
			],
			[
					'attribute' =>'create_time',
					'value' => function ($data) { return date("Y-m-d",strtotime($data->create_time)); },
					//'filter'=>Gx::listData(Vendor::class),
			],
		/*
		array(
			'attribute' =>'vendor_id',
			'value' => function ($data) { return Gx::str($data->vendor); },
			'filter'=>Gx::listData(Vendor::class),
			),
		array(
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>StockLog::getTypeOptions(),
				),
		array(
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>StockLog::getStatusOptions(),
				),
		'update_time',
		*/
		/* array(
			'class' => ActionColumn::class,
			'htmlOptions' => array('nowrap'=>'nowrap'),
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