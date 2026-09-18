<?php
/** @var yii\web\View $this */
/** @var app\models\PaymentMode $data */

use app\components\Ui;
use yii\helpers\Html;
?>
<div class="view">

	<b><?= Html::encode($data->getAttributeLabel('id')) ?>:</b>
	<?= Html::a(Html::encode($data->id), Ui::to('paymentMode/view', ['id' => $data->id])) ?>
	<br />

	<?= Html::encode($data->getAttributeLabel('id')) ?>:
	<?= Html::encode($data->id) ?>
	<br />
	<?= Html::encode($data->getAttributeLabel('title')) ?>:
	<?= Html::encode($data->title) ?>
	<br />
	<?= Html::encode($data->getAttributeLabel('type_id')) ?>:
	<?= Html::encode($data->type_id) ?>
	<br />
	<?= Html::encode($data->getAttributeLabel('status')) ?>:
	<?= Html::encode($data->status) ?>
	<br />
	<?= Html::encode($data->getAttributeLabel('create_time')) ?>:
	<?= Html::encode($data->create_time) ?>
	<br />
	<?= Html::encode($data->getAttributeLabel('update_time')) ?>:
	<?= Html::encode($data->update_time) ?>
	<br />

</div>
