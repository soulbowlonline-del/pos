
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'online-order-item-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
		'pager'=>true,
	'columns' => array(
		//'id',
		'name',
		
		'barcode',
			'product_code',
			'qty',
		'price',
		'total',
		//'image_url:html',
		/*
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>OnlineOrderItem::getTypeOptions(),
				),
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>OnlineOrderItem::getStatusOptions(),
				),
		'update_time',
		'updated_by',
		*/
		
	),
)); ?>