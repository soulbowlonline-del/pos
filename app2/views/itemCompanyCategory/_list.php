<?php
/**
 * Ported from protected/views/itemCompanyCategory/_list.php.
 */

use app\components\Gx;
use app\models\ItemCompanyCategory;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'item-company-category-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		'title',
		/* array(
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>ItemCompanyCategory::getTypeOptions(),
				), */
		[
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>ItemCompanyCategory::getStatusOptions(),
				], 
		/* 'update_time',
		array(
			'attribute' =>'company_id',
			'value' => function ($data) { return Gx::str($data->company); },
			'filter'=>Gx::listData(Company::class),
			), */
		/*
		array(
			'attribute' =>'updated_by',
			'value' => function ($data) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
		 [
		 	'header'=>'Actions',
			'class' => ActionColumn::class,
		 	'template'=>'{delete}'
		], 
	],
]); ?>