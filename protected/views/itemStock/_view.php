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
	<?php echo GxHtml::encode($data->item_id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('batch_number')); ?>:
	<?php echo GxHtml::encode($data->batch_number); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('qty')); ?>:
	<?php echo GxHtml::encode($data->qty); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('base_price')); ?>:
	<?php echo GxHtml::encode($data->base_price); ?>
	<br />
	<?php /*
	<?php echo GxHtml::encode($data->getAttributeLabel('mrp')); ?>:
	<?php echo GxHtml::encode($data->mrp); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('status')); ?>:
	<?php echo GxHtml::encode($data->status); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('type_id')); ?>:
	<?php echo GxHtml::encode($data->type_id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('create_time')); ?>:
	<?php echo GxHtml::encode($data->create_time); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('create_user_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->createUser)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('tax_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->tax)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('updated_by')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->updatedBy)); ?>
	<br />
	*/ ?>

</div>