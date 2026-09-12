<?php   $this->widget('bootstrap.widgets.TbButtonGroup', array(
'buttons'=>$this->actions,
'type'=>'success',
'htmlOptions'=>array('class'=> 'pull-right'),
));
?>

<?php $this->widget('bootstrap.widgets.TbGridView', array(
		'id' => 'task-grid',
		'type'=>'bordered', // 'condensed','striped',
		'dataProvider' => $dataProvider,
		'columns' => array(
				'id',
				'username',
				//'input_file',
				array(
						'name' => 'state_id',
						'value'=>'$data->getStatusOptions($data->state_id)',
						'filter'=>User::getStatusOptions(),
				),
				/*array(
						'name' => 'type_id',
						'value'=>'$data->getTypeOptions($data->type_id)',
						'filter'=>Bar::getTypeOptions(),
				),*/
			//	'lang_list',
			//	'lang_list_done',
				//'output_path',
			//	'complete_time',
				//'string_count',
				array(
					'class' => 'CxButtonColumn',
				),
		),
)); ?>


