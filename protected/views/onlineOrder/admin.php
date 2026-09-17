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
	$.fn.yiiGridView.update('online-order-grid', {
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
<?php 	if(empty($model->start_date) && empty($model->end_date ))
		{
			
				Yii::app()->session['onlineorder_start_date'] ='';
				Yii::app()->session['onlineorder_end_date'] ='';
				
			
		}
		?>
<section class="content-header">
<h1><?php echo Yii::t('app', 'Manage') . ' : ' . GxHtml::encode($model->label(2)); ?></h1>
<?php //echo CHtml::link('Delete',array('user/empty'));?>
<?php $this->widget('bootstrap.widgets.TbButtonGroup', array(
	'buttons'=>$this->menu,
	'type'=>'success',
	'htmlOptions'=>array('class'=> 'pull-right'),
));
?>
</section>

<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
         <div class="box-header"><h3 class="box-title"><?php echo  GxHtml::encode($model->label(2));?></h3></div>
        <div class="box-body">
        <?php    $this->widget('bootstrap.widgets.TbMenu', array(
       'type' => 'pills',
        	//	'htmlOptions'=>array('class'=>'abc'),
       'stacked' => false,
       'items' => array(
        		array('label' => 'Export',
        			
        				'url' => array('onlineOrder/admin' ,'exportCSV'=>'1',
        						
        		
        		),
       		
       		),
       ),
   ));  ?>
             <?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'stock-adjust-log-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>

<div class="col-md-6">

<?php echo $form->datepickerRow($model, 'start_date',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
						'options'=>array('format'=>'yyyy-mm-dd')))

; ?>
</div>
<div class="col-md-6">
<?php echo $form->datepickerRow($model, 'end_date',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
								'options'=>array('format'=>'yyyy-mm-dd')))

; ?>
</div>

	<div class="form-actions pull-left">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		)); ?>
	</div>

<?php $this->endWidget(); ?>
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">


<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'online-order-grid',
		'pager' => true,
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => array(
		//'id',
		
		'order_id',
			array(
					'name' => 'first_name',
					'value'=>'$data->getCustomerName()',
					
			),
			'mobile',
			'telephone',
			'order_date',
			'grand_total',
			
			'payment_method',
			'delivery_method',
		
			array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>OnlineOrder::getStatusOptions(),
				),
			'street',
			'delivery_boy',
			'delivery_telephone',
			'order_from',
			'comment',
		/*
		'street',
		'city',
		'telephone',
		'zip_code',
		'country',
		'delivery_slot',
		'ship_name',
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>OnlineOrder::getTypeOptions(),
				),
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>OnlineOrder::getStatusOptions(),
				),
		'update_time',
		'updated_by',
		*/
			array(
			
					'header'=>'<a>PDF</a>',
					'class'=>'FaButtonColumn',
					'template' => '{pdf}',
					//'template' => '{view}{update}', //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
							'pdf'=>array(
										
									'url' =>'Yii::app()->controller->createUrl("onlineOrder/pdf", array("id" => $data->id))',
									'label'=>'Pdf',
									'options'=>array('class'=>'pdf'),
										
							),
							/* 'update'=>array(
							 'visible'=>'$data->status =='."'".OnlineOrder::STATUS_PENDING."'",
									'url' =>'Yii::app()->controller->createUrl("onlineOrder/hold", array("id" => $data->id))',
									'label'=>'Hold',
									'options'=>array('class'=>'update'),
										
							)  */
								
					)
			),
			array(
						
					'header'=>'<a>Actions</a>',
					'class'=>'FaButtonColumn',
					'template' => '{view}',
					//'template' => '{view}{update}', //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
							'view'=>array(
									
									'url' =>'Yii::app()->controller->createUrl("onlineOrder/view", array("id" => $data->id))',
									'label'=>'View',
									'options'=>array('class'=>'view'),
			
							),
							 /* 'update'=>array(
									'visible'=>'$data->status =='."'".OnlineOrder::STATUS_PENDING."'",
									'url' =>'Yii::app()->controller->createUrl("onlineOrder/hold", array("id" => $data->id))',
									'label'=>'Hold',
									'options'=>array('class'=>'update'),
							
							)  */
							
					)
			),
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