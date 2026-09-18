<?php
/** @var yii\web\View $this */
/** @var app\models\PaymentMode $model */

use app\components\Ui;
use yii\helpers\Html;

$this->params['breadcrumbs'] = [
    ['label' => $model->label(2), 'url' => [Ui::to('paymentMode/index')]],
    ['label' => (string) $model, 'url' => [Ui::to('paymentMode/view', ['id' => $model->id])]],
    'Update',
];
?>
<section class="content-header">

<h1><?= 'Update' . ' ' . Html::encode($model->label()) . ' : ' . Html::encode((string) $model) ?></h1>
</section>

<?= $this->render('_form', ['model' => $model]) ?>
