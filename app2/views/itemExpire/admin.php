<?php
/**
 * Ported from protected/views/itemExpire/admin.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\ItemExpire;
use app\models\Outlet;
use app\models\User;
use app\models\Vendor;
use app\widgets\ActionColumn;
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
	$.fn.yiiGridView.update('item-expire-grid', {
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
							'action' => Ui::to( 'itemExpire/admin?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => [
									'enctype' => 'multipart/form-data' 
							] 
					] );
					?>
<?php

				
					$cols = [
							'vendor_id' => 'Vendor',
							'outlet_id' => 'Outlet',
							'amount' => 'Amount',
							'date' => 'Date'
							
				
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
	'id' => 'item-expire-grid',
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
					'attribute' =>'vendor_id',
					'value' => function ($data) { return Gx::str($data->vendor); },
					'filter'=>Gx::listData(Vendor::class),
			],
			[
					'attribute' =>'outlet_id',
					'value' => function ($data) { return Gx::str($data->outlet); },
					'filter'=>Gx::listData(Outlet::class),
			],
			[
					'attribute' =>'total_amt',
					'value' => function ($data) { return $data->total_amt; },
					'footer'=>$model->getTotals($model->search()->getKeys(),'total_amt','tbl_item_expire'),
			],
			[
					'header' => '<a>Create Time</a>',
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
			
					'header'=>'<a>Action</a>',
					'class' => ActionColumn::class,
					'template' => '{view}', //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					'buttons'=>[
			
							'view'=>[
										
									'url' => function ($data) { return Ui::to("itemExpire/view", ["id" => $data->id]); },
									'label'=>'View',
									'options'=>['class'=>'update'],
			
							]
					]
			],
		/*
		'qty',
		'total_amt',
		'vendor_id',
		array(
			'attribute' =>'outlet_id',
			'value' => function ($data) { return Gx::str($data->outlet); },
			'filter'=>Gx::listData(Outlet::class),
			),
		array(
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>ItemExpire::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>ItemExpire::getTypeOptions(),
				),
		array(
			'attribute' =>'updated_by',
			'value' => function ($data) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
		/* array(
			'class' => ActionColumn::class,
			'htmlOptions' => array('nowrap'=>'nowrap'),
		), */
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