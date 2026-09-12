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

	<h1><?php echo Yii::t('app', 'CompanyWise Report'); ?></h1>
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
							'action' => Yii::app ()->createUrl ( 'order/compWiseExport?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => array (
									'enctype' => 'multipart/form-data' 
							) 
					) );
					?>
<?php

				
					$cols = array (
							'company' => 'Company',
							'amount' => 'Amount'
				
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



	<div class="form-actions">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		)); ?>
	</div>

<?php $this->endWidget(); ?>

<?php 

$model->search();
if(isset(Yii::app ()->session ['company_total'])){
	$cat_total = Yii::app ()->session ['company_total'];
	
}else{
	$cat_total = 0;
}
?>
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
 
<?php $this->widget ( 'bootstrap.widgets.TbExtendedGridView', array (
	'id' => 'item-category-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'pager' => true,
	'filter' => $model,
	'columns' => array(
		//	'id',
		'title',
			array (
					'header' => 'Total Amount',
					'value' => '$data->getCompanyTotalAmount()',
					'footer'=>$cat_total
			),
		/* 	array(
					'header' => 'Parent',
					'value'=>'$data->getParentValue($data->parent_id)',
						
			),
			array(
					'name' => 'status',
					'value'=>'$data->getStatusOptions($data->status)',
					'filter'=>ItemCategory::getStatusOptions(),
			), */
		/*
		'gender_id',
		'religion',
		array(
			'name'=>'class_id',
			'value'=>'GxHtml::valueEx($data->class)',
			'filter'=>GxHtml::listDataEx(Classes::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'section_id',
			'value'=>'GxHtml::valueEx($data->section)',
			'filter'=>GxHtml::listDataEx(Section::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'school_id',
			'value'=>'GxHtml::valueEx($data->school)',
			'filter'=>GxHtml::listDataEx(School::model()->findAllAttributes(null, true)),
			),
		'parent_id',
		'roll_no',
		'image_file',
		array(
				'name' => 'state_id',
				'value'=>'$data->getStatusOptions($data->state_id)',
				'filter'=>Student::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>Student::getTypeOptions(),
				),
		'update_time',
		*/
		
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