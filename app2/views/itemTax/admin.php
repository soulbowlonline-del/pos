<?php
/**
 * Ported from protected/views/itemTax/admin.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Item;
use app\models\ItemDetail;
use app\models\Tax;
use app\widgets\ActiveForm;
use app\widgets\Button;
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
	$.fn.yiiGridView.update('item-tax-grid', {
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

	<h1><?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?></h1>
</section>

<ul class="nav nav-pills" id="yw2">
	<li><button type="button" class="btn btn-info export-btn" data-toggle="modal"
			data-target="#myModal">Export</button></li>
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
							'action' => Ui::to( 'itemTax/admin?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => [
									'enctype' => 'multipart/form-data' 
							] 
					] );
					?>
<?php

				
					$cols = [
							'bar_code' => 'Bar Code',
							'item' => 'Item',
							'sale_rate' => 'Sale Rate',
							'tax' => 'Tax',
							'hrn_code' => 'Total Tax(%age)',
							'cgst_per' => 'CGST(%age)',
							'cgst_amt' => 'CGST Amount',
							'sgst_per' => 'SGST(%age)',
							'sgst_amt' => 'SGST Amount',
							//'igst_per' => 'IGST(%age)',
							//'igst_amt' => 'IGST Amount',
						    'cess_per' => 'CESS(%age)',
							'cess_amt' => 'CESS Amount',
						
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
         <div class="box-header"><h3 class="box-title"><?php echo  Html::encode($model->label(2));?></h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
 
<?php echo GridView::widget([
	'id' => 'item-tax-grid',
		'pager' => true,
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => [
		//'id',
			[
					'header' => '<a>Bar Code</a>',
					'attribute' =>'item_detail_id',
					'value' => function ($data) { return isset($data->itemDetail)?$data->itemDetail->bar_code:""; },
					//'filter'=>Gx::listData(ItemDetail::class),
				
			],
			[
					'header' => '<a>Item</a>',
					'attribute' =>'item_id',
					'value' => function ($data) { return $data->getItemName(); },
				//	'filter'=>Gx::listData(Item::class),
			
			],
			[
					'header' => '<a>Sale Rate</a>',
					'attribute' =>'sale_rate',
					'value' => function ($data) { return $data->getSaleRate(); },
			
			],
			[
					'header' => '<a>Tax</a>',
					'attribute' =>'tax_id',
					'value' => function ($data) { return isset($data->tax)?$data->tax->title:""; },
					'filter'=>Gx::listData(Tax::class),
			
			],
			[
					'header' => '<a>Total Tax(%age)</a>',
					'attribute' =>'match_total_tax',
					'value' => function ($data) { return isset($data->tax)?$data->tax->hrn_code:""; },
						
			],
			[
					'header' => '<a>CGST (%age)</a>',
					'attribute' =>'match_cgst',
					'value' => function ($data) { return isset($data->tax)?$data->tax->tax_val1:""; },
			
			],
			[
					'header' => '<a>CGST Amount</a>',
					'value' => function ($data) { return $data->getCgstAmt(); },
						
			],
			[
					'header' => '<a>SGST (%age)</a>',
					'attribute' =>'match_sgst_tax',
					'value' => function ($data) { return isset($data->tax)?$data->tax->tax_val2:""; },
			
			],
			[
					'header' => '<a>SGST Amount</a>',
					'value' => function ($data) { return $data->getSgstAmt(); },
			
			],
			
			/* array(
					'header' => '<a>IGST (%age)</a>',
					'attribute' =>'match_cgst',
					'value' => function ($data) { return isset($data->tax)?$data->tax->tax_val4:""; },
			
			),
			array(
					'header' => '<a>IGST Amount</a>',
					'value' => function ($data) { return $data->getIgstAmt(); },
						
			),*/
			
			[
					'header' => '<a>CESS (%age)</a>',
					'attribute' =>'match_cess_tax',
					'value' => function ($data) { return isset($data->tax)?$data->tax->tax_val3:""; },
			
			],
			[
					'header' => '<a>CESS Amount</a>',
					'value' => function ($data) { return $data->getCessAmt(); },
			
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
$('#form-export').click(function(){
	$('#close_modal').trigger('click');
});
</script>