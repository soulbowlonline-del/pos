<?php

$this->breadcrumbs = array(
	$model->label(2) => array('index'),
	GxHtml::valueEx($model),
);


?>

<section class="content">
<div class="page-header">
<h1 class="pull-left"><?php echo GxHtml::encode(GxHtml::valueEx($model)); ?></h1>


<?php   $this->widget('bootstrap.widgets.TbButtonGroup', array(
	'buttons'=>$this->actions,
	'type'=>'success',
	'htmlOptions'=>array('class'=> 'pull-right'),
	));
?>
<div class="clearfix"></div>


</div>

<?php $this->widget('bootstrap.widgets.TbDetailView', array(
	'data' => $model,
	'attributes' => array(
'id',
'bill_no',
'bill_date',
'mode_of_payment',
'mode_of_delivery',
'qty',
'discount_amt',
'total_amt',
'paid_amt',
/* array(
				'name' => 'status',
				'type' => 'raw',
				'value'=>$model->getStatusOptions($model->status),
				),
array(
				'name' => 'type_id',
				'type' => 'raw',
				'value'=>$model->getTypeOptions($model->type_id),
				), */
//'city_id',
/* array(
				'name' => 'state_id',
				'type' => 'raw',
				'value'=>$model->getStatusOptions($model->state_id),
				), */
//'country_id',
array(
					'name' => 'Outlet',
					'value'=>isset($model->outlet)?$model->outlet:"",
					//'filter'=>Order::getStatusOptions(),
			),
			
'address:html',
'note:html',
'create_time',
'update_time',
//'customer_id',
array(
		'name' => 'Customer',
		'value'=>isset($model->customer)?$model->customer:"",
		//'filter'=>Order::getStatusOptions(),
)

//'updated_by',
			
	),
)); ?>

<?php
 $this->StartPanel(); ?>
<?php  $this->AddPanel($model->getRelationLabel('orderItems'), $model->getRelatedDataProvider('orderItems'),	'orderItems','orderItem');?>
<?php  //$this->AddPanel($model->getRelationLabel('orderRefunds'), $model->getRelatedDataProvider('orderRefunds'),	'orderRefunds','orderRefund');?>
<?php  $this->EndPanel(); ?>

<?php   $this->widget('CommentPortlet', array(
	'model' => $model,
));
?>
</section>