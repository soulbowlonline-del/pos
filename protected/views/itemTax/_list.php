
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'item-tax-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
		'item_detail_id',
		'tax_id',
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>ItemTax::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>ItemTax::getTypeOptions(),
				),
		'updated_by',
		array(
			'class' => 'CxButtonColumn',
		),
	),
)); ?>