<?php
/**
 * Ported from protected/views/creditNote/admin.php.
 */

use app\models\CreditNote;
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
	$.fn.yiiGridView.update('credit-note-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<style>
.btn-info.export-btn {
    background-color: #00c0ef;
    border-color: #00acd6;
    margin-left: 16px;
    margin-top: 10px;
}
</style>
<?php $create = $model->isAllowCreate();?>
<section class="content-header">
	<h1><?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?></h1>
<?php
if($create == true){
echo ButtonGroup::widget([
		'buttons' => $this->context->menu,
		'type' => 'success',
		'htmlOptions' => [
				'class' => 'pull-right margin10' 
		] 
] );
}
?>

</section>
<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">
				<div class="box-header">
					<h3 class="box-title"><?php echo  Html::encode($model->label(2));?></h3>
				</div>
				<div class="box-body">
					<div class="row">
						<div class="col-md-12">
							<div class="table-responsive customgridwidth">
							
<?php echo GridView::widget([
	'id' => 'credit-note-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
		'pager'=>true,
	'filter' => $model,
	'columns' => [
		'id',
		'credit_number',
		'amt',
		'amt_used',
		/* array(
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>CreditNote::getTypeOptions(),
				),
		array(
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>CreditNote::getStatusOptions(),
				), */
		/*
		'update_time',
		*/
			[
					'visible'=> $create == true,
					'header' => 'Actions',
					'class' => ActionColumn::class,
					'template' => '{view}{update}{delete}'
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