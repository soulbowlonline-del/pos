
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'advance-payment-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
		'payment_date',
		'payment',
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>AdvancePayment::getTypeOptions(),
				),
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>AdvancePayment::getStatusOptions(),
				),
		'update_time',
		/*
		array(
			'name'=>'vendor_id',
			'value'=>'GxHtml::valueEx($data->vendor)',
			'filter'=>GxHtml::listDataEx(Vendor::model()->findAllAttributes(null, true)),
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