<?php
/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use app\components\Ui;
use app\widgets\GridView;
use app\models\PaymentMode;
use app\models\User;
?>
<?= GridView::widget([
    'id' => 'payment-mode-grid',
    'type' => 'bordered',
    'dataProvider' => $dataProvider,
    'columns' => [
        'id',
        'title',
        [
            'attribute' => 'type_id',
            'value' => function ($data) { return PaymentMode::getTypeOptions($data->type_id); },
            'filter' => PaymentMode::getTypeOptions(),
        ],
        [
            'attribute' => 'status',
            'value' => function ($data) { return PaymentMode::getStatusOptions($data->status); },
            'filter' => PaymentMode::getStatusOptions(),
        ],
        'update_time',
        [
            'attribute' => 'updated_by',
            'value' => function ($data) {
                return $data->updatedBy !== null ? (string) $data->updatedBy : null;
            },
        ],
        [
            'class' => yii\grid\ActionColumn::class,
            'urlCreator' => function ($action, $data) {
                return Ui::to('paymentMode/' . $action, ['id' => $data->id]);
            },
        ],
    ],
]) ?>
