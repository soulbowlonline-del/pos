<?php
/**
 * Ported from protected/views/itemDetail/view.php.
 */

use app\components\Gx;
use app\models\ItemDetail;
use app\widgets\ButtonGroup;
use app\widgets\CommentPortlet;
use app\widgets\DetailView;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
		$model->label ( 2 ) => [
				'index' 
		],
		Gx::str($model) 
];

?>
<script
		src="<?php  echo '/themes/bar'; ?>/js/jQuery.print.js"
		type="text/javascript"></script>
<section class="content">
	<div class="page-header">
		<h1 class="pull-left"><?php echo Html::encode(Gx::str($model)); ?></h1>


<?php

echo ButtonGroup::widget([
		'buttons' => $this->context->menu,
		'type' => 'success',
		'htmlOptions' => [
				'class' => 'pull-right' 
		] 
] );

?>

<div class="clearfix"></div>
<a id="print_btn" class="btn btn-success"><i class="icon-wrench icon-white"></i> Print</a>

	</div>

<?php

echo DetailView::widget([
		'data' => $model,
		'attributes' => [
				'id',
				[
						'attribute' => 'item',
						'format' => 'raw',
						'value' => $model->item !== null ? Html::a( Html::encode( Gx::str($model->item) ), Gx::url([
								'item/view',
								'id' => Gx::pk($model->item) 
						])) : null 
				],
			//	'bar_code',
				[
						'attribute' => 'bar_code',
						'format' => 'raw',
						'value' => ItemDetail::getItemBarcode(["itemId"=> $model->id, "barocde"=>$model->bar_code])
				],
				
				'open_stock_qty',
				'reorder_qty',
				[
						'attribute' => 'status',
						'format' => 'raw',
						'value' => $model->getStatusOptions ( $model->status ) 
				],
				[
						'attribute' => 'type_id',
						'format' => 'raw',
						'value' => $model->getTypeOptions ( $model->type_id ) 
				],
				
				[
						'attribute' => 'tax',
						'format' => 'raw',
						'value' => $model->tax !== null ? Html::a( Html::encode( Gx::str($model->tax) ), Gx::url([
								'tax/view',
								'id' => Gx::pk($model->tax) 
						])) : null 
				] 
		]
		 
] );
?>

<?php
$this->context->StartPanel();
?>
<?php  //$this->context->AddPanel($model->getRelationLabel('itemDiscounts'), $model->getRelatedDataProvider('itemDiscounts'),	'itemDiscounts','itemDiscount');?>
<?php  //$this->context->AddPanel($model->getRelationLabel('itemStocks'), $model->getRelatedDataProvider('itemStocks'),	'itemStocks','itemStock');?>
<?php  //$this->context->AddPanel($model->getRelationLabel('itemTaxes'), $model->getRelatedDataProvider('itemTaxes'),	'itemTaxes','itemTax');?>
<?php  //$this->context->AddPanel($model->getRelationLabel('itemVendors'), $model->getRelatedDataProvider('itemVendors'),	'itemVendors','itemVendor');?>
<?php  //$this->context->AddPanel($model->getRelationLabel('mrnDetails'), $model->getRelatedDataProvider('mrnDetails'),	'mrnDetails','mrnDetail');?>
<?php  //$this->context->AddPanel($model->getRelationLabel('mrsDetails'), $model->getRelatedDataProvider('mrsDetails'),	'mrsDetails','mrsDetail');?>
<?php  //$this->context->AddPanel($model->getRelationLabel('orderHoldItems'), $model->getRelatedDataProvider('orderHoldItems'),	'orderHoldItems','orderHoldItem');?>
<?php  //$this->context->AddPanel($model->getRelationLabel('orderItems'), $model->getRelatedDataProvider('orderItems'),	'orderItems','orderItem');?>
<?php  //$this->context->AddPanel($model->getRelationLabel('orderRefundItems'), $model->getRelatedDataProvider('orderRefundItems'),	'orderRefundItems','orderRefundItem');?>
<?php  //$this->context->AddPanel($model->getRelationLabel('purchaseBillDetails'), $model->getRelatedDataProvider('purchaseBillDetails'),	'purchaseBillDetails','purchaseBillDetail');?>
<?php  //$this->context->AddPanel($model->getRelationLabel('purchaseOrderDetails'), $model->getRelatedDataProvider('purchaseOrderDetails'),	'purchaseOrderDetails','purchaseOrderDetail');?>
<?php  $this->context->EndPanel(); ?>
</section>

<?php

echo CommentPortlet::widget([
		'model' => $model 
] );
?>
<script>
$("#print_btn").click(function () {
    $("#12_bcode").print();
});
</script>