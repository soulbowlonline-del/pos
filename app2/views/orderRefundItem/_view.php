<?php
/**
 * Ported from protected/views/orderRefundItem/_view.php.
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
	<?php echo Html::encode($data->getAttributeLabel('order_refund_id')); ?>:
		<?php echo Html::encode(Gx::str($data->orderRefund)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('item_detail_id')); ?>:
		<?php echo Html::encode(Gx::str($data->itemDetail)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('qty')); ?>:
	<?php echo Html::encode($data->qty); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('price')); ?>:
	<?php echo Html::encode($data->price); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('discount_id')); ?>:
		<?php echo Html::encode(Gx::str($data->discount)); ?>
	<br />
	<?php /*
	<?php echo Html::encode($data->getAttributeLabel('discount_amt')); ?>:
	<?php echo Html::encode($data->discount_amt); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('tax_id')); ?>:
	<?php echo Html::encode($data->tax_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('tax_amt')); ?>:
	<?php echo Html::encode($data->tax_amt); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('order_discount')); ?>:
	<?php echo Html::encode($data->order_discount); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('status')); ?>:
	<?php echo Html::encode($data->status); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('type_id')); ?>:
	<?php echo Html::encode($data->type_id); ?>
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