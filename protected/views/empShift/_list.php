
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'emp-shift-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		//'id',
		array(
			'name'=>'emp_id',
			'value'=>'GxHtml::valueEx($data->emp)',
			'filter'=>GxHtml::listDataEx(Emp::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'shift_id',
			'value'=>'GxHtml::valueEx($data->shift)',
			'filter'=>GxHtml::listDataEx(Shift::model()->findAllAttributes(null, true)),
			),
		/* array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>EmpShift::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>EmpShift::getTypeOptions(),
				),
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			), */
		/* array(
			'class' => 'CxButtonColumn',
		), */
	),
)); ?>