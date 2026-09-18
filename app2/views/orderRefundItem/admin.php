<?php
/**
 * Ported from protected/views/orderRefundItem/admin.php.
 */

use Yii;
use app\components\Gx;
use app\components\Ui;
use app\models\ItemDetail;
use app\models\OrderRefundItem;
use app\models\User;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\CJuiDatePicker;
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
	$.fn.yiiGridView.update('order-refund-item-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content-header">
	<h1><?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?></h1>
	<ul class="nav nav-pills" id="yw2">
	<li><button type="button" class="btn btn-info export-btn"
			data-toggle="modal" data-target="#myModal">Export</button></li>
</ul>
</section>



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
							'action' => Ui::to( 'orderRefundItem/admin?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => [
									'enctype' => 'multipart/form-data' 
							] 
					] );
					?>
<?php

					$cols = [
							'bill_no' => 'Bill No',
							'item_id' => 'Item',
							'item_detail_id' => 'Barcode',
							'qty' => 'Quantity',
							'price' => 'Price',
							'discount_amt' => 'Discounted Amount',
							'tax_amt' => 'Tax Amount',
							'total_amt' => 'Total Amount'
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
	<?php $model->search();
	
	if(isset(Yii::$app->session ['refund_total'])){
		$refund_total = Yii::$app->session ['refund_total'];
						}else{
							$refund_total = 0;
						}?>
						<div class="col-md-12">
							<div class="table-responsive customsmallgridwidth">
<?php echo GridView::widget([
	'id' => 'order-refund-item-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
		'pager'=>true,
		'afterAjaxUpdate'=>"function(){
                                                       $.datepicker.setDefaults($.datepicker.regional['en']);
                                                        $('#Projects_projStart').datepicker({'dateFormat': 'yy-mm-dd'});
		
                                                }",
	'columns' => [
		//'id',
			[
					'header'=>'Bill No',
					'attribute' =>'bill_no',
					'value' => function ($data) { return $data->getOrderBillNo(); },
				
			],
			[
					'header'=>'Refund No',
					'attribute' =>'id',
					'value' => function ($data) { return $data->getOrderRefundNo(); },
				
			],
			
			[
					'header' => 'Item',
					'attribute' =>'item_id',
					'value' => function ($data) { return $data->getItemName(); },
					'filterHtmlOptions'=>['class'=>'item_detail_bar_code'],
			]
			,
		[
			'attribute' =>'item_detail_id',
			'value' => function ($data) { return Gx::str($data->itemDetail); },
			'filter'=>Gx::listData(ItemDetail::class),
			],
			[
					'attribute' =>'qty',
					'value' => function ($data) { return $data->qty; },
					'footer'=>$model->getTotals($model->search()->getKeys(),'qty','tbl_order_refund_item'),
			],
			[
					'attribute' =>'price',
					'value' => function ($data) { return $data->price; },
					'footer'=>$model->getTotals($model->search()->getKeys(),'price','tbl_order_refund_item'),
			],
			[
					'attribute' =>'discount_amt',
					'value' => function ($data) { return $data->discount_amt; },
					'footer'=>$model->getTotals($model->search()->getKeys(),'discount_amt','tbl_order_refund_item'),
			],
			[
					'attribute' =>'tax_amt',
					'value' => function ($data) { return $data->tax_amt; },
					'footer'=>$model->getTotals($model->search()->getKeys(),'tax_amt','tbl_order_refund_item'),
			],
		//'qty',
		/* 'price',
			'discount_amt',
			'tax_amt', */
			[
					'header' => 'Total Amt',
					'attribute' =>'total_amt',
					'value' => function ($data) { return $data->total_amt; },
					'footer'=>$refund_total
			
			]
			,
			[
					'header' => '<a>Refund Date</a>',
					'attribute' => 'create_time',
					'value' => function ($data) { return date("Y-m-d",strtotime($data->create_time)); },
					'filter' => CJuiDatePicker::widget([
									'model' => $model,
									'attribute' => 'create_time',
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
			[
					'header' => 'Order Date',
					// 'attribute' =>'total_amt',
					'value' => function ($data) { return $data->getOrderDate(); }
					
			
			],
		/*
		'discount_amt',
		'tax_id',
		'tax_amt',
		'order_discount',
		array(
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>OrderRefundItem::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>OrderRefundItem::getTypeOptions(),
				),
		'update_time',
		array(
			'attribute' =>'updated_by',
			'value' => function ($data) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
		
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
$('#form-export').click(function(){
	$('#close_modal').trigger('click');
});
</script>