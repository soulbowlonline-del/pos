<?php
/**
 * Ported from protected/views/onlineOrder/_view.php.
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
	<?php echo Html::encode($data->getAttributeLabel('order_id')); ?>:
	<?php echo Html::encode($data->order_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('item_count')); ?>:
	<?php echo Html::encode($data->item_count); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('grand_total')); ?>:
	<?php echo Html::encode($data->grand_total); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('first_name')); ?>:
	<?php echo Html::encode($data->first_name); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('last_name')); ?>:
	<?php echo Html::encode($data->last_name); ?>
	<br />
	<?php /*
	<?php echo Html::encode($data->getAttributeLabel('street')); ?>:
	<?php echo Html::encode($data->street); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('city')); ?>:
	<?php echo Html::encode($data->city); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('telephone')); ?>:
	<?php echo Html::encode($data->telephone); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('zip_code')); ?>:
	<?php echo Html::encode($data->zip_code); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('country')); ?>:
	<?php echo Html::encode($data->country); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('delivery_slot')); ?>:
	<?php echo Html::encode($data->delivery_slot); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('ship_name')); ?>:
	<?php echo Html::encode($data->ship_name); ?>
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
	<?php echo Html::encode($data->create_user_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('updated_by')); ?>:
	<?php echo Html::encode($data->updated_by); ?>
	<br />
	*/ ?>

</div>