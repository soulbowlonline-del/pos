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
	$.fn.yiiGridView.update('order-refund-item-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content-header">
	<h1><?php echo Yii::t('app', 'Manage') . ' : ' . GxHtml::encode($model->label(2)); ?></h1>
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
					
					$form = $this->beginWidget ( 'bootstrap.widgets.TbActiveForm', array (
							'id' => 'customer-export-form',
							'type' => 'horizontal',
							'action' => Yii::app ()->createUrl ( 'orderRefundItem/admin?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => array (
									'enctype' => 'multipart/form-data' 
							) 
					) );
					?>
<?php

					$cols = array (
							'bill_no' => 'Bill No',
							'item_id' => 'Item',
							'item_detail_id' => 'Barcode',
							'qty' => 'Quantity',
							'price' => 'Price',
							'discount_amt' => 'Discounted Amount',
							'tax_amt' => 'Tax Amount',
							'total_amt' => 'Total Amount'
					);
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
		
		$this->widget ( 'bootstrap.widgets.TbButton', array (
				'buttonType' => 'submit',
				'type' => 'primary',
				'label' => 'Export',
				'htmlOptions' => array (
						'id' => 'form-export' 
				) 
		) );
		?>
	</div>
<?php $this->endWidget(); ?>
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
					<h3 class="box-title"><?php echo  GxHtml::encode($model->label(2));?></h3>
				</div>
				<div class="box-body">
					<div class="">
	<?php $model->search();
	
	if(isset(Yii::app ()->session ['refund_total'])){
		$refund_total = Yii::app ()->session ['refund_total'];
						}else{
							$refund_total = 0;
						}?>
						<div class="col-md-12">
							<div class="table-responsive customsmallgridwidth">
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'order-refund-item-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
		'pager'=>true,
		'afterAjaxUpdate'=>"function(){
                                                       $.datepicker.setDefaults($.datepicker.regional['en']);
                                                        $('#Projects_projStart').datepicker({'dateFormat': 'yy-mm-dd'});
		
                                                }",
	'columns' => array(
		//'id',
			array(
					'header'=>'Bill No',
					'name'=>'bill_no',
					'value'=>'$data->getOrderBillNo()',
				
			),
			array(
					'header'=>'Refund No',
					'name'=>'id',
					'value'=>'$data->getOrderRefundNo()',
				
			),
			
			array (
					'header' => 'Item',
					'name'=>'item_id',
					'value' => '$data->getItemName()',
					'filterHtmlOptions'=>array('class'=>'item_detail_bar_code'),
			)
			,
		array(
			'name'=>'item_detail_id',
			'value'=>'GxHtml::valueEx($data->itemDetail)',
			'filter'=>GxHtml::listDataEx(ItemDetail::model()->findAllAttributes(null, true)),
			),
			array(
					'name'=>'qty',
					'value'=>'$data->qty',
					'footer'=>$model->getTotals($model->search()->getKeys(),'qty','tbl_order_refund_item'),
			),
			array(
					'name'=>'price',
					'value'=>'$data->price',
					'footer'=>$model->getTotals($model->search()->getKeys(),'price','tbl_order_refund_item'),
			),
			array(
					'name'=>'discount_amt',
					'value'=>'$data->discount_amt',
					'footer'=>$model->getTotals($model->search()->getKeys(),'discount_amt','tbl_order_refund_item'),
			),
			array(
					'name'=>'tax_amt',
					'value'=>'$data->tax_amt',
					'footer'=>$model->getTotals($model->search()->getKeys(),'tax_amt','tbl_order_refund_item'),
			),
		//'qty',
		/* 'price',
			'discount_amt',
			'tax_amt', */
			array (
					'header' => 'Total Amt',
					'name'=>'total_amt',
					'value' => '$data->total_amt',
					'footer'=>$refund_total
			
			)
			,
			array(
					'header' => '<a>Refund Date</a>',
					'name' => 'create_time',
					'value'=>'date("Y-m-d",strtotime($data->create_time))',
					'filter' => $this->widget('zii.widgets.jui.CJuiDatePicker',
							array(
									'model' => $model,
									'attribute' => 'create_time',
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
			array (
					'header' => 'Order Date',
					// 'name'=>'total_amt',
					'value' => '$data->getOrderDate()'
					
			
			),
		/*
		'discount_amt',
		'tax_id',
		'tax_amt',
		'order_discount',
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>OrderRefundItem::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>OrderRefundItem::getTypeOptions(),
				),
		'update_time',
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			),
		*/
		
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
$('#form-export').click(function(){
	$('#close_modal').trigger('click');
});
</script>