<?php
/**
 * Ported from protected/views/itemCompanyCategory/admin.php.
 */

use app\components\Access;
use app\components\Gx;
use app\components\Ui;
use app\models\ItemCompany;
use app\models\ItemCompanyCategory;
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
	$.fn.yiiGridView.update('item-company-category-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content">
<div class="page-header">
<h1 class="pull-left"><?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?></h1>
<?php //echo Html::a('Delete',array('user/empty'));?>
<?php echo ButtonGroup::widget([
	'buttons'=>$this->context->menu,
	'type'=>'success',
	'htmlOptions'=>['class'=> 'pull-right'],
]);
?>
<div class="clearfix"></div>
</div>
<div class="table-responsive customgridwidth">


<?php echo GridView::widget([
	'id' => 'item-company-category-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => [
		'id',
		'title',
		/* array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>ItemCompanyCategory::getTypeOptions(),
				), */
		[
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>ItemCompanyCategory::getStatusOptions(),
				],
		//'update_time',
		/* array(
			'attribute' =>'company_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->company); },
			'filter'=>Gx::listData(ItemCompany::class),
			), */
		/*
		array(
			'attribute' =>'updated_by',
			'value' => function ($data, $key, $index) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
			[
			
					'header'=>'<a>Actions</a>',
					'class' => ActionColumn::class,
					'template' => '{view}{update}', //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					'buttons'=>[
							 	'view'=>[
							 'visible' => function ($data) { return Access::check("itemCompanyCategory/view")=="true"; },
									'url' => function ($data) { return Ui::to("itemCompanyCategory/view", ["id" => $data->id]); },
									'label'=>'View',
									'options'=>['class'=>'view'],
										
							], 
							'update'=>[
									'visible' => function ($data) { return Access::check("itemCompanyCategory/update")=="true"; },
									'url' => function ($data) { return Ui::to("itemCompanyCategory/update", ["id" => $data->id]); },
									'label'=>'Update',
									'options'=>['class'=>'update'],
			
							]
					]
			],
	],
]); ?>
</div>
</section>