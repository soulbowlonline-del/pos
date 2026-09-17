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
	$.fn.yiiGridView.update('vendor-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content-header">
<h1><?php echo Yii::t('app', 'Manage') . ' : ' . GxHtml::encode($model->label(2)); ?>
</h1>
<?php //echo CHtml::link('Delete',array('user/empty'));?>
<?php $this->widget('bootstrap.widgets.TbButtonGroup', array(
	'buttons'=>$this->menu,
	'type'=>'success',
	'htmlOptions'=>array('class'=> 'pull-right'),
));
?>
</section>

<?php    $this->widget('bootstrap.widgets.TbMenu', array(
       'type' => 'pills',
       'stacked' => false,
       'items' => array(
        		array('label' => 'Export',
        				'url' => array('vendor/admin' ,'exportCSV'=>'1',
        						
        		
        		),
       		
       		),
       ),
   ));  ?>
<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
         <div class="box-header"><h3 class="box-title"><?php echo  GxHtml::encode($model->label(2));?></h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
 
<?php $this->widget('bootstrap.widgets.TbExtendedGridView', array(
	'id' => 'vendor-grid',
	'type'=>'striped bordered condensed',
		'pager'=>true,
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => array(
		'id',
		'name',
		//'email',
		
		'contact_no',
			'secondary_contact_no',
			'primary_address:html',
		/*'secondary_contact_no',
		
		'primary_address:html',
		'secondary_address:html',
		'tax_no',
		array(
				'name' => 'is_local_vendor',
				'value' => '($data->is_local_vendor === 0) ? Yii::t(\'app\', \'No\') : Yii::t(\'app\', \'Yes\')',
				'filter' => array('0' => Yii::t('app', 'No'), '1' => Yii::t('app', 'Yes')),
				),
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>Vendor::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>Vendor::getTypeOptions(),
				),
		array(
			'name'=>'city_id',
			'value'=>'GxHtml::valueEx($data->city)',
			'filter'=>GxHtml::listDataEx(City::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'state_id',
			'value'=>'GxHtml::valueEx($data->state)',
			'filter'=>GxHtml::listDataEx(State::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'country_id',
			'value'=>'GxHtml::valueEx($data->country)',
			'filter'=>GxHtml::listDataEx(Country::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'outlet_id',
			'value'=>'GxHtml::valueEx($data->outlet)',
			'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			),
		*/
array(

		'header'=>'<a>Mrs Details</a>',
		'class'=>'FaButtonColumn',
		'template' => '{Mrs Details}', //include the standard buttons plus the new status button
		'htmlOptions'=> array('style'=>'width:80px'),
		'buttons'=>array(
				'Mrs Details'=>array(
						//'visible'=>'$data->checkPermission ("mrsDetail/admin")=="true"',
						'url' =>'Yii::app()->controller->createUrl("mrsDetail/admin", array("id" => $data->id))',
						'label'=>'Mrs Details',
						'options'=>array('class'=>'view'),
							
				),
					
		)
),
array(

		'header'=>'<a>Status</a>',
		'class'=>'FaButtonColumn',
		'template' => '{view}{update}', //include the standard buttons plus the new status button
		'htmlOptions'=> array('style'=>'width:80px'),
		'buttons'=>array(
				'view'=>array(
						'visible'=>'$data->checkPermission ("vendor/view")=="true"',
						'url' =>'Yii::app()->controller->createUrl("vendor/view", array("id" => $data->id))',
						'label'=>'View',
						'options'=>array('class'=>'view'),
							
				),
				'update'=>array(
						'visible'=>'$data->checkPermission ("vendor/create")=="true"',
						'url' =>'Yii::app()->controller->createUrl("vendor/create", array("id" => $data->id))',
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