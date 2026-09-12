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
	$.fn.yiiGridView.update('order-grid', {
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
	input[type=checkbox] {
    margin: -6px 0 0;
    margin-top: 1px \9;
    line-height: normal;
	}
</style>
<ul class="nav nav-pills" id="yw2">
	<li><button type="button" class="btn btn-info export-btn" data-toggle="modal"
			data-target="#myModal">Export</button></li>
		
		
</ul>
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
							'action' => Yii::app ()->createUrl ( 'item/scannedItems?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => array (
									'enctype' => 'multipart/form-data' 
							) 
					) );
					?>
<?php

				
					$cols = array (
							'username' => 'Username',
							'user_email' => 'User Email',
							'computer_name' => 'Computer Name',
							'item' => 'Item',
							'bar_code' => 'Bar Code',
							'quantity' => 'Quantity',
							'sale_rate' => 'Sale Rate',
							'base_price' => 'Base Price',
							'mrp' => 'MRP',
							'created_at' => 'Created At',
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
<section class="content-header">

	<h1><?php echo Yii::t('app', 'Manage') . ' : ' . GxHtml::encode($model->label(2)); ?></h1>
</section>

<section class="content">
	<div class="">
		<div class="col-md-12 col-xs-12">
			<div class="box">

				</br>
				<div class="">
					<?php $form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
						'id' => 'stock-adjust-log-form',
						'type' => 'horizontal',
						'enableAjaxValidation' => true,
						'htmlOptions' => array('enctype' => 'multipart/form-data'),
					));
					?>
					<?php echo $form->datepickerRow(
						$model,
						'start_date',
						array(
							'hint' => 'Click inside! to select a date.',
							'prepend' => '<i class="icon-calendar"></i>',
							'options' => array('format' => 'yyyy-mm-dd')
						)
					); ?>

					<?php echo $form->datepickerRow(
						$model,
						'end_date',
						array(
							'hint' => 'Click inside! to select a date.',
							'prepend' => '<i class="icon-calendar"></i>',
							'options' => array('format' => 'yyyy-mm-dd')
						)
					)

					; ?>
				</div>

				<div class="form-actions" style="padding: 13px;">
					<?php $this->widget('bootstrap.widgets.TbButton', array(
						'buttonType' => 'submit',
						'type' => 'primary',
						'label' => 'Search',
					)); ?>
				</div>

				<?php $this->endWidget(); ?>

				<?php

				// $model->scannedItemsearch();
				if (isset(Yii::app()->session['gross_total'])) {
					$gross_total = Yii::app()->session['gross_total'];

				} else {
					$gross_total = 0;
				}

				if (isset(Yii::app()->session['gross_total_amt'])) {
					$gross_total_amt = Yii::app()->session['gross_total_amt'];

				} else {
					$gross_total_amt = 0;
				}
				?>
				<div class="col-md-12">
					<div class="table-responsive customgridwidth">

						<?php $this->widget('bootstrap.widgets.TbGridView', array(
							'id' => 'order-grid',
							'type' => 'striped bordered condensed',
							'dataProvider' => $model->scannedItemsearch(),
							'pager' => true,
							'filter' => $model,
							'columns' => array(
								//'id',
						
								array(
									'name' => 'user_id',
									'header' => '<a>Username</a>',
									'value' => 'isset($data->createUser)?$data->createUser:""',
									'filter'=>User::getAllUserOptions(),
								),
								array(
									'header' => '<a>User Email</a>',
									'value' => '$data->user_email',
								),
								'computer_name',
								array(
									'header' => '<a>Item</a>',
									'value' => 'isset($data->getItemDetail)?$data->getItemDetail ? $data->getItemDetail->item : "":""',
								),
								'bar_code',
								array(
									'header' => '<a>Quantity</a>',
									'value' => '$data->qty',
								),
								array(
									'header' => '<a>Sale Rate</a>',
									'value' => '$data->sale_rate',
								),
								array(
									'header' => '<a>Base Price</a>',
									'value' => '$data->base_price',
								),
								array(
									'header' => '<a>MRP</a>',
									'value' => '$data->mrp',
								),
								'created_at'
							),
						)); ?>

					</div>
				</div>
			</div>
		</div>
	</div>
</section>