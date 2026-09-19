<?php
/**
 * Ported from protected/views/freeItem/admin.php.
 */

use app\components\Gx;
use app\models\FreeItem;
use app\models\Item;
use app\models\ItemCategory;
use app\models\ItemCompany;
use app\models\ItemDetail;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\ButtonGroup;
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
	$.fn.yiiGridView.update('free-item-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content">
<div class="page-header">
<h1 class="pull-left"><?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?></h1>
<?php //echo Html::a('Delete',array('user/empty'));?>
<?php echo ButtonGroup::widget([
	'buttons'=>$this->context->menu,
	'type'=>'success',
	'htmlOptions'=>['class'=> 'pull-right'],
]);
?>
<div class="clearfix"></div>
</div>

<div class="table-responsive customgridwidth">
<?php echo GridView::widget([
	'id' => 'free-item-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => [
		'id',
		'title',
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
			'attribute' =>'item_category_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->itemCategory); },
			'filter'=>Gx::listData(ItemCategory::class),
			],
		[
			'attribute' =>'item_company_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->itemCompany); },
			'filter'=>Gx::listData(ItemCompany::class),
			],
		/*
		'qty',
		'stock_qty',
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>FreeItem::getTypeOptions(),
				),
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>FreeItem::getStatusOptions(),
				),
		'update_time',
		array(
			'attribute' =>'updated_by',
			'value' => function ($data, $key, $index) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
		[
			'header'=>'Actions',
			'class' => ActionColumn::class,
			'template'=>'{view}'
				
		],
	],
]); ?>
</div>
</section>