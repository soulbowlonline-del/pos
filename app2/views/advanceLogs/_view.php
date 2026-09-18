<?php
/**
 * Ported from protected/views/advanceLogs/_view.php.
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
	<?php echo Html::encode($data->getAttributeLabel('amount')); ?>:
	<?php echo Html::encode($data->amount); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('advance_payment_id')); ?>:
		<?php echo Html::encode(Gx::str($data->advancePayment)); ?>
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
	<?php /*
	<?php echo Html::encode($data->getAttributeLabel('update_time')); ?>:
	<?php echo Html::encode($data->update_time); ?>
	<br />
	*/ ?>

</div>