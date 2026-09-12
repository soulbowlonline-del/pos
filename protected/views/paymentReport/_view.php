<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('id')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->id),array('view','id'=>$data->id)); ?>
	<br />

	<?php echo GxHtml::encode($data->getAttributeLabel('id')); ?>:
	<?php echo GxHtml::encode($data->id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('doc_no')); ?>:
	<?php echo GxHtml::encode($data->doc_no); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('chq_no')); ?>:
	<?php echo GxHtml::encode($data->chq_no); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('comp_code')); ?>:
	<?php echo GxHtml::encode($data->comp_code); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('house_bank')); ?>:
	<?php echo GxHtml::encode($data->house_bank); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('hb_acct')); ?>:
	<?php echo GxHtml::encode($data->hb_acct); ?>
	<br />
	<?php /*
	<?php echo GxHtml::encode($data->getAttributeLabel('ben_acc_no')); ?>:
	<?php echo GxHtml::encode($data->ben_acc_no); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('ref_no')); ?>:
	<?php echo GxHtml::encode($data->ref_no); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('amount')); ?>:
	<?php echo GxHtml::encode($data->amount); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('vendor_id')); ?>:
	<?php echo GxHtml::encode($data->vendor_id); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('run_date')); ?>:
	<?php echo GxHtml::encode($data->run_date); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('inst_date')); ?>:
	<?php echo GxHtml::encode($data->inst_date); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('value_date')); ?>:
	<?php echo GxHtml::encode($data->value_date); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('pay_type')); ?>:
	<?php echo GxHtml::encode($data->pay_type); ?>
	<br />
	<?php echo GxHtml::encode($data->getAttributeLabel('pay_status')); ?>:
	<?php echo GxHtml::encode($data->pay_status); ?>
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