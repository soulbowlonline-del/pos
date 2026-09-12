<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('id')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->id),array('view','id'=>$data->id)); ?>
	<br />

	<?php echo GxHtml::encode($data->getAttributeLabel('id')); ?>:
	<?php echo GxHtml::encode($data->id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('qty')); ?>:
	<?php echo GxHtml::encode($data->qty); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('discount')); ?>:
	<?php echo GxHtml::encode($data->discount); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('discount_amt')); ?>:
	<?php echo GxHtml::encode($data->discount_amt); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('total_amt')); ?>:
	<?php echo GxHtml::encode($data->total_amt); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('paid_amt')); ?>:
	<?php echo GxHtml::encode($data->paid_amt); ?>
	<br />
	<?php /*
	<?php echo GxHtml::encode($data->getAttributeLabel('status')); ?>:
	<?php echo GxHtml::encode($data->status); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('type_id')); ?>:
	<?php echo GxHtml::encode($data->type_id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('city_id')); ?>:
	<?php echo GxHtml::encode($data->city_id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('state_id')); ?>:
	<?php echo GxHtml::encode($data->state_id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('country_id')); ?>:
	<?php echo GxHtml::encode($data->country_id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('address')); ?>:
	<?php echo GxHtml::encode($data->address); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('note')); ?>:
	<?php echo GxHtml::encode($data->note); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('create_time')); ?>:
	<?php echo GxHtml::encode($data->create_time); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('update_time')); ?>:
	<?php echo GxHtml::encode($data->update_time); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('order_id')); ?>:
	<?php echo GxHtml::encode($data->order_id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('customer_id')); ?>:
	<?php echo GxHtml::encode($data->customer_id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('updated_by')); ?>:
	<?php echo GxHtml::encode($data->updated_by); ?>
	<br />
	*/ ?>

</div>