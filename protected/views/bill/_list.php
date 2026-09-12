
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'bill-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
		'bill_no',
		'image_file1:html',
		'image_file2:html',
		'image_file3:html',
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>Bill::getTypeOptions(),
				),
		/*
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>Bill::getStatusOptions(),
				),
		array(
			'name'=>'po_id',
			'value'=>'GxHtml::valueEx($data->po)',
			'filter'=>GxHtml::listDataEx(PurchaseOrder::model()->findAllAttributes(null, true)),
			),
		*/
		array(
			'class' => 'CxButtonColumn',
		),
	),
)); ?>