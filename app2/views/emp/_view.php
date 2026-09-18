<?php
/**
 * Ported from protected/views/emp/_view.php.
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
	<?php echo Html::encode($data->getAttributeLabel('name')); ?>:
	<?php echo Html::encode($data->name); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('email')); ?>:
	<?php echo Html::encode($data->email); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('contact_no')); ?>:
	<?php echo Html::encode($data->contact_no); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('gender_id')); ?>:
	<?php echo Html::encode($data->gender_id); ?>
	<br />
	<?php /*
	<?php echo Html::encode($data->getAttributeLabel('date_of_birth')); ?>:
	<?php echo Html::encode($data->date_of_birth); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('date_of_joining')); ?>:
	<?php echo Html::encode($data->date_of_joining); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('permanent_address')); ?>:
	<?php echo Html::encode($data->permanent_address); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('temp_address')); ?>:
	<?php echo Html::encode($data->temp_address); ?>
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
	<?php echo Html::encode($data->getAttributeLabel('designation_id')); ?>:
		<?php echo Html::encode(Gx::str($data->designation)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('create_user_id')); ?>:
		<?php echo Html::encode(Gx::str($data->createUser)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('updated_by')); ?>:
		<?php echo Html::encode(Gx::str($data->updatedBy)); ?>
	<br />
	*/ ?>

</div>