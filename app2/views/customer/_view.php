<?php
/**
 * Ported from protected/views/customer/_view.php.
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
	<?php echo Html::encode($data->getAttributeLabel('name')); ?>:
	<?php echo Html::encode($data->name); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('email')); ?>:
	<?php echo Html::encode($data->email); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('fax')); ?>:
	<?php echo Html::encode($data->fax); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('address')); ?>:
	<?php echo Html::encode($data->address); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('city_id')); ?>:
	<?php echo Html::encode($data->city_id); ?>
	<br />
	<?php /*
	<?php echo Html::encode($data->getAttributeLabel('state_id')); ?>:
	<?php echo Html::encode($data->state_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('country_id')); ?>:
	<?php echo Html::encode($data->country_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('zip_code')); ?>:
	<?php echo Html::encode($data->zip_code); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('opening_balance')); ?>:
	<?php echo Html::encode($data->opening_balance); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('credit_limit')); ?>:
	<?php echo Html::encode($data->credit_limit); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('payment_days')); ?>:
	<?php echo Html::encode($data->payment_days); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('contact_no')); ?>:
	<?php echo Html::encode($data->contact_no); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('status')); ?>:
	<?php echo Html::encode($data->status); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('type_id')); ?>:
	<?php echo Html::encode($data->type_id); ?>
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