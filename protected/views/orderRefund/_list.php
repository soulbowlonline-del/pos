
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'order-refund-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
		'qty',
		'discount',
		'discount_amt',
		'total_amt',
		'paid_amt',
		/*
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>OrderRefund::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>OrderRefund::getTypeOptions(),
				),
		'city_id',
		array(
				'name' => 'state_id',
				'value'=>'$data->getStatusOptions($data->state_id)',
				'filter'=>OrderRefund::getStatusOptions(),
				),
		'country_id',
		'address:html',
		'note:html',
		'update_time',
		'order_id',
		'customer_id',
		'updated_by',
		*/
		array(
			'class' => 'CxButtonColumn',
		),
	),
)); ?>