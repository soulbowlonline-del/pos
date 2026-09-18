<?php
/**
 * Ported from protected/views/itemDetail/_view.php.
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
	<?php echo Html::encode($data->getAttributeLabel('bar_code')); ?>:
	<?php echo Html::encode($data->bar_code); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('open_stock_qty')); ?>:
	<?php echo Html::encode($data->open_stock_qty); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('reorder_qty')); ?>:
	<?php echo Html::encode($data->reorder_qty); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('status')); ?>:
	<?php echo Html::encode($data->status); ?>
	<br />
	<?php /*
	<?php echo Html::encode($data->getAttributeLabel('type_id')); ?>:
	<?php echo Html::encode($data->type_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('create_time')); ?>:
	<?php echo Html::encode($data->create_time); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('tax_id')); ?>:
		<?php echo Html::encode(Gx::str($data->tax)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('create_user_id')); ?>:
		<?php echo Html::encode(Gx::str($data->createUser)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('updated_by')); ?>:
		<?php echo Html::encode(Gx::str($data->updatedBy)); ?>
	<br />
	*/ ?>

</div>