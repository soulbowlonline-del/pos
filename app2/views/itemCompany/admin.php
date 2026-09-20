<?php
/**
 * Ported from protected/views/itemCompany/admin.php.
 */

use app\components\Access;
use app\components\Gx;
use app\components\Ui;
use app\models\ItemCompany;
use app\models\User;
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
	$.fn.yiiGridView.update('item-company-grid', {
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
</section>
<?php    echo Menu::widget([
       'type' => 'pills',
       'stacked' => false,
       'items' => [
        		['label' => 'Export',
        				'url' => ['itemCompany/admin' ,'exportCSV'=>'1',
        						
        		
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
	'id' => 'item-company-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
		'pager'=>true,
	'filter' => $model,
	'columns' => [
		'id',
		'title',
			[
					'header' => 'Parent',
					'value' => function ($data, $key, $index) { return $data->getParentValue($data->parent_id); },
					
			],
		/* array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>ItemCompany::getTypeOptions(),
				), */
	/* 	array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>ItemCompany::getStatusOptions(),
				), */
		//'update_time',
		/*
		array(
			'attribute' =>'updated_by',
			'value' => function ($data, $key, $index) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
			[
			
					'header'=>'<a>Status</a>',
					'class' => ActionColumn::class,
					'template' => '{view}{update}', //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					'buttons'=>[
							'view'=>[
									'visible' => function ($data) { return Access::check("ItemCompany/view")=="true"; },
									'url' => function ($data) { return Ui::to("ItemCompany/view", ["id" => $data->id]); },
									'label'=>'View',
									'options'=>['class'=>'view'],
										
							],
							'update'=>[
									'visible' => function ($data) { return Access::check("ItemCompany/update")=="true"; },
									'url' => function ($data) { return Ui::to("ItemCompany/update", ["id" => $data->id]); },
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