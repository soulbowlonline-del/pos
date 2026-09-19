<?php
/**
 * Ported from protected/views/item/_list.php.
 */

use app\components\Gx;
use app\models\Item;
use app\models\ItemCategory;
use app\models\ItemCompany;
use app\models\ItemCompanyCategory;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'item-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		'title',
		'item_code',
		'image_file:html',
		[
				'attribute' => 'item_type',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->item_type); },
				'filter'=>Item::getTypeOptions(),
				],
		[
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>Item::getStatusOptions(),
				],
		/*
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>Item::getTypeOptions(),
				),
		array(
				'attribute' => 'is_tax',
				'value' => function ($data, $key, $index) { return ($data->is_tax === 0) ? Yii::t('app', 'No') : Yii::t('app', 'Yes'); },
				'filter' => array('0' => 'No', '1' => 'Yes'),
				),
		array(
				'attribute' => 'is_discount',
				'value' => function ($data, $key, $index) { return ($data->is_discount === 0) ? Yii::t('app', 'No') : Yii::t('app', 'Yes'); },
				'filter' => array('0' => 'No', '1' => 'Yes'),
				),
		array(
			'attribute' =>'category_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->category); },
			'filter'=>Gx::listData(ItemCategory::class),
			),
		array(
			'attribute' =>'sub_company_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->subCompany); },
			'filter'=>Gx::listData(ItemCompanyCategory::class),
			),
		array(
			'attribute' =>'company_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->company); },
			'filter'=>Gx::listData(ItemCompany::class),
			),
		array(
			'attribute' =>'updated_by',
			'value' => function ($data, $key, $index) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
		[
			'class' => ActionColumn::class,
		],
	],
]); ?>