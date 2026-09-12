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
	$.fn.yiiGridView.update('purchase-bill-grid', {
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
<section class="content-header">

	<h1><?php echo 'Purchase Orders'; ?></h1>
</section>


<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
         <div class="box-header"><h3 class="box-title"><?php echo  'GRN List'; ?></h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
  


<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'purchase-bill-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search($val = true),
	'filter' => $model,
		'pager'=>true,
		'afterAjaxUpdate'=>"function(){
                                                       $.datepicker.setDefaults($.datepicker.regional['en']);
                                                        $('#Projects_projStart').datepicker({'dateFormat': 'yy-mm-dd'});
		
                                                }",
	'columns' => array(
			'id',
		
			array('name'=>'start_date',
					'value'=>'$data->start_date',
					'filter' => $this->widget('zii.widgets.jui.CJuiDatePicker',
							array(
									'model' => $model,
									'attribute' => 'start_date',
									'language' => 'en',
									'htmlOptions' => array(
											'id' => 'Projects_projStart',
											'dateFormat' => 'yy-mm-dd',
									),
									'options' => array(  // (#3)
											'showOn' => 'focus',
											'dateFormat' => 'yy-mm-dd',
											'showOtherMonths' => true,
											'selectOtherMonths' => false,
											'changeMonth' => false,
											'changeYear' => false,
									)
							),
							true),
								
),
			'end_date',
			/* array(
					'name' => 'total_amount',
					'value'=>'isset($data->total_amount)?$data->total_amount:""',
			
			), */
			
		/* 	array(
					'name'=>'outlet_id',
					'value'=>'GxHtml::valueEx($data->outlet)',
					'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			), */
			'gross_amt',
			'total_discount',
			'tax_amount',
			'bill_amount',
			array(
						
					'header'=>'<a>Status</a>',
					'class'=>'CButtonColumn',
					'template' => '{pdf}',  //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
							'pdf'=>array(
								//	'visible'=>'$data->state_id=='.User::STATUS_INACTIVE,
									'url' =>'Yii::app()->controller->createUrl("purchaseOrder/printPdf", array("id" => $data->id))',
									'label'=>'pdf',
									'options'=>array('class'=>'pdf'),
			
							),
							
					)
			),
			array(
			
					'header'=>'<a>Status</a>',
					'class'=>'CButtonColumn',
					'template' => '{Bill}',  //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
							'Bill'=>array(
									//	'visible'=>'$data->state_id=='.User::STATUS_INACTIVE,
									'url' =>'Yii::app()->controller->createUrl("bill/create", array("id" => $data->id))',
									'label'=>'Bill',
									'options'=>array('class'=>'bill'),
										
							),
								
					)
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
<script>

$("#purchase-bill-grid").tooltip("disable");

</script>