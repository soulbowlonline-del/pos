<?php
$this->breadcrumbs = array (
		$model->label ( 2 ) => array (
				'index'
		),
		Yii::t ( 'app', 'Manage' )
);

Yii::app ()->clientScript->registerScript ( 'search', "
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
</style>



<section class="content-header">
	<h1><?php echo Yii::t('app', 'Manage') . ' : ' . GxHtml::encode($model->label(2)); ?></h1>
</section>

<?php 
/*
	       * $this->widget('bootstrap.widgets.TbMenu', array(
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
					
					$form = $this->beginWidget ( 'bootstrap.widgets.TbActiveForm', array (
							'id' => 'customer-export-form',
							'type' => 'horizontal',
							'action' => Yii::app ()->createUrl ( 'order/tax?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => array (
									'enctype' => 'multipart/form-data' 
							) 
					) );
					?>
<?php

					$cols = array (
							'bill_no' => 'Bill No',
							'bar_code' => 'Barcode',
							'sale_rate' => 'Sale Rate',
							'item' => 'Item',
							'tax' => 'Tax',
							'hrn_code' => 'Total Tax(%age)',
							'cgst_per' => 'CGST(%age)',
							'cgst_amt' => 'CGST Amount',
							'sgst_per' => 'SGST(%age)',
							'sgst_amt' => 'SGST Amount',
							'cess_per' => 'CESS(%age)',
							'cess_amt' => 'CESS Amount',
							'tax_amount' => 'Tax Amount' 
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
					<div class="row">
						<div class="col-md-12">
							<div class="table-responsive">
								
<?php

$this->widget ( 'bootstrap.widgets.TbGridView', array (
		'id' => 'order-item-grid',
		'type' => 'striped bordered condensed',
		'dataProvider' => $model->search (),
		'filter' => $model,
		'pager'=>true,
		'columns' => array (
				// 'id',
					array (
						'header' => 'Bill No',
							'name'=>'order_id',
						'value' => 'isset($data->order)?$data->order->bill_no:""' 
				),
				array (
						'header' => 'Barcode',
						'name'=>'item_detail_id',
						'value' => 'isset($data->itemDetail)?$data->itemDetail->bar_code:""' ,
						//'filter'=>GxHtml::listDataEx(ItemDetail::model()->findAllAttributes(null, true)),
				)
				,
				array (
						'header' => 'Item',
						'name'=>'item_id',
						'value' => '$data->getItemName()' ,
						//'filter'=>GxHtml::listDataEx(Item::model()->findAllAttributes(null, true)),
				),
				array(
						'header' => '<a>Sale Rate</a>',
						'value'=>'$data->getSaleRate()',
							
				),
				array (
						'header' => 'Tax',
						'name'=>'tax_id',
						'value' => 'isset($data->tax)?$data->tax->title:""',
						'filter'=>GxHtml::listDataEx(Tax::model()->findAllAttributes(null, true)),
				),
				array (
						'header' => 'Total Tax(%age)',
						'value' => 'isset($data->tax)?$data->tax->hrn_code:""',
				),
				array(
						'header' => '<a>CGST (%age)</a>',
						'value'=>'$data->cgst_per',
							
				),
				array(
						'header' => '<a>CGST Amount</a>',
						'value'=>'$data->cgst_amt',
				
				),
				array(
						'header' => '<a>SGST (%age)</a>',
						'value'=>'$data->sgst_per',
							
				),
				array(
						'header' => '<a>SGST Amount</a>',
						'value'=>'$data->sgst_amt',
							
				),
				array(
						'header' => '<a>CESS (%age)</a>',
						'value'=>'$data->cess_per',
							
				),
				array(
						'header' => '<a>CESS Amount</a>',
						'value'=>'$data->cess_amt',
							
				),
				array (
						'header' => 'Tax Amount',
						'value' => '$data->tax_amount',
				),
				
				// 'item_detail_id',
				
				
		/*
		'discount_amt',
		'tax_id',
		'tax_amount',
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>OrderItem::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>OrderItem::getTypeOptions(),
				),
		'update_time',
		'updated_by',
		*/
		/* array(
			'class'=>'bootstrap.widgets.TbButtonColumn',
			'htmlOptions' => array('nowrap'=>'nowrap'),
		), */
	) 
) );
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