<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('id')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->id),array('view','id'=>$data->id)); ?>
	<br />

	<?php echo GxHtml::encode($data->getAttributeLabel('id')); ?>:
	<?php echo GxHtml::encode($data->id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('title')); ?>:
	<?php echo GxHtml::encode($data->title); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('item_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->item)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('item_detail_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->itemDetail)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('item_category_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->itemCategory)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('item_company_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->itemCompany)); ?>
	<br />
	<?php /*
	<?php echo GxHtml::encode($data->getAttributeLabel('qty')); ?>:
	<?php echo GxHtml::encode($data->qty); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('stock_qty')); ?>:
	<?php echo GxHtml::encode($data->stock_qty); ?>
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
	<?php echo GxHtml::encode($data->getAttributeLabel('create_user_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->createUser)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('updated_by')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->updatedBy)); ?>
	<br />
	*/ ?>

</div>