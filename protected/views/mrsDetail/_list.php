			<div class="col-md-12 item-list-table">
<div class="table-responsive customsmallgridwidth">
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'mrs-detail-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
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
			array(
					'header'=>'Ttl Rmn Qty',
					'value'=>'isset($data->item)?$data->item->getTotalRemainingQuantity():""',
					'htmlOptions'=>array('class'=>'item_qty_field'),
			
			),
		'req_qty',
		'approved_qty',
			'mrp',
			'sale_rate',
			'price',
			'margin',
			'discount',
			'discount_amt',
			'discount1',
			'discount_amt1',
			array(
					'name'=>'tax_id',
					'value'=>'GxHtml::valueEx($data->tax)',
					
			),
			'amount'
		/* 'bal_qty',
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>MrsDetail::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>MrsDetail::getTypeOptions(),
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
			'name'=>'mrs_id',
			'value'=>'GxHtml::valueEx($data->mrs)',
			'filter'=>GxHtml::listDataEx(Mrs::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'outlet_id',
			'value'=>'GxHtml::valueEx($data->outlet)',
			'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
		*/
		
	),
)); ?>
</div>
</div>