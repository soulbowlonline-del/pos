<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('id')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->id),array('view','id'=>$data->id)); ?>
	<br />

	<?php echo GxHtml::encode($data->getAttributeLabel('id')); ?>:
	<?php echo GxHtml::encode($data->id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('req_qty')); ?>:
	<?php echo GxHtml::encode($data->req_qty); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('bal_qty')); ?>:
	<?php echo GxHtml::encode($data->bal_qty); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('approved_qty')); ?>:
	<?php echo GxHtml::encode($data->approved_qty); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('mrp')); ?>:
	<?php echo GxHtml::encode($data->mrp); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('price')); ?>:
	<?php echo GxHtml::encode($data->price); ?>
	<br />
	<?php /*
	<?php echo GxHtml::encode($data->getAttributeLabel('discount')); ?>:
	<?php echo GxHtml::encode($data->discount); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('discount_amt')); ?>:
	<?php echo GxHtml::encode($data->discount_amt); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('vat')); ?>:
	<?php echo GxHtml::encode($data->vat); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('other_charge')); ?>:
	<?php echo GxHtml::encode($data->other_charge); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('amount')); ?>:
	<?php echo GxHtml::encode($data->amount); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('sale_rate')); ?>:
	<?php echo GxHtml::encode($data->sale_rate); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('status')); ?>:
	<?php echo GxHtml::encode($data->status); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('type_id')); ?>:
	<?php echo GxHtml::encode($data->type_id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('charge_amount')); ?>:
	<?php echo GxHtml::encode($data->charge_amount); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('extra_charges')); ?>:
	<?php echo GxHtml::encode($data->extra_charges); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('remarks')); ?>:
	<?php echo GxHtml::encode($data->remarks); ?>
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
	<?php echo GxHtml::encode($data->getAttributeLabel('item_detail_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->itemDetail)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('purchase_bill_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->purchaseBill)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('outlet_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->outlet)); ?>
	<br />
	*/ ?>

</div>