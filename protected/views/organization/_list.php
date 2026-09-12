
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'organization-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
		'title',
		'link',
		'email',
		'contact_no',
		'address:html',
		/*
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>Organization::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>Organization::getTypeOptions(),
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