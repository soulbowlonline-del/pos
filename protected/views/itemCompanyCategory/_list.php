
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'item-company-category-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
		'title',
		/* array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>ItemCompanyCategory::getTypeOptions(),
				), */
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>ItemCompanyCategory::getStatusOptions(),
				), 
		/* 'update_time',
		array(
			'name'=>'company_id',
			'value'=>'GxHtml::valueEx($data->company)',
			'filter'=>GxHtml::listDataEx(Company::model()->findAllAttributes(null, true)),
			), */
		/*
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			),
		*/
		 array(
		 	'header'=>'Actions',
			'class' => 'CButtonColumn',
		 	'template'=>'{delete}'
		), 
	),
)); ?>