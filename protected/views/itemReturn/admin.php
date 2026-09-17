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
	$.fn.yiiGridView.update('item-return-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content-header">
<h1><?php echo Yii::t('app', 'Manage') . ' : ' . GxHtml::encode($model->label(2)); ?></h1>
<?php //echo CHtml::link('Delete',array('user/empty'));?>
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
					
					$form = $this->beginWidget ( 'bootstrap.widgets.TbActiveForm', array (
							'id' => 'customer-export-form',
							'type' => 'horizontal',
							'action' => Yii::app ()->createUrl ( 'itemReturn/admin?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => array (
									'enctype' => 'multipart/form-data' 
							) 
					) );
					?>
<?php


					$cols = array (
							'gross_amt' => 'Gross Amount',
							'tax_amt' => 'Tax Amount',
							'discount_amt' => 'Discount Amount',
							'total_amt' => 'Total Amount',
							'vendor_id' => 'Vendor',
							'outlet_id' => 'Outlet',
							'credit_note_id' => 'Credit Note'
				
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
         <div class="box-header"><h3 class="box-title"><?php echo  GxHtml::encode($model->label(2));?></h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">

<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'id' => 'item-return-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
		'pager'=>true,
	'columns' => array(
		//'id',
			
			array(
					'name'=>'gross_amt',
					'value'=>'$data->gross_amt',
					'footer'=>$model->getTotals($model->search()->getKeys(),'gross_amt','tbl_item_return'),
			),
			array(
					'name'=>'tax_amt',
					'value'=>'$data->tax_amt',
					'footer'=>$model->getTotals($model->search()->getKeys(),'tax_amt','tbl_item_return'),
			),
			array(
					'name'=>'discount_amt',
					'value'=>'$data->discount_amt',
					'footer'=>$model->getTotals($model->search()->getKeys(),'discount_amt','tbl_item_return'),
			),
			array(
					'name'=>'total_amt',
					'value'=>'$data->total_amt',
					'footer'=>$model->getTotals($model->search()->getKeys(),'total_amt','tbl_item_return'),
			),
				
			array(
					'name'=>'vendor_id',
					'value'=>'GxHtml::valueEx($data->vendor)',
					'filter'=>GxHtml::listDataEx(Vendor::model()->findAllAttributes(null, true)),
			),
			array(
					'name'=>'outlet_id',
					'value'=>'GxHtml::valueEx($data->outlet)',
					'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
			array(
					'name'=>'credit_note_id',
					'value'=>'$data->credit_note_no',
					'filter' => false
				//	'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
			array(
				'name'=>'bill_date',
				'value'=>'$data->credit_note_date',
				'filter' => false
			//	'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
		),
		array(
			'name'=>'save_date',
			// 'value'=>'$data->grn_save_date',
			'value'=>'$data->create_time',
			'filter' => false
		//	'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
	),
		/*
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>ItemReturn::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>ItemReturn::getTypeOptions(),
				),
		'credit_note_id',
		'updated_by',
		*/
			array(
			
					'header'=>'<a>Status</a>',
					'class'=>'FaButtonColumn',
					'template' => '{view}{update}', //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
							'view'=>array(
								//	'visible'=>'$data->checkPermission ("outlet/view")=="true"',
									'url' =>'Yii::app()->controller->createUrl("itemReturn/view", array("id" => $data->id))',
									'label'=>'View',
									'options'=>array('class'=>'view'),
										
							),
							'update'=>array(
								//	'visible'=>'$data->checkPermission ("outlet/view")=="true"',
									'url' =>'Yii::app()->controller->createUrl("itemReturn/update", array("id" => $data->id))',
									'label'=>'Update',
									'options'=>array('class'=>'update'),
										
							)
							
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