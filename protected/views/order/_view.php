<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('id')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->id),array('view','id'=>$data->id)); ?>
	<br />

	<?php echo GxHtml::encode($data->getAttributeLabel('id')); ?>:
	<?php echo GxHtml::encode($data->id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('bill_no')); ?>:
	<?php echo GxHtml::encode($data->bill_no); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('bill_date')); ?>:
	<?php echo GxHtml::encode($data->bill_date); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('mode_of_payment')); ?>:
	<?php echo GxHtml::encode($data->mode_of_payment); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('mode_of_delivery')); ?>:
	<?php echo GxHtml::encode($data->mode_of_delivery); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('qty')); ?>:
	<?php echo GxHtml::encode($data->qty); ?>
	<br />
	<?php /*
	<?php echo GxHtml::encode($data->getAttributeLabel('discount_amt')); ?>:
	<?php echo GxHtml::encode($data->discount_amt); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('total_amt')); ?>:
	<?php echo GxHtml::encode($data->total_amt); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('paid_amt')); ?>:
	<?php echo GxHtml::encode($data->paid_amt); ?>
	<br />
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
	<?php echo GxHtml::encode($data->getAttributeLabel('outlet_id')); ?>:
	<?php echo GxHtml::encode($data->outlet_id); ?>
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
	<?php echo GxHtml::encode($data->getAttributeLabel('customer_id')); ?>:
	<?php echo GxHtml::encode($data->customer_id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('updated_by')); ?>:
	<?php echo GxHtml::encode($data->updated_by); ?>
	<br />
	*/ ?>

</div>