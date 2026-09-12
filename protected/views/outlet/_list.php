
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'outlet-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
		'title',
		'email',
		'contact_no',
		'secondary_contact_no',
		'address:html',
		/*
		'tax_no:html',
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>Outlet::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>Outlet::getTypeOptions(),
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
			'name'=>'organization_id',
			'value'=>'GxHtml::valueEx($data->organization)',
			'filter'=>GxHtml::listDataEx(Organization::model()->findAllAttributes(null, true)),
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