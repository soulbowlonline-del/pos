<?php
/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use app\models\PaymentMode;
use yii\helpers\Html;

$this->params['breadcrumbs'] = [
    PaymentMode::label(2),
    'Index',
];
?>

<div class="page-header">
<h1><?= Html::encode(PaymentMode::label(2)) ?></h1>
</div>

<?= $this->render('_list', ['dataProvider' => $dataProvider]) ?>
