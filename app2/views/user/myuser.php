<?php
/**
 * Ported from protected/views/user/myuser.php.
 */

use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\ButtonGroup;
use app\widgets\GridView;
?>
<?php
echo ButtonGroup::widget([
'buttons'=>$this->context->actions,
'type'=>'success',
'htmlOptions'=>['class'=> 'pull-right'],
]);
?>

<?php echo GridView::widget([
		'id' => 'task-grid',
		'type'=>'bordered', // 'condensed','striped',
		'dataProvider' => $dataProvider,
		'columns' => [
				'id',
				'username',
				//'input_file',
				[
						'attribute' => 'state_id',
						'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->state_id); },
						'filter'=>User::getStatusOptions(),
				],
				/*array(
						'attribute' => 'type_id',
						'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
						'filter'=>Bar::getTypeOptions(),
				),*/
			//	'lang_list',
			//	'lang_list_done',
				//'output_path',
			//	'complete_time',
				//'string_count',
				[
					'class' => ActionColumn::class,
				],
		],
]); ?>


