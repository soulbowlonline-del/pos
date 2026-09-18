<?php
/** @var yii\web\View $this */
/** @var app\models\PaymentMode $model */

use app\components\Access;
use app\components\Ui;
use app\widgets\ButtonGroup;
use app\widgets\GridView;
use app\models\PaymentMode;
use yii\helpers\Html;

$this->params['breadcrumbs'] = [
    ['label' => $model->label(2), 'url' => [Ui::to('paymentMode/index')]],
    'Manage',
];

$this->registerJs("
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$.pjax.reload({container: '#payment-mode-grid-pjax', data: $(this).serialize()});
	return false;
});
");
?>
<section class="content-header">
<h1><?= 'Manage' . ' : ' . Html::encode($model->label(2)) ?></h1>
<?= ButtonGroup::widget([
    'buttons' => $this->context->menu,
    'type' => 'success',
    'htmlOptions' => ['class' => 'pull-right'],
]) ?>
</section>

<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
         <div class="box-header"><h3 class="box-title"><?= Html::encode($model->label(2)) ?></h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">

<?= GridView::widget([
    'id' => 'payment-mode-grid',
    'type' => 'striped bordered condensed',
    'dataProvider' => $model->search(),
    'filterModel' => $model,
    'columns' => [
        'id',
        'title',
        [
            'attribute' => 'type_id',
            'value' => function ($data) { return PaymentMode::getTypeOptions($data->type_id); },
            'filter' => PaymentMode::getTypeOptions(),
        ],
        [
            'class' => yii\grid\ActionColumn::class,
            'header' => '<a>Actions</a>',
            'template' => '{view}{update}',
            'contentOptions' => ['style' => 'width:80px'],
            // Yii 1 hides a row button the role has no permission for, and
            // still answers the URL. Hiding it is reproduced here; refusing
            // the URL is not, because Yii 1 does not refuse it either.
            'visibleButtons' => [
                'view' => function () { return Access::check('paymentMode/view'); },
                'update' => function () { return Access::check('paymentMode/update'); },
            ],
            'urlCreator' => function ($action, $data) {
                return Ui::to('paymentMode/' . $action, ['id' => $data->id]);
            },
        ],
    ],
]) ?>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</section>
