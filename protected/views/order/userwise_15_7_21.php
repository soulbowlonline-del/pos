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
</style>

<section class="content-header">

	<h1><?php echo Yii::t('app', 'Manage') . ' : ' . GxHtml::encode($model->label(2)); ?></h1>
</section>

<?php    /* $this->widget('bootstrap.widgets.TbMenu', array(
       'type' => 'pills',
       'stacked' => false,
       'items' => array(
        		array('label' => 'Export',
        				'url' => array('order/admin' ,'exportCSV'=>'1',
        						
        		
        		),
       		
       		),
       ),
   )); */  ?>
   
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
					
					$form = $this->beginWidget ( 'bootstrap.widgets.TbActiveForm', array (
							'id' => 'customer-export-form',
							'type' => 'horizontal',
							'action' => Yii::app ()->createUrl ( 'order/userWiseExport?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => array (
									'enctype' => 'multipart/form-data' 
							) 
					) );
					?>
<?php

				
					$cols = array (
							'username' => 'Username',
							'amount' => 'Net Amount'
				
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
  <div class="">
    <div class="col-md-12 col-xs-12">
      <div class="box">
         <div class="box-header"><h3 class="box-title"><?php echo  GxHtml::encode($model->label(2));?></h3></div>
        <div class="box-body">
        <a target = "_blank" href="<?php echo Yii::app()->createUrl('order/userwisePdf');?>" class="btn btn-warning pull-right">
		Pdf</a>
		</br>
          <div class="">
               <?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'stock-adjust-log-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>


<?php echo $form->datepickerRow($model, 'start_date',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
						'options'=>array('format'=>'yyyy-mm-dd')))

; ?>

<?php echo $form->datepickerRow($model, 'end_date',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
								'options'=>array('format'=>'yyyy-mm-dd')))

; ?>
<?php /*?>
<div class="form-group">
<label class="control-label col-md-3">
Item
</label>
<div class="col-md-9">
<?php echo CHtml::activeListBox($model, 'item_id',Item::getActiveItems(), array('class'=>'chosen', 'multiple'=>true, 'data-placeholder'=>'Select Item')) ?>
</div>
</div>	*/?>


<?php Yii::import('application.extensions.widgets.yii-chosen.EChosenWidget');
   
?>
 <?php $this->widget('EChosenWidget',array(
    // the select selector
    'selector'=>'.chosen',
    // Chosen options
));?>

	<div class="form-actions">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		)); ?>
	</div>

<?php $this->endWidget(); ?>

<?php 

$model->userwisesearch();
if(isset(Yii::app ()->session ['gross_total'])){
	$gross_total = Yii::app ()->session ['gross_total'];
	
}else{
	$gross_total = 0;
}

if(isset(Yii::app ()->session ['gross_total_amt'])){
	$gross_total_amt = Yii::app ()->session ['gross_total_amt'];

}else{
	$gross_total_amt = 0;
}?>
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
 
<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'order-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->userwisesearch(),
	'filter' => $model,
	'columns' => array(
		//'id',
		
			array(
					'header' => '<a>Username</a>',
					'value'=>'isset($data->createUser)?$data->createUser:""',
					//'filter'=>Order::getStatusOptions(),
			),
			array(
					'header' => '<a>Taxable Amount</a>',
					'value'=>'$data->getTotalGrossAmount()',
				//	'footer'=>$gross_total
					//'filter'=>Order::getStatusOptions(),
			),
				
			/*array(
					'header' => '<a>Gross Amount old</a>',
					'value'=>'$data->getTotalNetAmount()',
				//	'footer'=>$gross_total_amt
					//'filter'=>Order::getStatusOptions(),
			),*/
			
			
			array(
					'header' => '<a>Gross Amount</a>',
					'value'=>'$data->getTotalGrossAmountData()',
				
			),
			
			array(
					'header' => '<a>Net Amount</a>',
					'value'=>'$data->getTotalNetAmountData()',
				
			),
			
			
			
			
			
		//'qty',
		/*
		'discount_amt',
		'total_amt',
		'paid_amt',
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>Order::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>Order::getTypeOptions(),
				),
		'city_id',
		array(
				'name' => 'state_id',
				'value'=>'$data->getStatusOptions($data->state_id)',
				'filter'=>Order::getStatusOptions(),
				),
		'country_id',
		'outlet_id',
		'address:html',
		'note:html',
		'update_time',
		'customer_id',
		'updated_by',
		*/
		/* array(
			'class'=>'bootstrap.widgets.TbButtonColumn',
			'htmlOptions' => array('nowrap'=>'nowrap'),
		), */
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