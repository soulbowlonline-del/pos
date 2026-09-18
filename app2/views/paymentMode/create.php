<?php
/** @var yii\web\View $this */
/** @var app\models\PaymentMode $model */

use app\components\Ui;
use yii\helpers\Html;

$this->params['breadcrumbs'] = [
    ['label' => $model->label(2), 'url' => [Ui::to('paymentMode/index')]],
    'Create',
];
?>
<section class="content">
<div class="page-header">
<h1><?= 'Create' . ' ' . Html::encode($model->label()) ?></h1>
</div>
<?= $this->render('_form', ['model' => $model, 'buttons' => 'create']) ?>
</section>
