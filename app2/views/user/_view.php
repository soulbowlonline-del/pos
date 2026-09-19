<?php
/**
 * Ported from protected/views/user/_view.php.
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
	<?php echo Html::encode($data->getAttributeLabel('full_name')); ?>:
	<?php echo Html::encode($data->full_name); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('email')); ?>:
	<?php echo Html::encode($data->email); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('password')); ?>:
	<?php echo Html::encode($data->password); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('lat')); ?>:
	<?php echo Html::encode($data->lat); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('long')); ?>:
	<?php echo Html::encode($data->long); ?>
	<br />
	<?php /*
	<?php echo Html::encode($data->getAttributeLabel('contact_no')); ?>:
	<?php echo Html::encode($data->contact_no); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('date_of_birth')); ?>:
	<?php echo Html::encode($data->date_of_birth); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('about_me')); ?>:
	<?php echo Html::encode($data->about_me); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('address')); ?>:
	<?php echo Html::encode($data->address); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('postal_code')); ?>:
	<?php echo Html::encode($data->postal_code); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('country')); ?>:
	<?php echo Html::encode($data->country); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('city')); ?>:
	<?php echo Html::encode($data->city); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('state')); ?>:
	<?php echo Html::encode($data->state); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('lang')); ?>:
	<?php echo Html::encode($data->lang); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('image_file')); ?>:
	<?php echo Html::encode($data->image_file); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('is_passenger')); ?>:
	<?php echo Html::encode($data->is_passenger); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('is_dispatcher')); ?>:
	<?php echo Html::encode($data->is_dispatcher); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('is_driver')); ?>:
	<?php echo Html::encode($data->is_driver); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('role_id')); ?>:
	<?php echo Html::encode($data->role_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('state_id')); ?>:
	<?php echo Html::encode($data->state_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('type_id')); ?>:
	<?php echo Html::encode($data->type_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('last_visit_time')); ?>:
	<?php echo Html::encode($data->last_visit_time); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('last_action_time')); ?>:
	<?php echo Html::encode($data->last_action_time); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('last_password_change')); ?>:
	<?php echo Html::encode($data->last_password_change); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('activation_key')); ?>:
	<?php echo Html::encode($data->activation_key); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('is_active')); ?>:
	<?php echo Html::encode($data->is_active); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('login_error_count')); ?>:
	<?php echo Html::encode($data->login_error_count); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('create_user_id')); ?>:
	<?php echo Html::encode($data->create_user_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('create_time')); ?>:
	<?php echo Html::encode($data->create_time); ?>
	<br />
	*/ ?>

</div>