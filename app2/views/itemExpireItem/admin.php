<?php
/**
 * Ported from protected/views/itemExpireItem/admin.php.
 */

use app\components\Gx;
use app\models\Item;
use app\models\ItemDetail;
use app\models\ItemExpire;
use app\models\ItemExpireItem;
use app\models\Outlet;
use app\models\User;
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
	$.fn.yiiGridView.update('item-expire-item-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<div class="page-header">
	<h1><?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?></h1>
</div>
<p>
You may optionally enter a comparison operator (&lt;, &lt;=, &gt;, &gt;=, &lt;&gt; or =) at the beginning of each of your search values to specify how the comparison should be done.
</p>


<?php echo GridView::widget([
	'id' => 'item-expire-item-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => [
		'id',
		[
			'attribute' =>'item_id',
			'value' => function ($data) { return Gx::str($data->item); },
			'filter'=>Gx::listData(Item::class),
			],
		[
			'attribute' =>'item_detail_id',
			'value' => function ($data) { return Gx::str($data->itemDetail); },
			'filter'=>Gx::listData(ItemDetail::class),
			],
		'mrp',
		'sale_rate',
		'free',
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
				'filter'=>ItemExpireItem::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>ItemExpireItem::getTypeOptions(),
				),
		array(
			'attribute' =>'item_expire_id',
			'value' => function ($data) { return Gx::str($data->itemExpire); },
			'filter'=>Gx::listData(ItemExpire::class),
			),
		array(
			'attribute' =>'updated_by',
			'value' => function ($data) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
		[
			'class' => ActionColumn::class,
			'htmlOptions' => ['nowrap'=>'nowrap'],
		],
	],
]); ?>