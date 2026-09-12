<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('id')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->id),array('view','id'=>$data->id)); ?>
	<br />

	<?php echo GxHtml::encode($data->getAttributeLabel('id')); ?>:
	<?php echo GxHtml::encode($data->id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('name')); ?>:
	<?php echo GxHtml::encode($data->name); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('description')); ?>:
	<?php echo GxHtml::encode($data->description); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('contact_person')); ?>:
	<?php echo GxHtml::encode($data->contact_person); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('person_designation')); ?>:
	<?php echo GxHtml::encode($data->person_designation); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('contact_no')); ?>:
	<?php echo GxHtml::encode($data->contact_no); ?>
	<br />
	<?php /*
	<?php echo GxHtml::encode($data->getAttributeLabel('secondary_contact_no')); ?>:
	<?php echo GxHtml::encode($data->secondary_contact_no); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('primary_address')); ?>:
	<?php echo GxHtml::encode($data->primary_address); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('secondary_address')); ?>:
	<?php echo GxHtml::encode($data->secondary_address); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('tax_no')); ?>:
	<?php echo GxHtml::encode($data->tax_no); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('is_local_vendor')); ?>:
	<?php echo GxHtml::encode($data->is_local_vendor); ?>
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
	<?php echo GxHtml::encode($data->getAttributeLabel('city_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->city)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('state_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->state)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('country_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->country)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('outlet_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->outlet)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('create_user_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->createUser)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('updated_by')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->updatedBy)); ?>
	<br />
	*/ ?>

</div>