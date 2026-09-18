<?php
/**
 * Ported from protected/views/vendorSchemes/_view.php.
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
	<?php echo Html::encode($data->getAttributeLabel('vendor_id')); ?>:
		<?php echo Html::encode(Gx::str($data->vendor)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('item_id')); ?>:
		<?php echo Html::encode(Gx::str($data->item)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('total_sale')); ?>:
	<?php echo Html::encode($data->total_sale); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('start_date')); ?>:
	<?php echo Html::encode($data->start_date); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('end_date')); ?>:
	<?php echo Html::encode($data->end_date); ?>
	<br />
	<?php /*
	<?php echo Html::encode($data->getAttributeLabel('discount')); ?>:
	<?php echo Html::encode($data->discount); ?>
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