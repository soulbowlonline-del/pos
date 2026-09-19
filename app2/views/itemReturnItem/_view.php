<?php
/**
 * Ported from protected/views/itemReturnItem/_view.php.
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
	<?php echo Html::encode($data->getAttributeLabel('item_id')); ?>:
		<?php echo Html::encode(Gx::str($data->item)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('item_detail_id')); ?>:
		<?php echo Html::encode(Gx::str($data->itemDetail)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('mrp')); ?>:
	<?php echo Html::encode($data->mrp); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('price')); ?>:
	<?php echo Html::encode($data->price); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('sale_rate')); ?>:
	<?php echo Html::encode($data->sale_rate); ?>
	<br />
	<?php /*
	<?php echo Html::encode($data->getAttributeLabel('free')); ?>:
	<?php echo Html::encode($data->free); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('qty')); ?>:
	<?php echo Html::encode($data->qty); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('discount')); ?>:
	<?php echo Html::encode($data->discount); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('discount_amt')); ?>:
	<?php echo Html::encode($data->discount_amt); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('discount1')); ?>:
	<?php echo Html::encode($data->discount1); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('discount_amt1')); ?>:
	<?php echo Html::encode($data->discount_amt1); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('cgst_per')); ?>:
	<?php echo Html::encode($data->cgst_per); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('sgst_per')); ?>:
	<?php echo Html::encode($data->sgst_per); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('cess_per')); ?>:
	<?php echo Html::encode($data->cess_per); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('cgst_amt')); ?>:
	<?php echo Html::encode($data->cgst_amt); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('sgst_amt')); ?>:
	<?php echo Html::encode($data->sgst_amt); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('cess_amt')); ?>:
	<?php echo Html::encode($data->cess_amt); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('igst_per')); ?>:
	<?php echo Html::encode($data->igst_per); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('igst_amt')); ?>:
	<?php echo Html::encode($data->igst_amt); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('tax_id')); ?>:
	<?php echo Html::encode($data->tax_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('other_charge')); ?>:
	<?php echo Html::encode($data->other_charge); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('total_amt')); ?>:
	<?php echo Html::encode($data->total_amt); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('vendor_id')); ?>:
	<?php echo Html::encode($data->vendor_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('outlet_id')); ?>:
	<?php echo Html::encode($data->outlet_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('status')); ?>:
	<?php echo Html::encode($data->status); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('type_id')); ?>:
	<?php echo Html::encode($data->type_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('return_id')); ?>:
	<?php echo Html::encode($data->return_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('create_time')); ?>:
	<?php echo Html::encode($data->create_time); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('create_user_id')); ?>:
		<?php echo Html::encode(Gx::str($data->createUser)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('updated_by')); ?>:
		<?php echo Html::encode(Gx::str($data->updatedBy)); ?>
	<br />
	*/ ?>

</div>