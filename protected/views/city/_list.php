
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'city-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
		'title',
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>City::getTypeOptions(),
				),
		array(
			'name'=>'state_id',
			'value'=>'GxHtml::valueEx($data->state)',
			'filter'=>GxHtml::listDataEx(State::model()->findAllAttributes(null, true)),
			),
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>City::getStatusOptions(),
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