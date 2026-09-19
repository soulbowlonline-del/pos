<?php
/**
 * Ported from protected/views/itemReturn/_view.php.
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
	<?php echo Html::encode($data->getAttributeLabel('discount_amt')); ?>:
	<?php echo Html::encode($data->discount_amt); ?>
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
	<?php /*
	<?php echo Html::encode($data->getAttributeLabel('status')); ?>:
	<?php echo Html::encode($data->status); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('type_id')); ?>:
	<?php echo Html::encode($data->type_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('create_time')); ?>:
	<?php echo Html::encode($data->create_time); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('credit_note_id')); ?>:
	<?php echo Html::encode($data->credit_note_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('create_user_id')); ?>:
	<?php echo Html::encode($data->create_user_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('updated_by')); ?>:
	<?php echo Html::encode($data->updated_by); ?>
	<br />
	*/ ?>

</div>