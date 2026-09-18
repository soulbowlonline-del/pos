<?php
/**
 * Ported from protected/views/purchaseOrder/_view.php.
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
	<?php echo Html::encode($data->getAttributeLabel('code')); ?>:
	<?php echo Html::encode($data->code); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('start_date')); ?>:
	<?php echo Html::encode($data->start_date); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('end_date')); ?>:
	<?php echo Html::encode($data->end_date); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('receiving_date')); ?>:
	<?php echo Html::encode($data->receiving_date); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('status')); ?>:
	<?php echo Html::encode($data->status); ?>
	<br />
	<?php /*
	<?php echo Html::encode($data->getAttributeLabel('type_id')); ?>:
	<?php echo Html::encode($data->type_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('is_open_po')); ?>:
	<?php echo Html::encode($data->is_open_po); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('is_po_received')); ?>:
	<?php echo Html::encode($data->is_po_received); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('remarks')); ?>:
	<?php echo Html::encode($data->remarks); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('payment_terms')); ?>:
	<?php echo Html::encode($data->payment_terms); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('transport_mode')); ?>:
	<?php echo Html::encode($data->transport_mode); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('purchase_order_amount')); ?>:
	<?php echo Html::encode($data->purchase_order_amount); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('charges_total_amount')); ?>:
	<?php echo Html::encode($data->charges_total_amount); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('discount_amount')); ?>:
	<?php echo Html::encode($data->discount_amount); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('frieght_charges')); ?>:
	<?php echo Html::encode($data->frieght_charges); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('extra_charges')); ?>:
	<?php echo Html::encode($data->extra_charges); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('total_amount')); ?>:
	<?php echo Html::encode($data->total_amount); ?>
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
	<?php echo Html::encode($data->getAttributeLabel('outlet_id')); ?>:
		<?php echo Html::encode(Gx::str($data->outlet)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('vendor_id')); ?>:
		<?php echo Html::encode(Gx::str($data->vendor)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('mrn_id')); ?>:
		<?php echo Html::encode(Gx::str($data->mrn)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('organization_id')); ?>:
		<?php echo Html::encode(Gx::str($data->organization)); ?>
	<br />
	*/ ?>

</div>