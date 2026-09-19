<?php
/**
 * Ported from protected/views/itemReturn/_list.php.
 */

use app\models\ItemReturn;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'item-return-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		'discount_amt',
		'other_charge',
		'total_amt',
		'vendor_id',
		'outlet_id',
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
		[
			'class' => ActionColumn::class,
		],
	],
]); ?>