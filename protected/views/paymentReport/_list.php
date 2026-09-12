
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'payment-report-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
		'doc_no',
		'chq_no',
		'comp_code',
		'house_bank',
		'hb_acct',
		/*
		'ben_acc_no',
		'ref_no',
		'amount',
		'vendor_id',
		'run_date',
		'inst_date',
		'value_date',
		array(
				'name' => 'pay_type',
				'value'=>'$data->getTypeOptions($data->pay_type)',
				'filter'=>PaymentReport::getTypeOptions(),
				),
		array(
				'name' => 'pay_status',
				'value'=>'$data->getStatusOptions($data->pay_status)',
				'filter'=>PaymentReport::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>PaymentReport::getTypeOptions(),
				),
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>PaymentReport::getStatusOptions(),
				),
		'update_time',
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