<?php
$this->breadcrumbs = array (
		$model->label ( 2 ) => array (
				'index' 
		),
		GxHtml::valueEx ( $model ) 
);

?>
<script
		src="<?php  echo Yii::app()->theme->baseUrl; ?>/js/jQuery.print.js"
		type="text/javascript"></script>
<section class="content">
	<div class="page-header">
		<h1 class="pull-left"><?php echo GxHtml::encode(GxHtml::valueEx($model)); ?></h1>


<?php

$this->widget ( 'bootstrap.widgets.TbButtonGroup', array (
		'buttons' => $this->menu,
		'type' => 'success',
		'htmlOptions' => array (
				'class' => 'pull-right' 
		) 
) );

?>

<div class="clearfix"></div>
<a id="print_btn" class="btn btn-success"><i class="icon-wrench icon-white"></i> Print</a>

	</div>

<?php

$this->widget ( 'bootstrap.widgets.TbDetailView', array (
		'data' => $model,
		'attributes' => array (
				'id',
				array (
						'name' => 'item',
						'type' => 'raw',
						'value' => $model->item !== null ? GxHtml::link ( GxHtml::encode ( GxHtml::valueEx ( $model->item ) ), array (
								'item/view',
								'id' => GxActiveRecord::extractPkValue ( $model->item, true ) 
						) ) : null 
				),
			//	'bar_code',
				array(
						'name' => 'bar_code',
						'type'=>'raw',
						'value' => ItemDetail::getItemBarcode(array("itemId"=> $model->id, "barocde"=>$model->bar_code))
				),
				
				'open_stock_qty',
				'reorder_qty',
				array (
						'name' => 'status',
						'type' => 'raw',
						'value' => $model->getStatusOptions ( $model->status ) 
				),
				array (
						'name' => 'type_id',
						'type' => 'raw',
						'value' => $model->getTypeOptions ( $model->type_id ) 
				),
				
				array (
						'name' => 'tax',
						'type' => 'raw',
						'value' => $model->tax !== null ? GxHtml::link ( GxHtml::encode ( GxHtml::valueEx ( $model->tax ) ), array (
								'tax/view',
								'id' => GxActiveRecord::extractPkValue ( $model->tax, true ) 
						) ) : null 
				) 
		)
		 
) );
?>

<?php
$this->StartPanel ();
?>
<?php  //$this->AddPanel($model->getRelationLabel('itemDiscounts'), $model->getRelatedDataProvider('itemDiscounts'),	'itemDiscounts','itemDiscount');?>
<?php  //$this->AddPanel($model->getRelationLabel('itemStocks'), $model->getRelatedDataProvider('itemStocks'),	'itemStocks','itemStock');?>
<?php  //$this->AddPanel($model->getRelationLabel('itemTaxes'), $model->getRelatedDataProvider('itemTaxes'),	'itemTaxes','itemTax');?>
<?php  //$this->AddPanel($model->getRelationLabel('itemVendors'), $model->getRelatedDataProvider('itemVendors'),	'itemVendors','itemVendor');?>
<?php  //$this->AddPanel($model->getRelationLabel('mrnDetails'), $model->getRelatedDataProvider('mrnDetails'),	'mrnDetails','mrnDetail');?>
<?php  //$this->AddPanel($model->getRelationLabel('mrsDetails'), $model->getRelatedDataProvider('mrsDetails'),	'mrsDetails','mrsDetail');?>
<?php  //$this->AddPanel($model->getRelationLabel('orderHoldItems'), $model->getRelatedDataProvider('orderHoldItems'),	'orderHoldItems','orderHoldItem');?>
<?php  //$this->AddPanel($model->getRelationLabel('orderItems'), $model->getRelatedDataProvider('orderItems'),	'orderItems','orderItem');?>
<?php  //$this->AddPanel($model->getRelationLabel('orderRefundItems'), $model->getRelatedDataProvider('orderRefundItems'),	'orderRefundItems','orderRefundItem');?>
<?php  //$this->AddPanel($model->getRelationLabel('purchaseBillDetails'), $model->getRelatedDataProvider('purchaseBillDetails'),	'purchaseBillDetails','purchaseBillDetail');?>
<?php  //$this->AddPanel($model->getRelationLabel('purchaseOrderDetails'), $model->getRelatedDataProvider('purchaseOrderDetails'),	'purchaseOrderDetails','purchaseOrderDetail');?>
<?php  $this->EndPanel(); ?>
</section>

<?php

$this->widget ( 'CommentPortlet', array (
		'model' => $model 
) );
?>
<script>
$("#print_btn").click(function () {
    $("#12_bcode").print();
});
</script>