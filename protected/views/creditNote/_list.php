
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'credit-note-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
		'credit_number',
		'amt',
		'amt_used',
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>CreditNote::getTypeOptions(),
				),
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>CreditNote::getStatusOptions(),
				),
		/*
		'update_time',
		*/
		array(
			'class' => 'CxButtonColumn',
		),
	),
)); ?>