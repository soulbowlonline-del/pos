
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'emp-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
		'code',
		'name',
		'email',
		'contact_no',
		'gender_id',
		/*
		'date_of_birth',
		'date_of_joining',
		'permanent_address:html',
		'temp_address:html',
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>Emp::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>Emp::getTypeOptions(),
				),
		array(
			'name'=>'designation_id',
			'value'=>'GxHtml::valueEx($data->designation)',
			'filter'=>GxHtml::listDataEx(Designation::model()->findAllAttributes(null, true)),
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