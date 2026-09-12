
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'vendor-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
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
				'name' => 'is_local_vendor',
				'value' => '($data->is_local_vendor === 0) ? Yii::t(\'app\', \'No\') : Yii::t(\'app\', \'Yes\')',
				'filter' => array('0' => Yii::t('app', 'No'), '1' => Yii::t('app', 'Yes')),
				),
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>Vendor::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>Vendor::getTypeOptions(),
				),
		array(
			'name'=>'city_id',
			'value'=>'GxHtml::valueEx($data->city)',
			'filter'=>GxHtml::listDataEx(City::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'state_id',
			'value'=>'GxHtml::valueEx($data->state)',
			'filter'=>GxHtml::listDataEx(State::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'country_id',
			'value'=>'GxHtml::valueEx($data->country)',
			'filter'=>GxHtml::listDataEx(Country::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'outlet_id',
			'value'=>'GxHtml::valueEx($data->outlet)',
			'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			),
		*/
		array(
			'class' => 'CxButtonColumn',
		),
	),
)); ?>