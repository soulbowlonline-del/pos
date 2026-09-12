
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'state-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
		'title',
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>State::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>State::getTypeOptions(),
				),
		array(
			'name'=>'country_id',
			'value'=>'GxHtml::valueEx($data->country)',
			'filter'=>GxHtml::listDataEx(Country::model()->findAllAttributes(null, true)),
			),
		'update_time',
		/*
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