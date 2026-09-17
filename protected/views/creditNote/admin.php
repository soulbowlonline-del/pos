<?php

$this->breadcrumbs = array(
	$model->label(2) => array('index'),
	Yii::t('app', 'Manage'),
);


Yii::app()->clientScript->registerScript('search', "
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
	<h1><?php echo Yii::t('app', 'Manage') . ' : ' . GxHtml::encode($model->label(2)); ?></h1>
<?php
if($create == true){
$this->widget ( 'bootstrap.widgets.TbButtonGroup', array (
		'buttons' => $this->menu,
		'type' => 'success',
		'htmlOptions' => array (
				'class' => 'pull-right margin10' 
		) 
) );
}
?>

</section>
<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">
				<div class="box-header">
					<h3 class="box-title"><?php echo  GxHtml::encode($model->label(2));?></h3>
				</div>
				<div class="box-body">
					<div class="row">
						<div class="col-md-12">
							<div class="table-responsive customgridwidth">
							
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'credit-note-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
		'pager'=>true,
	'filter' => $model,
	'columns' => array(
		'id',
		'credit_number',
		'amt',
		'amt_used',
		/* array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>CreditNote::getTypeOptions(),
				),
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>CreditNote::getStatusOptions(),
				), */
		/*
		'update_time',
		*/
			array (
					'visible'=> $create == true,
					'header' => 'Actions',
					'class'=>'FaButtonColumn',
					'template' => '{view}{update}{delete}'
			),
	),
)); ?>

							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>