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
	<?php echo GxHtml::encode($data->getAttributeLabel('image_file1')); ?>:
	<?php echo GxHtml::encode($data->image_file1); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('image_file2')); ?>:
	<?php echo GxHtml::encode($data->image_file2); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('image_file3')); ?>:
	<?php echo GxHtml::encode($data->image_file3); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('type_id')); ?>:
	<?php echo GxHtml::encode($data->type_id); ?>
	<br />
	<?php /*
	<?php echo GxHtml::encode($data->getAttributeLabel('status')); ?>:
	<?php echo GxHtml::encode($data->status); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('create_time')); ?>:
	<?php echo GxHtml::encode($data->create_time); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('create_user_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->createUser)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('po_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->po)); ?>
	<br />
	*/ ?>

</div>