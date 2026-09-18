<?php
/**
 * Ported from protected/views/paymentReport/_view.php.
 */

use app\components\Gx;
use yii\helpers\Html;
?>
<div class="view">

	<b><?php echo Html::encode($data->getAttributeLabel('id')); ?>:</b>
	<?php echo Html::a(Html::encode($data->id), Gx::url(['view','id'=>$data->id])); ?>
	<br />

	<?php echo Html::encode($data->getAttributeLabel('id')); ?>:
	<?php echo Html::encode($data->id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('doc_no')); ?>:
	<?php echo Html::encode($data->doc_no); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('chq_no')); ?>:
	<?php echo Html::encode($data->chq_no); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('comp_code')); ?>:
	<?php echo Html::encode($data->comp_code); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('house_bank')); ?>:
	<?php echo Html::encode($data->house_bank); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('hb_acct')); ?>:
	<?php echo Html::encode($data->hb_acct); ?>
	<br />
	<?php /*
	<?php echo Html::encode($data->getAttributeLabel('ben_acc_no')); ?>:
	<?php echo Html::encode($data->ben_acc_no); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('ref_no')); ?>:
	<?php echo Html::encode($data->ref_no); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('amount')); ?>:
	<?php echo Html::encode($data->amount); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('vendor_id')); ?>:
	<?php echo Html::encode($data->vendor_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('run_date')); ?>:
	<?php echo Html::encode($data->run_date); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('inst_date')); ?>:
	<?php echo Html::encode($data->inst_date); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('value_date')); ?>:
	<?php echo Html::encode($data->value_date); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('pay_type')); ?>:
	<?php echo Html::encode($data->pay_type); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('pay_status')); ?>:
	<?php echo Html::encode($data->pay_status); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('type_id')); ?>:
	<?php echo Html::encode($data->type_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('status')); ?>:
	<?php echo Html::encode($data->status); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('create_time')); ?>:
	<?php echo Html::encode($data->create_time); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('update_time')); ?>:
	<?php echo Html::encode($data->update_time); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('create_user_id')); ?>:
		<?php echo Html::encode(Gx::str($data->createUser)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('updated_by')); ?>:
		<?php echo Html::encode(Gx::str($data->updatedBy)); ?>
	<br />
	*/ ?>

</div>