<?php
/**
 * Ported from protected/views/mrn/_list.php.
 */

use app\components\Gx;
use app\models\Mrn;
use app\models\Mrs;
use app\models\Organization;
use app\models\Outlet;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'mrn-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		'code',
		'mrs_date',
		'mrs_update_date',
		'mrs_req_date',
		[
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>Mrn::getStatusOptions(),
				],
		/*
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>Mrn::getTypeOptions(),
				),
		'remarks:html',
		'update_time',
		array(
			'attribute' =>'updated_by',
			'value' => function ($data, $key, $index) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		array(
			'attribute' =>'outlet_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->outlet); },
			'filter'=>Gx::listData(Outlet::class),
			),
		array(
			'attribute' =>'mrs_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->mrs); },
			'filter'=>Gx::listData(Mrs::class),
			),
		array(
			'attribute' =>'organization_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->organization); },
			'filter'=>Gx::listData(Organization::class),
			),
		*/
		[
			'class' => ActionColumn::class,
		],
	],
]); ?>