<?php
/** @var yii\web\View $this */
/** @var app\models\PaymentMode $model */

use app\components\Ui;
use app\widgets\ButtonGroup;
use app\widgets\DetailView;
use yii\helpers\Html;

$this->params['breadcrumbs'] = [
    ['label' => $model->label(2), 'url' => [Ui::to('paymentMode/index')]],
    (string) $model,
];
?>

<section class="content">
<div class="page-header">
<h1 class="pull-left"><?= Html::encode((string) $model) ?></h1>

<?= ButtonGroup::widget([
    'buttons' => $this->context->menu,
    'type' => 'success',
    'htmlOptions' => ['class' => 'pull-right'],
]) ?>
<div class="clearfix"></div>

</div>

<?= DetailView::widget([
    'data' => $model,
    'attributes' => [
        'id',
        'title',
        [
            'attribute' => 'type_id',
            'format' => 'raw',
            'value' => $model->getTypeOptions($model->type_id),
        ],
        'create_time',
        'update_time',
        [
            'attribute' => 'createUser',
            'format' => 'raw',
            'value' => $model->createUser !== null
                ? Html::a(Html::encode((string) $model->createUser), Ui::to('user/view', ['id' => $model->createUser->id]))
                : null,
        ],
        [
            'attribute' => 'updatedBy',
            'format' => 'raw',
            'value' => $model->updatedBy !== null
                ? Html::a(Html::encode((string) $model->updatedBy), Ui::to('user/view', ['id' => $model->updatedBy->id]))
                : null,
        ],
    ],
]) ?>

</section>
