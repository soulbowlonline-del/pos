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
	<?php echo GxHtml::encode($data->getAttributeLabel('item_code')); ?>:
	<?php echo GxHtml::encode($data->item_code); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('description')); ?>:
	<?php echo GxHtml::encode($data->description); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('image_file')); ?>:
	<?php echo GxHtml::encode($data->image_file); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('item_type')); ?>:
	<?php echo GxHtml::encode($data->item_type); ?>
	<br />
	<?php /*
	<?php echo GxHtml::encode($data->getAttributeLabel('status')); ?>:
	<?php echo GxHtml::encode($data->status); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('type_id')); ?>:
	<?php echo GxHtml::encode($data->type_id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('is_tax')); ?>:
	<?php echo GxHtml::encode($data->is_tax); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('is_discount')); ?>:
	<?php echo GxHtml::encode($data->is_discount); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('category_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->category)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('sub_company_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->subCompany)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('company_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->company)); ?>
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