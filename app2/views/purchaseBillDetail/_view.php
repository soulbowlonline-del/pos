<?php
/**
 * Ported from protected/views/purchaseBillDetail/_view.php.
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
	<?php echo Html::encode($data->getAttributeLabel('req_qty')); ?>:
	<?php echo Html::encode($data->req_qty); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('bal_qty')); ?>:
	<?php echo Html::encode($data->bal_qty); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('approved_qty')); ?>:
	<?php echo Html::encode($data->approved_qty); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('mrp')); ?>:
	<?php echo Html::encode($data->mrp); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('price')); ?>:
	<?php echo Html::encode($data->price); ?>
	<br />
	<?php /*
	<?php echo Html::encode($data->getAttributeLabel('discount')); ?>:
	<?php echo Html::encode($data->discount); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('discount_amt')); ?>:
	<?php echo Html::encode($data->discount_amt); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('vat')); ?>:
	<?php echo Html::encode($data->vat); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('other_charge')); ?>:
	<?php echo Html::encode($data->other_charge); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('amount')); ?>:
	<?php echo Html::encode($data->amount); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('sale_rate')); ?>:
	<?php echo Html::encode($data->sale_rate); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('status')); ?>:
	<?php echo Html::encode($data->status); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('type_id')); ?>:
	<?php echo Html::encode($data->type_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('charge_amount')); ?>:
	<?php echo Html::encode($data->charge_amount); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('extra_charges')); ?>:
	<?php echo Html::encode($data->extra_charges); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('remarks')); ?>:
	<?php echo Html::encode($data->remarks); ?>
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
	<?php echo Html::encode($data->getAttributeLabel('item_detail_id')); ?>:
		<?php echo Html::encode(Gx::str($data->itemDetail)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('purchase_bill_id')); ?>:
		<?php echo Html::encode(Gx::str($data->purchaseBill)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('outlet_id')); ?>:
		<?php echo Html::encode(Gx::str($data->outlet)); ?>
	<br />
	*/ ?>

</div>