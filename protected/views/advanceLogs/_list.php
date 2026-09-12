
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'advance-logs-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		//'id',
		'amount',
		array(
			'name'=>'advance_payment_id',
			'value'=>'GxHtml::valueEx($data->advancePayment)',
			'filter'=>GxHtml::listDataEx(AdvancePayment::model()->findAllAttributes(null, true)),
			),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>AdvanceLogs::getTypeOptions(),
				),
	/* 	array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>AdvanceLogs::getStatusOptions(),
				), */
		'create_time',
		/* array(
			'class' => 'CxButtonColumn',
		), */
	),
)); ?>