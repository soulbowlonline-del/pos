<?php
/**
 * Ported from protected/views/item/_view.php.
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
	<?php echo Html::encode($data->getAttributeLabel('title')); ?>:
	<?php echo Html::encode($data->title); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('item_code')); ?>:
	<?php echo Html::encode($data->item_code); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('description')); ?>:
	<?php echo Html::encode($data->description); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('image_file')); ?>:
	<?php echo Html::encode($data->image_file); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('item_type')); ?>:
	<?php echo Html::encode($data->item_type); ?>
	<br />
	<?php /*
	<?php echo Html::encode($data->getAttributeLabel('status')); ?>:
	<?php echo Html::encode($data->status); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('type_id')); ?>:
	<?php echo Html::encode($data->type_id); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('is_tax')); ?>:
	<?php echo Html::encode($data->is_tax); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('is_discount')); ?>:
	<?php echo Html::encode($data->is_discount); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('category_id')); ?>:
		<?php echo Html::encode(Gx::str($data->category)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('sub_company_id')); ?>:
		<?php echo Html::encode(Gx::str($data->subCompany)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('company_id')); ?>:
		<?php echo Html::encode(Gx::str($data->company)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('create_time')); ?>:
	<?php echo Html::encode($data->create_time); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('create_user_id')); ?>:
		<?php echo Html::encode(Gx::str($data->createUser)); ?>
	<br />
	<?php echo Html::encode($data->getAttributeLabel('updated_by')); ?>:
		<?php echo Html::encode(Gx::str($data->updatedBy)); ?>
	<br />
	*/ ?>

</div>