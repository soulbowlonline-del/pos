
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'item-return-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
		'discount_amt',
		'other_charge',
		'total_amt',
		'vendor_id',
		'outlet_id',
		/*
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>ItemReturn::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>ItemReturn::getTypeOptions(),
				),
		'credit_note_id',
		'updated_by',
		*/
		array(
			'class' => 'CxButtonColumn',
		),
	),
)); ?>