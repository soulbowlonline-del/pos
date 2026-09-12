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
	<?php echo GxHtml::encode($data->getAttributeLabel('approved_qty')); ?>:
	<?php echo GxHtml::encode($data->approved_qty); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('bal_qty')); ?>:
	<?php echo GxHtml::encode($data->bal_qty); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('status')); ?>:
	<?php echo GxHtml::encode($data->status); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('type_id')); ?>:
	<?php echo GxHtml::encode($data->type_id); ?>
	<br />
	<?php /*
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
	<?php echo GxHtml::encode($data->getAttributeLabel('mrs_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->mrs)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('outlet_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->outlet)); ?>
	<br />
	*/ ?>

</div>