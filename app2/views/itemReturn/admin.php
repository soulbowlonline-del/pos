<?php
/**
 * Ported from protected/views/itemReturn/admin.php.
 */

use app\components\Access;
use app\components\Gx;
use app\components\Ui;
use app\models\ItemReturn;
use app\models\Outlet;
use app\models\Vendor;
use app\widgets\ActionColumn;
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
	$.fn.yiiGridView.update('item-return-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content-header">
<h1><?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?></h1>
<?php //echo Html::a('Delete',array('user/empty'));?>
<ul class="nav nav-pills" id="yw2">
	<li><button type="button" class="btn btn-info export-btn" data-toggle="modal"
			data-target="#myModal">Export</button></li>
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
							'action' => Ui::to( 'itemReturn/admin?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => [
									'enctype' => 'multipart/form-data' 
							] 
					] );
					?>
<?php


					$cols = [
							'gross_amt' => 'Gross Amount',
							'tax_amt' => 'Tax Amount',
							'discount_amt' => 'Discount Amount',
							'total_amt' => 'Total Amount',
							'vendor_id' => 'Vendor',
							'outlet_id' => 'Outlet',
							'credit_note_id' => 'Credit Note'
				
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
	'id' => 'item-return-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
		'pager'=>true,
	'columns' => [
		//'id',
			
			[
					'attribute' =>'gross_amt',
					'value' => function ($data, $key, $index) { return $data->gross_amt; },
					'footer'=>$model->getTotals($model->search()->getKeys(),'gross_amt','tbl_item_return'),
			],
			[
					'attribute' =>'tax_amt',
					'value' => function ($data, $key, $index) { return $data->tax_amt; },
					'footer'=>$model->getTotals($model->search()->getKeys(),'tax_amt','tbl_item_return'),
			],
			[
					'attribute' =>'discount_amt',
					'value' => function ($data, $key, $index) { return $data->discount_amt; },
					'footer'=>$model->getTotals($model->search()->getKeys(),'discount_amt','tbl_item_return'),
			],
			[
					'attribute' =>'total_amt',
					'value' => function ($data, $key, $index) { return $data->total_amt; },
					'footer'=>$model->getTotals($model->search()->getKeys(),'total_amt','tbl_item_return'),
			],
				
			[
					'attribute' =>'vendor_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->vendor); },
					'filter'=>Gx::listData(Vendor::class),
			],
			[
					'attribute' =>'outlet_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->outlet); },
					'filter'=>Gx::listData(Outlet::class),
			],
			[
					'attribute' =>'credit_note_id',
					'value' => function ($data, $key, $index) { return $data->credit_note_no; },
					'filter' => false
				//	'filter'=>Gx::listData(Outlet::class),
			],
			[
				'attribute' =>'bill_date',
				'value' => function ($data, $key, $index) { return $data->credit_note_date; },
				'filter' => false
			//	'filter'=>Gx::listData(Outlet::class),
		],
		[
			'attribute' =>'save_date',
			// 'value' => function ($data, $key, $index) { return $data->grn_save_date; },
			'value' => function ($data, $key, $index) { return $data->create_time; },
			'filter' => false
		//	'filter'=>Gx::listData(Outlet::class),
	],
		/*
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>ItemReturn::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>ItemReturn::getTypeOptions(),
				),
		'credit_note_id',
		'updated_by',
		*/
			[
			
					'header'=>'<a>Status</a>',
					'class' => ActionColumn::class,
					'template' => '{view}{update}', //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					'buttons'=>[
							'view'=>[
								//	'visible' => Access::check('outlet/view'),
									'url' => function ($data) { return Ui::to("itemReturn/view", ["id" => $data->id]); },
									'label'=>'View',
									'options'=>['class'=>'view'],
										
							],
							'update'=>[
								//	'visible' => Access::check('outlet/view'),
									'url' => function ($data) { return Ui::to("itemReturn/update", ["id" => $data->id]); },
									'label'=>'Update',
									'options'=>['class'=>'update'],
										
							]
							
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