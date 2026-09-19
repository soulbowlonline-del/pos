<?php
/**
 * Ported from protected/views/bill/admin.php.
 */

use app\components\Gx;
use app\models\Bill;
use app\models\PurchaseOrder;
use app\widgets\ActionColumn;
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
	$.fn.yiiGridView.update('bill-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content-header">
  <h1><?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?></h1>
 
</section>



<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
         <div class="box-header"><h3 class="box-title"><?php echo Html::encode($model->label(2)); ?></h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">



<?php echo GridView::widget([
	'id' => 'bill-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => [
		//'id',
		'bill_no',
			[
					'attribute' =>'image_file1',
					'value' => function ($data, $key, $index) { return $data->getNewImage($data->image_file1); },
					'format' => 'raw',
			],
			[
					'attribute' =>'image_file2',
					'value' => function ($data, $key, $index) { return $data->getNewImage($data->image_file2); },
					'format' => 'raw',
			],
			//	'image_file1:html',
			
			[
					'attribute' =>'po_id',
					'value' => function ($data, $key, $index) { return isset($data->po)?$data->po->id:""; },
					//'filter'=>Gx::listData(PurchaseOrder::class),
			],
		//'image_file3:html',
		/* array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>Bill::getTypeOptions(),
				), */
		/*
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>Bill::getStatusOptions(),
				),
		array(
			'attribute' =>'po_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->po); },
			'filter'=>Gx::listData(PurchaseOrder::class),
			),
		*/
			[
						
					'header'=>'<a>Status</a>',
					'class' => ActionColumn::class,
					'template' => '{update}{delete}', //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					
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