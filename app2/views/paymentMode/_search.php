<?php
/** @var yii\web\View $this */
/** @var app\models\PaymentMode $model */

use app\components\Ui;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\models\PaymentMode;

$form = ActiveForm::begin([
    'action' => Ui::to('paymentMode/search'),
    'method' => 'get',
    'type' => 'horizontal',
]);
?>
<?= $form->textFieldRow($model, 'id') ?>
<?= $form->textFieldRow($model, 'title', ['maxlength' => 255]) ?>
<?= $form->dropDownListRow($model, 'type_id', PaymentMode::getTypeOptions(), ['prompt' => '']) ?>

<div class="form-actions">
<?= Button::widget(['buttonType' => 'submit', 'type' => 'primary', 'label' => 'Search']) ?>
</div>
<?php ActiveForm::end(); ?>
