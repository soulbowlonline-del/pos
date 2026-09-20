<?php
/**
 * Ported from protected/views/itemDetail/admin.php.
 */

use app\components\Access;
use app\components\Gx;
use app\components\Ui;
use app\models\Item;
use app\models\ItemDetail;
use app\models\Tax;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\ButtonGroup;
use app\widgets\GridView;
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
	$.fn.yiiGridView.update('item-detail-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content-header">

<h1><?php echo 'Manage SubItems'; ?></h1>
<?php //echo Html::a('Delete',array('user/empty'));?>
<?php echo ButtonGroup::widget([
	'buttons'=>$this->context->menu,
	'type'=>'success',
	'htmlOptions'=>['class'=> 'pull-right'],
]);
?>
<br>



<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
         <div class="box-header"><h3 class="box-title"><?php echo 'Create SubItem';?></h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
<?php echo GridView::widget([
	'id' => 'item-detail-grid',
	'type'=>'striped bordered condensed',
		'pager'=>true,
	'dataProvider' => $model->adminsearch(),
	'filter' => $model,
	'columns' => [
	//	'id',
	/* 	array(
			'attribute' =>'item_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->item); },
			'filter'=>Gx::listData(Item::class),
			), */
		'bar_code',
			'mrp',
		'open_stock_qty',
		
		[
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>ItemDetail::getStatusOptions(),
				],
			[
					'attribute' =>'tax_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->tax); },
					'filter'=>Gx::listData(Tax::class),
			],
			[
					'attribute' =>'item_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->item); },
					//'filter'=>$model->getItemOptions(),
			],
		/*'reorder_qty',
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>ItemDetail::getTypeOptions(),
				),
		array(
			'attribute' =>'tax_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->tax); },
			'filter'=>Gx::listData(Tax::class),
			),
		array(
			'attribute' =>'updated_by',
			'value' => function ($data, $key, $index) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
			[
			
					'header'=>'<a>Status</a>',
					'class' => ActionColumn::class,
					'template' => '{view}{update}{delete}', //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					'buttons'=>[
							'view'=>[
									'visible' => function ($data) { return Access::check("itemDetail/view")=="true"; },
									'url' => function ($data) { return Ui::to("itemDetail/view", ["id" => $data->id]); },
									'label'=>'View',
									'options'=>['class'=>'view'],
										
							],
							'update'=>[
									'visible' => function ($data) { return Access::check("itemDetail/update")=="true"; },
									'url' => function ($data) { return Ui::to("itemDetail/update", ["id" => $data->id]); },
									'label'=>'Update',
									'options'=>['class'=>'update'],
			
							],
							'delete'=>[
									'visible' => function ($data) { return Access::check("itemDetail/delete")=="true"; },
									'url' => function ($data) { return Ui::to("itemDetail/delete", ["id" => $data->id]); },
									'label'=>'Delete',
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