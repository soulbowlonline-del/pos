<?php
/**
 * Ported from protected/views/vendorSchemes/admin.php.
 */

use app\components\Gx;
use app\models\Item;
use app\models\User;
use app\models\Vendor;
use app\models\VendorSchemes;
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
	$.fn.yiiGridView.update('vendor-schemes-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content-header">
<h1><?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?>
</h1>
<?php //echo Html::a('Delete',array('user/empty'));?>
<?php echo ButtonGroup::widget([
	'buttons'=>$this->context->menu,
	'type'=>'success',
	'htmlOptions'=>['class'=> 'pull-right'],
]);
?>
</section>

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
	'id' => 'vendor-schemes-grid',
	'type'=>'striped bordered condensed',
	'pager'=>true,
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => [
		'id',
		[
			'attribute' =>'vendor_id',
			'value' => function ($data) { return Gx::str($data->vendor); },
			'filter'=>Gx::listData(Vendor::class),
			],
		[
			'header'=>'<a>Item</a>',
			'value' => function ($data) { return $data->getItems(); },
		//	'filter'=>Gx::listData(Item::class),
			],
		'total_sale',
		'start_date',
		'end_date',
		/*
		'discount',
		array(
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>VendorSchemes::getTypeOptions(),
				),
		array(
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>VendorSchemes::getStatusOptions(),
				),
		'update_time',
		array(
			'attribute' =>'updated_by',
			'value' => function ($data) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
		[
				'header'=>'Actions',
			'class' => ActionColumn::class,
			
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