<?php
/**
 * Ported from protected/views/orderRefund/_view.php.
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
	<?php echo Html::encode($data->getAttributeLabel('qty')); ?>:
	<?php echo Html::encode($data->qty); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('discount')); ?>:
	<?php echo Html::encode($data->discount); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('discount_amt')); ?>:
	<?php echo Html::encode($data->discount_amt); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('total_amt')); ?>:
	<?php echo Html::encode($data->total_amt); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('paid_amt')); ?>:
	<?php echo Html::encode($data->paid_amt); ?>
	<br />
	<?php /*
	<?php echo Html::encode($data->getAttributeLabel('status')); ?>:
	<?php echo Html::encode($data->status); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('type_id')); ?>:
	<?php echo Html::encode($data->type_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('city_id')); ?>:
	<?php echo Html::encode($data->city_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('state_id')); ?>:
	<?php echo Html::encode($data->state_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('country_id')); ?>:
	<?php echo Html::encode($data->country_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('address')); ?>:
	<?php echo Html::encode($data->address); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('note')); ?>:
	<?php echo Html::encode($data->note); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('create_time')); ?>:
	<?php echo Html::encode($data->create_time); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('update_time')); ?>:
	<?php echo Html::encode($data->update_time); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('order_id')); ?>:
	<?php echo Html::encode($data->order_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('customer_id')); ?>:
	<?php echo Html::encode($data->customer_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('updated_by')); ?>:
	<?php echo Html::encode($data->updated_by); ?>
	<br />
	*/ ?>

</div>