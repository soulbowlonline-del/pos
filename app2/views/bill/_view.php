<?php
/**
 * Ported from protected/views/bill/_view.php.
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
	<?php echo Html::encode($data->getAttributeLabel('bill_no')); ?>:
	<?php echo Html::encode($data->bill_no); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('image_file1')); ?>:
	<?php echo Html::encode($data->image_file1); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('image_file2')); ?>:
	<?php echo Html::encode($data->image_file2); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('image_file3')); ?>:
	<?php echo Html::encode($data->image_file3); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('type_id')); ?>:
	<?php echo Html::encode($data->type_id); ?>
	<br />
	<?php /*
	<?php echo Html::encode($data->getAttributeLabel('status')); ?>:
	<?php echo Html::encode($data->status); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('create_time')); ?>:
	<?php echo Html::encode($data->create_time); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('create_user_id')); ?>:
		<?php echo Html::encode(Gx::str($data->createUser)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('po_id')); ?>:
		<?php echo Html::encode(Gx::str($data->po)); ?>
	<br />
	*/ ?>

</div>