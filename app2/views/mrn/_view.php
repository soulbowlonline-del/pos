<?php
/**
 * Ported from protected/views/mrn/_view.php.
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
	<?php echo Html::encode($data->getAttributeLabel('mrs_date')); ?>:
	<?php echo Html::encode($data->mrs_date); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('mrs_update_date')); ?>:
	<?php echo Html::encode($data->mrs_update_date); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('mrs_req_date')); ?>:
	<?php echo Html::encode($data->mrs_req_date); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('status')); ?>:
	<?php echo Html::encode($data->status); ?>
	<br />
	<?php /*
	<?php echo Html::encode($data->getAttributeLabel('type_id')); ?>:
	<?php echo Html::encode($data->type_id); ?>
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
	<?php echo Html::encode($data->getAttributeLabel('outlet_id')); ?>:
		<?php echo Html::encode(Gx::str($data->outlet)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('mrs_id')); ?>:
		<?php echo Html::encode(Gx::str($data->mrs)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('organization_id')); ?>:
		<?php echo Html::encode(Gx::str($data->organization)); ?>
	<br />
	*/ ?>

</div>