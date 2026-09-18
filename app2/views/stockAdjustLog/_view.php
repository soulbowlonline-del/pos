<?php
/**
 * Ported from protected/views/stockAdjustLog/_view.php.
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
	<?php echo Html::encode($data->getAttributeLabel('date')); ?>:
	<?php echo Html::encode($data->date); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('item_detail_id')); ?>:
		<?php echo Html::encode(Gx::str($data->itemDetail)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('item_id')); ?>:
		<?php echo Html::encode(Gx::str($data->item)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('mrp')); ?>:
	<?php echo Html::encode($data->mrp); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('current_stock')); ?>:
	<?php echo Html::encode($data->current_stock); ?>
	<br />
	<?php /*
	<?php echo Html::encode($data->getAttributeLabel('actual_stock')); ?>:
	<?php echo Html::encode($data->actual_stock); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('adjusted')); ?>:
	<?php echo Html::encode($data->adjusted); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('outlet_id')); ?>:
		<?php echo Html::encode(Gx::str($data->outlet)); ?>
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
	*/ ?>

</div>