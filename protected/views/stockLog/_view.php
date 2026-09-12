<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('id')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->id),array('view','id'=>$data->id)); ?>
	<br />

	<?php echo GxHtml::encode($data->getAttributeLabel('id')); ?>:
	<?php echo GxHtml::encode($data->id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('item_detail_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->itemDetail)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('item_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->item)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('batch_no')); ?>:
	<?php echo GxHtml::encode($data->batch_no); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('Qty')); ?>:
	<?php echo GxHtml::encode($data->Qty); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('outlet_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->outlet)); ?>
	<br />
	<?php /*
	<?php echo GxHtml::encode($data->getAttributeLabel('vendor_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->vendor)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('type_id')); ?>:
	<?php echo GxHtml::encode($data->type_id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('status')); ?>:
	<?php echo GxHtml::encode($data->status); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('create_time')); ?>:
	<?php echo GxHtml::encode($data->create_time); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('update_time')); ?>:
	<?php echo GxHtml::encode($data->update_time); ?>
	<br />
	*/ ?>

</div>