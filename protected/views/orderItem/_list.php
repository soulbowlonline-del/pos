
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'order-item-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
			array(
					'header' => '<a>Barcode</a>',
					'value'=>'isset($data->itemDetail)?$data->itemDetail->bar_code:""'
					
			),
			array(
					'header' => '<a>Item</a>',
					'value'=>'$data->getItemName()'
			
			),
			'qty',
		array(
					'name' => 'price',
					'value'=>'$data->price'
		
			),
		'discount_amt',
			array (
					'header' => '<a>Tax</a>',
					'value' => 'isset($data->tax)?$data->tax->title:""',
			),
			array (
					'header' => '<a>HSN Code</a>',
					'value' => 'isset($data->item)?$data->item->hsn_code:""',
			),
			array (
					'header' => '<a>CGST(%age)</a>',
					'value' => 'isset($data->tax)?$data->tax->tax_val1:""',
			),
			array (
					'header' => '<a>SGST(%age)</a>',
					'value' => 'isset($data->tax)?$data->tax->tax_val2:""',
			),
			array (
					'header' => '<a>CESS(%age)</a>',
					'value' => 'isset($data->tax)?$data->tax->tax_val3:""',
			),
			array(
					'name' => 'tax_amount',
					'value'=>'$data->tax_amount'
			
			),
			
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