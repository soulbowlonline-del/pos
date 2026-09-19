<?php
/**
 * Ported from protected/views/order/grouphsntax.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Customer;
use app\models\OrderItem;
use app\models\PaymentMode;
use app\widgets\ActionColumn;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\GridView;
use app\widgets\Menu;
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
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.7.0/css/buttons.dataTables.min.css">
<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.html5.min.js"></script>
<style>
.btn-info.export-btn {
	background-color: #00c0ef;
	border-color: #00acd6;
	margin-left: 16px;
	margin-top: 10px;
}
</style>

 

<section class="content-header">
	<h1><?php echo 'Department with group tax wise report' ; ?></h1>
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
							'action' => Ui::to( 'order/grouphsntax?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => [
									'enctype' => 'multipart/form-data' 
							] 
					] );
					?>
<?php

					$cols = [
					'item_id' => 'Item name',
					'hsn_code' => 'HSN Code',
					'taxable' => 'Taxable',
							'mode_of_payment' => 'Mode Of Payment',
							'order_id' => 'Bill Date',
							'Gst' => 'Gst',
							'Cgst_per' => 'Cgst(%age)',
							'Sgst_per' => 'Sgst(%age)',
							'Cess_per' => 'Cess(%age)',
							'Igst_per' => 'Igst(%age)',
							'Cgst' => 'Cgst',
							'Sgst' => 'Sgst',
							'Cess' => 'Cess',
							'Igst' => 'Igst',
							'Amount' => 'Amount'
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
					<h3 class="box-title"><?php echo  'Report';?></h3>
				</div>
				<div class="box-body">
					<div class="">
					<?php if(empty($model->start_date)){
						Yii::$app->session ['order_item_start_date'] = '';
					}
					if(empty($model->end_date)){
						Yii::$app->session ['order_item_end_date'] = '';
					}?>
					
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

	<div class="form-actions pull-left">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		]); ?>
	</div>

<?php ActiveForm::end(); ?>
					
						<div class="col-md-12">
						<?php $model->groupHSNTaxsearch ();?>
						
						
							<div class="table-responsive customsmallgridwidth">
							
								
<?php

echo GridView::widget([
		'id' => 'order-item-grid',
		'type' => 'striped bordered condensed',
		'dataProvider' => $model->groupHSNTaxsearch (),
		'htmlOptions' => [
        'id' => 'order-item-table',
        ],
		'filter' => $model,
		'pager'=>true,
		'columns' => [
				// 'id',
				[
						'header' => 'Item name',
						'attribute' =>'item_id',
						'value' => function ($data, $key, $index) { return isset($data->item)?$data->item->title:""; },
						'filter'=>false
				],
[
						'header' => 'HSN Code',
						'attribute' =>'item_id',
						'value' => function ($data, $key, $index) { return isset($data->item)?$data->item->hsn_code:""; }
				],
				[
						'header' => 'Bill Date',
						'attribute' =>'order_id',
						'value' => function ($data, $key, $index) { return isset($data->order)?$data->order->bill_date:""; }
				],
					[
						'header' => 'Taxable',
						'value' => function ($data, $key, $index) { return $data->getTotalHsnItemTaxableAmount(); }, 
						
				],
				[
						'header' => 'Mode of Payment',
						'attribute' =>'mode_of_payment',
						'value' => function ($data, $key, $index) { return isset($data->order)?$data->order->modePayment:""; },
						'filter'=>Gx::listData(PaymentMode::find()->where(['type_id'=>0])->orderBy(['id' => SORT_DESC])->all()),
				]
				,
				/* array (
						'header' => 'Customer',
						'attribute' =>'customer_id',
						'value' => function ($data, $key, $index) { return isset($data->order)?$data->order->customer:""; },
						'filter' => Gx::listData(Customer::class)
				)
				, */
				[
						'header' => 'Gst',
						'value' => function ($data, $key, $index) { return $data->getOrdertotalHsngstAmount(); },
						//'footer'=>$gst,
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
						'value' => function ($data, $key, $index) { return $data->getGroupHsnTaxCgstAmount(); },
						
				],
				[
						'header' => 'Sgst',
						'value' => function ($data, $key, $index) { return $data->getGroupHsnTaxSgstAmount(); },
						
				],
				[
						'header' => 'Cess',
							'value' => function ($data, $key, $index) { return $data->getGroupHsnTaxCessAmount(); },
						
				],
				[
						'header' => 'Igst',
						'value' => function ($data, $key, $index) { return $data->getGroupTaxHsnIgstAmount(); },
						
				],
				[
						'header' => 'Amount',
						'value' => function ($data, $key, $index) { return $data->getGroupHsnTaxOrderTotalAmount(); },
						
				],
				
				// 'item_detail_id',
				
				
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

$(document).ready(function() {
    $('.items').DataTable( {
		 "paging":   true,
        dom: 'Bfrtip',

       buttons: ['excel','csv'],
  exportOptions: {
    modifer: {
      page: 'all',
      search: 'none'}
  }
    } );
} );
</script>
