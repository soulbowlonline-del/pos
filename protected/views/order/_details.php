
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'order-item-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		//'id',
			array(
					'header' => 'Barcode',
					'value'=>'isset($data->itemDetail)?$data->itemDetail->bar_code:""'
					
			),
			array(
					'header' => 'Item',
					'value'=>'$data->getItemName()'
			
			),
			array(
					'header' => 'Quantity',
					'value'=>'$data->getItemQuantity()'
		
			),
			//'qty',
		'price',
		'discount_amt',
			'tax_amount',
		/*
		'discount_amt',
		'tax_id',
		'tax_amount',
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>OrderItem::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>OrderItem::getTypeOptions(),
				),
		'update_time',
		'updated_by',
		*/
	/* 	array(
			'class' => 'CxButtonColumn',
		), */
	),
)); ?>