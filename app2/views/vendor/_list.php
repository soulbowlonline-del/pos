<?php
/**
 * Ported from protected/views/vendor/_list.php.
 */

use Yii;
use app\components\Gx;
use app\models\City;
use app\models\Country;
use app\models\Outlet;
use app\models\State;
use app\models\User;
use app\models\Vendor;
use app\widgets\ActionColumn;
use app\widgets\GridView;
?>
<?php
echo GridView::widget([
	'id' => 'vendor-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => [
		'id',
		'name',
		'contact_person',
		'person_designation',
		'contact_no',
		'secondary_contact_no',
		/*
		'primary_address:html',
		'secondary_address:html',
		'tax_no',
		array(
				'attribute' => 'is_local_vendor',
				'value' => function ($data) { return ($data->is_local_vendor === 0) ? Yii::t('app', 'No') : Yii::t('app', 'Yes'); },
				'filter' => array('0' => 'No', '1' => 'Yes'),
				),
		array(
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>Vendor::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>Vendor::getTypeOptions(),
				),
		array(
			'attribute' =>'city_id',
			'value' => function ($data) { return Gx::str($data->city); },
			'filter'=>Gx::listData(City::class),
			),
		array(
			'attribute' =>'state_id',
			'value' => function ($data) { return Gx::str($data->state); },
			'filter'=>Gx::listData(State::class),
			),
		array(
			'attribute' =>'country_id',
			'value' => function ($data) { return Gx::str($data->country); },
			'filter'=>Gx::listData(Country::class),
			),
		array(
			'attribute' =>'outlet_id',
			'value' => function ($data) { return Gx::str($data->outlet); },
			'filter'=>Gx::listData(Outlet::class),
			),
		array(
			'attribute' =>'updated_by',
			'value' => function ($data) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
		[
			'class' => ActionColumn::class,
		],
	],
]); ?>