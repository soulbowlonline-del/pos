<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('id')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->id),array('view','id'=>$data->id)); ?>
	<br />

	<?php echo GxHtml::encode($data->getAttributeLabel('id')); ?>:
	<?php echo GxHtml::encode($data->id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('code')); ?>:
	<?php echo GxHtml::encode($data->code); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('start_date')); ?>:
	<?php echo GxHtml::encode($data->start_date); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('end_date')); ?>:
	<?php echo GxHtml::encode($data->end_date); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('receiving_date')); ?>:
	<?php echo GxHtml::encode($data->receiving_date); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('status')); ?>:
	<?php echo GxHtml::encode($data->status); ?>
	<br />
	<?php /*
	<?php echo GxHtml::encode($data->getAttributeLabel('type_id')); ?>:
	<?php echo GxHtml::encode($data->type_id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('is_open_po')); ?>:
	<?php echo GxHtml::encode($data->is_open_po); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('is_po_received')); ?>:
	<?php echo GxHtml::encode($data->is_po_received); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('remarks')); ?>:
	<?php echo GxHtml::encode($data->remarks); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('payment_terms')); ?>:
	<?php echo GxHtml::encode($data->payment_terms); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('transport_mode')); ?>:
	<?php echo GxHtml::encode($data->transport_mode); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('purchase_order_amount')); ?>:
	<?php echo GxHtml::encode($data->purchase_order_amount); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('charges_total_amount')); ?>:
	<?php echo GxHtml::encode($data->charges_total_amount); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('discount_amount')); ?>:
	<?php echo GxHtml::encode($data->discount_amount); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('frieght_charges')); ?>:
	<?php echo GxHtml::encode($data->frieght_charges); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('extra_charges')); ?>:
	<?php echo GxHtml::encode($data->extra_charges); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('total_amount')); ?>:
	<?php echo GxHtml::encode($data->total_amount); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('create_time')); ?>:
	<?php echo GxHtml::encode($data->create_time); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('update_time')); ?>:
	<?php echo GxHtml::encode($data->update_time); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('create_user_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->createUser)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('updated_by')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->updatedBy)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('outlet_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->outlet)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('vendor_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->vendor)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('purchase_order_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->purchaseOrder)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('organization_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->organization)); ?>
	<br />
	*/ ?>

</div>