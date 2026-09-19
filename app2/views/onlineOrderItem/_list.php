<?php
/**
 * Ported from protected/views/onlineOrderItem/_list.php.
 */

use app\models\OnlineOrderItem;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'online-order-item-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
		'pager'=>true,
	'columns' => [
		//'id',
		'name',
		
		'barcode',
			'product_code',
			'qty',
		'price',
		'total',
		//'image_url:html',
		/*
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>OnlineOrderItem::getTypeOptions(),
				),
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>OnlineOrderItem::getStatusOptions(),
				),
		'update_time',
		'updated_by',
		*/
		
	],
]); ?>