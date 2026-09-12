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
	$.fn.yiiGridView.update('mrs-detail-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content-header">
  <h1> <?php echo Yii::t('app', 'Manage') ;?> <?php echo GxHtml::encode($model->label(2))?> </h1>
 
</section>
<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
        <div class="box-header"><h3 class="box-title">MrsDetails</h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">

<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'id' => 'mrs-detail-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'action'=>Yii::app()->createUrl('mrsDetail/pending'),
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>


<?php //echo $form->dropDownListRow($model, 'mrs_id', GxHtml::listDataEx(Mrs::model()->findAllAttributes(null, true))); ?>

<div class="box-body">
<?php echo $form->datepickerRow($model, 'mrs_req_date',
					array('hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
					'class'=>'form-control'))
; ?>
<?php echo $form->dropdownListRow($model, 'outlet_id', GxHtml::listDataEx(Outlet::model()->findAllByAttributes(array('status'=>Outlet::STATUS_ACTIVE))),array('class'=>'form-control')); ?>


</div>


	<div class="form-actions box-footer">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Save',
		)); ?>
	</div>

<?php $this->endWidget(); ?>
<div class="clearfix"></div>
			  <br/>
 <div class="table-responsive">
                <div class="table table-bordered table-hover dataTable">
<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm', array(
    'enableAjaxValidation'=>true,
)); ?>
 
<?php 
    $this->widget('bootstrap.widgets.TbGridView', array(
    'id'=>'menu-grid',
    'dataProvider'=>$model->pendingsearch(),
    'filter'=>$model,
    'columns'=>array(
     /*    array(
            'id'=>'mrsId',
            'class'=>'CCheckBoxColumn',
            'selectableRows' => '50',   
        ), */
        array(
			'name'=>'item_id',
			'value'=>'GxHtml::valueEx($data->item)',
			'filter'=>GxHtml::listDataEx(Item::model()->findAllAttributes(null, true)),
	),
			array(
					'name'=>'item_detail_id',
					'value'=>'GxHtml::valueEx($data->itemDetail)',
					'filter'=>GxHtml::listDataEx(ItemDetail::model()->findAllAttributes(null, true)),
			),
			array(
					'name'=>'outlet_id',
					'value'=>'GxHtml::valueEx($data->outlet)',
					'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
			array(
					'header'=>'vendor',
					'value'=>'GxHtml::valueEx($data->mrs->vendor)',
					
			),
			'req_qty',
			
    		array(
    		
    				'header'=>'<a>Assign</a>',
    				'class'=>'CButtonColumn',
    				'template' => '{Assign}', //include the standard buttons plus the new status button
    				'htmlOptions'=> array('style'=>'width:80px'),
    				'buttons'=>array(
    						'Assign'=>array(
    								//'visible'=>'$data->checkPermission ("mrsDetail/admin")=="true"',
    								'url' =>'Yii::app()->controller->createUrl("mrsDetail/assign", array("id" => $data->id))',
    								'label'=>'Assign',
    								'options'=>array('class'=>'view'),
    									
    						),
    							
    				)
    		),

        
    ),
)); ?>
<script>
function reloadGrid(data) {
    $.fn.yiiGridView.update('menu-grid');
}
</script>

<?php //echo CHtml::ajaxSubmitButton('Assign',array('mrs/assign','act'=>'Insert'), array('success'=>'reloadGrid')); ?>

<?php $this->endWidget(); ?>
                </div>
              </div>
            </div>
          </div>
    
        </div>
      </div>
    </div>
  </div>
</section>