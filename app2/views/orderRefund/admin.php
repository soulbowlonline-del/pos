<?php
/**
 * Ported from protected/views/orderRefund/admin.php.
 */

use app\models\OrderRefund;
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
	$.fn.yiiGridView.update('order-refund-grid', {
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
	'id' => 'order-refund-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => [
		'id',
		'qty',
		'discount',
		'discount_amt',
		'total_amt',
		'paid_amt',
		/*
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>OrderRefund::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>OrderRefund::getTypeOptions(),
				),
		'city_id',
		array(
				'attribute' => 'state_id',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->state_id); },
				'filter'=>OrderRefund::getStatusOptions(),
				),
		'country_id',
		'address:html',
		'note:html',
		'update_time',
		'order_id',
		'customer_id',
		'updated_by',
		*/
		[
			'class' => ActionColumn::class,
			'htmlOptions' => ['nowrap'=>'nowrap'],
		],
	],
]); ?>