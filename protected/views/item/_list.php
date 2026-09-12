
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'item-grid',
	'type'=>'bordered', // 'condensed','striped',
	'dataProvider' => $dataProvider,
	'columns' => array(
		'id',
		'title',
		'item_code',
		'image_file:html',
		array(
				'name' => 'item_type',
				'value'=>'$data->getTypeOptions($data->item_type)',
				'filter'=>Item::getTypeOptions(),
				),
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>Item::getStatusOptions(),
				),
		/*
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>Item::getTypeOptions(),
				),
		array(
				'name' => 'is_tax',
				'value' => '($data->is_tax === 0) ? Yii::t(\'app\', \'No\') : Yii::t(\'app\', \'Yes\')',
				'filter' => array('0' => Yii::t('app', 'No'), '1' => Yii::t('app', 'Yes')),
				),
		array(
				'name' => 'is_discount',
				'value' => '($data->is_discount === 0) ? Yii::t(\'app\', \'No\') : Yii::t(\'app\', \'Yes\')',
				'filter' => array('0' => Yii::t('app', 'No'), '1' => Yii::t('app', 'Yes')),
				),
		array(
			'name'=>'category_id',
			'value'=>'GxHtml::valueEx($data->category)',
			'filter'=>GxHtml::listDataEx(ItemCategory::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'sub_company_id',
			'value'=>'GxHtml::valueEx($data->subCompany)',
			'filter'=>GxHtml::listDataEx(ItemCompanyCategory::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'company_id',
			'value'=>'GxHtml::valueEx($data->company)',
			'filter'=>GxHtml::listDataEx(ItemCompany::model()->findAllAttributes(null, true)),
			),
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