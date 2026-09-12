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
	'buttons'=>$this->menu,
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
'order_id',
'item_count',
'grand_total',

array(
				'name' => 'first_name',
				'type' => 'raw',
				'value'=>$model->getCustomerName(),
				),
'street',
'city',
			'mobile',
'telephone',
'zip_code',
'country',
'delivery_slot',
'ship_name',
			'delivery_boy',
			'delivery_telephone',
			'payment_method',
			'delivery_method',
/* array(
				'name' => 'type_id',
				'type' => 'raw',
				'value'=>$model->getTypeOptions($model->type_id),
				), */
array(
				'name' => 'status',
				'type' => 'raw',
				'value'=>$model->getStatusOptions($model->status),
				),
'create_time',
'order_from',
'comment'

	),
)); ?>

<?php
 $this->StartPanel(); ?>
<?php  $this->AddPanel($model->getRelationLabel('Items'), $model->getRelatedDataProvider('onlineOrderItems'),	'onlineOrderItems','onlineOrderItem');?>
<?php // $this->AddPanel($model->getRelationLabel('purchaseOrders'), $model->getRelatedDataProvider('purchaseOrders'),	'purchaseOrders','purchaseOrder');?>
<?php  $this->EndPanel(); ?>
</section>