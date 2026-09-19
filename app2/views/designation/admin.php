<?php
/**
 * Ported from protected/views/designation/admin.php.
 */

use app\models\Designation;
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
	$.fn.yiiGridView.update('designation-grid', {
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
        				'url' => ['designation/admin' ,'exportCSV'=>'1',
        						
        		
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
	'id' => 'designation-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => [
		'id',
		'title',
		[
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>Designation::getStatusOptions(),
				],
		
		[
					'header'=>'Actions',
					'class' => ActionColumn::class,
					'template' => '{view}{update}'
			
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