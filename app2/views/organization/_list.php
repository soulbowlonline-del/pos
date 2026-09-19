<?php
/**
 * Ported from protected/views/organization/_list.php.
 */

use app\components\Gx;
use app\models\City;
use app\models\Country;
use app\models\Organization;
use app\models\State;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'organization-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		'title',
		'link',
		'email',
		'contact_no',
		'address:html',
		/*
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>Organization::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>Organization::getTypeOptions(),
				),
		array(
			'attribute' =>'city_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->city); },
			'filter'=>Gx::listData(City::class),
			),
		array(
			'attribute' =>'state_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->state); },
			'filter'=>Gx::listData(State::class),
			),
		array(
			'attribute' =>'country_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->country); },
			'filter'=>Gx::listData(Country::class),
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