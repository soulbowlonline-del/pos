<?php
/**
 * Ported from protected/views/purchaseOrder/list.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Outlet;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\CJuiDatePicker;
use app\widgets\GridView;
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
  


<?php echo GridView::widget([
	'id' => 'purchase-bill-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search($val = true),
	'filter' => $model,
		'pager'=>true,
		'afterAjaxUpdate'=>"function(){
                                                       $.datepicker.setDefaults($.datepicker.regional['en']);
                                                        $('#Projects_projStart').datepicker({'dateFormat': 'yy-mm-dd'});
		
                                                }",
	'columns' => [
			'id',
		
			['attribute' =>'start_date',
					'value' => function ($data, $key, $index) { return $data->start_date; },
					'filter' => CJuiDatePicker::widget([
									'model' => $model,
									'attribute' => 'start_date',
									'language' => 'en',
									'htmlOptions' => [
											'id' => 'Projects_projStart',
											'dateFormat' => 'yy-mm-dd',
									],
									'options' => [  // (#3)
											'showOn' => 'focus',
											'dateFormat' => 'yy-mm-dd',
											'showOtherMonths' => true,
											'selectOtherMonths' => false,
											'changeMonth' => false,
											'changeYear' => false,
									]
							],
							true),
								
],
			'end_date',
			/* array(
					'attribute' => 'total_amount',
					'value' => function ($data, $key, $index) { return isset($data->total_amount)?$data->total_amount:""; },
			
			), */
			
		/* 	array(
					'attribute' =>'outlet_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->outlet); },
					'filter'=>Gx::listData(Outlet::class),
			), */
			'gross_amt',
			'total_discount',
			'tax_amount',
			'bill_amount',
			[
						
					'header'=>'<a>Status</a>',
					'class' => ActionColumn::class,
					'template' => '{pdf}',  //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					'buttons'=>[
							'pdf'=>[
								//	'visible'=>'$data->state_id=='.User::STATUS_INACTIVE,
									'url' => function ($data) { return Ui::to("purchaseOrder/printPdf", ["id" => $data->id]); },
									'label'=>'pdf',
									'options'=>['class'=>'pdf'],
			
							],
							
					]
			],
			[
			
					'header'=>'<a>Status</a>',
					'class' => ActionColumn::class,
					'template' => '{Bill}',  //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					'buttons'=>[
							'Bill'=>[
									//	'visible'=>'$data->state_id=='.User::STATUS_INACTIVE,
									'url' => function ($data) { return Ui::to("bill/create", ["id" => $data->id]); },
									'label'=>'Bill',
									'options'=>['class'=>'bill'],
										
							],
								
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
<script>

$("#purchase-bill-grid").tooltip("disable");

</script>