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
	<?php echo GxHtml::encode($data->getAttributeLabel('name')); ?>:
	<?php echo GxHtml::encode($data->name); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('email')); ?>:
	<?php echo GxHtml::encode($data->email); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('contact_no')); ?>:
	<?php echo GxHtml::encode($data->contact_no); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('gender_id')); ?>:
	<?php echo GxHtml::encode($data->gender_id); ?>
	<br />
	<?php /*
	<?php echo GxHtml::encode($data->getAttributeLabel('date_of_birth')); ?>:
	<?php echo GxHtml::encode($data->date_of_birth); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('date_of_joining')); ?>:
	<?php echo GxHtml::encode($data->date_of_joining); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('permanent_address')); ?>:
	<?php echo GxHtml::encode($data->permanent_address); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('temp_address')); ?>:
	<?php echo GxHtml::encode($data->temp_address); ?>
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
	<?php echo GxHtml::encode($data->getAttributeLabel('designation_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->designation)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('create_user_id')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->createUser)); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('updated_by')); ?>:
		<?php echo GxHtml::encode(GxHtml::valueEx($data->updatedBy)); ?>
	<br />
	*/ ?>

</div>