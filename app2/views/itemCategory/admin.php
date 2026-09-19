<?php
/**
 * Ported from protected/views/itemCategory/admin.php.
 */

use app\components\Access;
use app\components\Gx;
use app\components\Ui;
use app\models\ItemCategory;
use app\widgets\ActionColumn;
use app\widgets\ButtonGroup;
use app\widgets\GridView;
use app\widgets\Menu;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	'Manage',
];


$this->registerJs("
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$.fn.yiiGridView.update('item-category-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content-header">

<h1><?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?></h1>
<?php //echo Html::a('Delete',array('user/empty'));?>
<?php echo ButtonGroup::widget([
	'buttons'=>$this->context->menu,
	'type'=>'success',
	'htmlOptions'=>['class'=> 'pull-right'],
]);
?>


<?php    echo Menu::widget([
       'type' => 'pills',
       'stacked' => false,
       'items' => [
        		['label' => 'Export',
        				'url' => ['itemCategory/admin' ,'exportCSV'=>'1',
        						
        		
        		],
       		
       		],
       ],
   ]);  ?>

<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
         <div class="box-header"><h3 class="box-title"><?php echo  Html::encode($model->label(2));?></h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">


<?php echo GridView::widget([
	'id' => 'item-category-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'pager' => true,
	'filter' => $model,
	'columns' => [
			'id',
		'title',
			[
					'header' => 'Parent',
					'value' => function ($data, $key, $index) { return $data->getParentValue($data->parent_id); },
						
			],
			[
					'attribute' => 'status',
					'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
					'filter'=>ItemCategory::getStatusOptions(),
			],
		/*
		'gender_id',
		'religion',
		array(
			'attribute' =>'class_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->class); },
			'filter'=>Gx::listData(Classes::class),
			),
		array(
			'attribute' =>'section_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->section); },
			'filter'=>Gx::listData(Section::class),
			),
		array(
			'attribute' =>'school_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->school); },
			'filter'=>Gx::listData(School::class),
			),
		'parent_id',
		'roll_no',
		'image_file',
		array(
				'attribute' => 'state_id',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->state_id); },
				'filter'=>Student::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>Student::getTypeOptions(),
				),
		'update_time',
		*/
			[
						
					'header'=>'<a>Status</a>',
					'class' => ActionColumn::class,
					'template' => '{view}{update}', //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					'buttons'=>[
							'view'=>[
									'visible' => Access::check('itemCategory/view'),
									'url' => function ($data) { return Ui::to("itemCategory/view", ["id" => $data->id]); },
									'label'=>'View',
									'options'=>['class'=>'view'],
			
							],
							'update'=>[
									'visible' => Access::check('itemCategory/update'),
									'url' => function ($data) { return Ui::to("itemCategory/update", ["id" => $data->id]); },
									'label'=>'Update',
									'options'=>['class'=>'update'],
										
							]
					]
			],
	],
]); ?>



</div>
</div>
</div>
</div>
</div>
</div>
</div>
</section>