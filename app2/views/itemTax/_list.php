<?php
/**
 * Ported from protected/views/itemTax/_list.php.
 */

use app\models\ItemTax;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'item-tax-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		'item_detail_id',
		'tax_id',
		[
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>ItemTax::getStatusOptions(),
				],
		[
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>ItemTax::getTypeOptions(),
				],
		'updated_by',
		[
			'class' => ActionColumn::class,
		],
	],
]); ?>