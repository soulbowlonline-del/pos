<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('id')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->id),array('view','id'=>$data->id)); ?>
	<br />

	<?php echo GxHtml::encode($data->getAttributeLabel('id')); ?>:
	<?php echo GxHtml::encode($data->id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('item_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->item)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('item_detail_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->itemDetail)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('mrp')); ?>:
	<?php echo GxHtml::encode($data->mrp); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('price')); ?>:
	<?php echo GxHtml::encode($data->price); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('sale_rate')); ?>:
	<?php echo GxHtml::encode($data->sale_rate); ?>
	<br />
	<?php /*
	<?php echo GxHtml::encode($data->getAttributeLabel('free')); ?>:
	<?php echo GxHtml::encode($data->free); ?>
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
	<?php echo GxHtml::encode($data->getAttributeLabel('discount1')); ?>:
	<?php echo GxHtml::encode($data->discount1); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('discount_amt1')); ?>:
	<?php echo GxHtml::encode($data->discount_amt1); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('cgst_per')); ?>:
	<?php echo GxHtml::encode($data->cgst_per); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('sgst_per')); ?>:
	<?php echo GxHtml::encode($data->sgst_per); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('cess_per')); ?>:
	<?php echo GxHtml::encode($data->cess_per); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('cgst_amt')); ?>:
	<?php echo GxHtml::encode($data->cgst_amt); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('sgst_amt')); ?>:
	<?php echo GxHtml::encode($data->sgst_amt); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('cess_amt')); ?>:
	<?php echo GxHtml::encode($data->cess_amt); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('igst_per')); ?>:
	<?php echo GxHtml::encode($data->igst_per); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('igst_amt')); ?>:
	<?php echo GxHtml::encode($data->igst_amt); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('tax_id')); ?>:
	<?php echo GxHtml::encode($data->tax_id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('other_charge')); ?>:
	<?php echo GxHtml::encode($data->other_charge); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('total_amt')); ?>:
	<?php echo GxHtml::encode($data->total_amt); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('vendor_id')); ?>:
	<?php echo GxHtml::encode($data->vendor_id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('outlet_id')); ?>:
	<?php echo GxHtml::encode($data->outlet_id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('status')); ?>:
	<?php echo GxHtml::encode($data->status); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('type_id')); ?>:
	<?php echo GxHtml::encode($data->type_id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('return_id')); ?>:
	<?php echo GxHtml::encode($data->return_id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('create_time')); ?>:
	<?php echo GxHtml::encode($data->create_time); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('create_user_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->createUser)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('updated_by')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->updatedBy)); ?>
	<br />
	*/ ?>

</div>