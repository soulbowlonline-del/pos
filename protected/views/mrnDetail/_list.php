
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'mrn-detail-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
			array(
					'name'=>'item_id',
					'value'=>'GxHtml::valueEx($data->item)',
					'filter'=>GxHtml::listDataEx(Item::model()->findAllAttributes(null, true)),
			),
			array(
					'name'=>'item_detail_id',
					'value'=>'GxHtml::valueEx($data->itemDetail)',
					'filter'=>GxHtml::listDataEx(ItemDetail::model()->findAllAttributes(null, true)),
			),
		'req_qty',
		'approved_qty',
		'bal_qty',
			
			
			
		/* array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>MrnDetail::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>MrnDetail::getTypeOptions(),
				), */
		/*
		'remarks:html',
		'update_time',
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'item_detail_id',
			'value'=>'GxHtml::valueEx($data->itemDetail)',
			'filter'=>GxHtml::listDataEx(ItemDetail::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'mrn_id',
			'value'=>'GxHtml::valueEx($data->mrn)',
			'filter'=>GxHtml::listDataEx(Mrn::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'outlet_id',
			'value'=>'GxHtml::valueEx($data->outlet)',
			'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
		*/
	/* 	array(
			'class' => 'CxButtonColumn',
		), */
	),
)); ?>