<?php
/**
 * Ported from protected/views/b2bpurchaseBillDetail/texwisereport.php.
 */

use app\components\Ui;
use app\models\OrderItem;
use app\widgets\ActionColumn;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\GridView;
use app\widgets\Menu;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
		$model->label ( 2 ) => [
				'index' 
		],
		Yii::t ( 'app', 'Manage' ) 
];

$this->registerJs("
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$.fn.yiiGridView.update('order-item-grid', {
		data: $(this).serialize()
	});
	return false;
});
" );
?>


<style>
.btn-info.export-btn {
	background-color: #00c0ef;
	border-color: #00acd6;
	margin-left: 16px;
	margin-top: 10px;
}
.mb-10 {
    margin-bottom: 1rem;
}
</style>

 <?php 	/*   if(empty($model->start_date) && empty($model->end_date ))
		{
			
				Yii::$app->session['order_item_start_date'] ='';
				Yii::$app->session['order_item_end_date'] ='';
				
			
		}   */
		
	/*	if(empty($model->min_amt) && empty($model->max_amt ))
		{
		
			Yii::$app->session['order_item_min_amt'] ='';
			Yii::$app->session['order_item_max_amt'] ='';
		
		
		} */
		?> 

<section class="content-header">
	<h1>B2b Department with group tax wise report</h1>
</section>

<?php 
/*
	       * echo Menu::widget(array(
	       * 'type' => 'pills',
	       * 'stacked' => false,
	       * 'items' => array(
	       * array('label' => 'Export',
	       * 'url' => array('orderItem/admin' ,'exportCSV'=>'1',
	       *
	       *
	       * ),
	       *
	       * ),
	       * ),
	       * ));
	       */
?>

<ul class="nav nav-pills" id="yw2">
	<li><button type="button" class="btn btn-info export-btn"
			data-toggle="modal" data-target="#myModal">Export</button></li>
</ul>

<!-- Modal -->
<div id="myModal" class="modal fade" role="dialog">
	<div class="modal-dialog">

		<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Select Columns</h4>
			</div>
			<div class="modal-body">
     <?php
					
					$form = ActiveForm::begin([
							'id' => 'customer-export-form',
							'type' => 'horizontal',
							'action' => Ui::to( 'b2bpurchaseBillDetail/taxwise?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => [
									'enctype' => 'multipart/form-data' 
							] 
					] );
					?>
<?php

					$cols = [
					
			
							'bill_no' => 'Bill No',
							'bill_date' => 'Bill Date',
							
							'customer' => 'Customer',
							'taxable' => 'Taxable',
							'gst_per' => 'Tax',
							'cgst_per' => 'Cgst(%)',
							'sgst_per' => 'Sgst(%)',
							'cess_per' => 'Cess(%)',
							'igst_per' => 'Igst(%)',
							'cgst_amt' => 'Cgst Amount',
							'sgst_amt' => 'sgst Amount',
							'cess_amt' => 'cess Amount',
							'igst_amt' => 'Igst Amount',
							
							'round_amt' => ' Amount'
					];
					?>
<div class="form-group ">
					<label for="ItemStock_item_id"
						class="control-label col-md-3 required"> </label>
					<div class="col-md-9">
			<?php echo $form->checkboxListRow($model,'columns',$cols); ?>
		</div>
				</div>

				<div class="form-actions">
		<?php
		
		echo Button::widget([
				'buttonType' => 'submit',
				'type' => 'primary',
				'label' => 'Export',
				'htmlOptions' => [
						'id' => 'form-export' 
				] 
		] );
		?>
	</div>
<?php ActiveForm::end(); ?>
      </div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"
					id="close_modal">Close</button>
			</div>
		</div>

	</div>
</div>
<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">
				<div class="box-header">
					<h3 class="box-title"><?php echo  Html::encode($model->label(2));?></h3>
				</div>
				<div class="box-body">
					<div class="">
					       <?php $form = ActiveForm::begin([
	'id' => 'stock-adjust-log-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>

<div class="col-md-6">

<?php echo $form->datepickerRow($model, 'start_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
						'options'=>['format'=>'yyyy-mm-dd']])

; ?>
</div>
<div class="col-md-6">
<?php echo $form->datepickerRow($model, 'end_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
								'options'=>['format'=>'yyyy-mm-dd']])

; ?>
</div>
<div class="col-md-6">
	<div class="form-actions  mb-10">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		]); ?>
	</div>
	</div>


<?php ActiveForm::end(); ?>
						<div class="col-md-12">
							<div class="table-responsive customsmallgridwidth">
								
<?php

echo GridView::widget([
		'id' => 'order-item-grid',
		'type' => 'striped bordered condensed',
		'dataProvider' => $model->b2bTaxwisesearch (),
		'filter' => $model,
		'pager'=>true,
		'columns' => [
				// 'id',
					[
						'header' => 'Bill No',
							'attribute' =>'purchase_bill_id',
						'value' => function ($data, $key, $index) { return $data->getOrderBillNo(); } 
				],
				[
						'header' => 'Bill Date',
						'attribute' =>'bill_date',
						'value' => function ($data, $key, $index) { return $data->getOrderBillDate(); }
				],
				
				[
						'header' => 'Customer',
						// 'attribute' =>'vendor',
						'value' => function ($data, $key, $index) { return $data->getVendorName(); },
						
				]
				,
					[
						'header' => 'Taxable',
						
							'value' => function ($data, $key, $index) { return $data->price-($data->discount_amt1 + $data->discount_amt) ; }, 
				
				],
					[
						'header' => 'Tax',
						
						'value' => function ($data, $key, $index) { return $data->getTaxTitle(); },
				
				],
				[
						'header' => 'Cgst(%age)',
						'value' => function ($data, $key, $index) { return $data->cgst_per; }
				],
				[
						'header' => 'Sgst(%age)',
						'value' => function ($data, $key, $index) { return $data->sgst_per; }
				],
				[
						'header' => 'Cess(%age)',
						'value' => function ($data, $key, $index) { return $data->cess_per; }
				],
				[
						'header' => 'Igst(%age)',
						'value' => function ($data, $key, $index) { return $data->igst_per; }
				],
				
				[
						'header' => 'Cgst',
						'value' => function ($data, $key, $index) { return $data->cgst_amt; },
						
				],
				[
						'header' => 'Sgst',
						'value' => function ($data, $key, $index) { return $data->sgst_amt; },
					
				],
				[
						'header' => 'Cess',
							'value' => function ($data, $key, $index) { return $data->cess_amt; },
						
				],
				[
						'header' => 'Igst',
						'value' => function ($data, $key, $index) { return $data->igst_amt; },
						
				],
				
				
				[
						'header' => 'Amount',
						'attribute' =>'amount',
						'value' => function ($data, $key, $index) { return $data->amount; },
				
				]
				,
		/*
		'discount_amt',
		'tax_id',
		'tax_amount',
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>OrderItem::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>OrderItem::getTypeOptions(),
				),
		'update_time',
		'updated_by',
		*/
		/* array(
			'class' => ActionColumn::class,
			'htmlOptions' => array('nowrap'=>'nowrap'),
		), */
	] 
] );
?>

							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<script>
$('#form-export').click(function(){
	$('#close_modal').trigger('click');
});
</script>