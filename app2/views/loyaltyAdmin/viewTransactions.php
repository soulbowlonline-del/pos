<?php
/**
 * Ported from protected/views/loyaltyAdmin/viewTransactions.php.
 */

use app\components\Gx;
use app\widgets\GridView;
use yii\helpers\Html;
?>
<?php
$customer    = $loyalty->customer;
$displayName = ($customer && trim($customer->name) !== '') ? Html::encode($customer->name) : ($customer ? Html::encode($customer->contact_no) : 'Unknown');
$pts         = (int)$loyalty->total_points;

if ($pts >= 5000)       { $tier = 'Gold';   $tierClass = 'gold';   $tierIcon = 'fa-trophy'; }
elseif ($pts >= 1000)   { $tier = 'Silver'; $tierClass = 'silver'; $tierIcon = 'fa-star'; }
elseif ($pts > 0)       { $tier = 'Bronze'; $tierClass = 'bronze'; $tierIcon = 'fa-star-o'; }
else                    { $tier = 'None';   $tierClass = 'none';   $tierIcon = 'fa-circle-o'; }

$this->title = 'Transactions - ' . strip_tags($displayName);
$this->params['breadcrumbs'] = [
    'Loyalty Admin' => ['index'],
    'Customers'     => ['customers'],
    'Transactions',
];
?>

<div class="loyalty-view-transactions content">

    <!-- Customer Summary -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-user"></i> <?php echo $displayName; ?>
                        <span class="loyalty-tier <?php echo $tierClass; ?>" style="margin-left:10px;">
                            <i class="fa <?php echo $tierIcon; ?>"></i> <?php echo $tier; ?>
                        </span>
                    </h3>
                    <div class="box-tools pull-right">
                        <?php echo Html::a(
                            '<i class="fa fa-pencil"></i> Adjust Points', Gx::url(['adjustPoints', 'customer_id' => $loyalty->customer_id]),
                            ['class' => 'btn btn-warning btn-sm']
                        ); ?>
                        <?php echo Html::a(
                            '<i class="fa fa-arrow-left"></i> Back to Customers', Gx::url(['customers']),
                            ['class' => 'btn btn-default btn-sm']
                        ); ?>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-3 text-center">
                            <div class="description-block border-right">
                                <span class="description-header" style="font-size:24px; color:#00a65a; font-weight:700;">
                                    <?php echo number_format($pts); ?>
                                </span>
                                <p class="description-text">Current Points</p>
                            </div>
                        </div>
                        <div class="col-sm-3 text-center">
                            <div class="description-block border-right">
                                <span class="description-header" style="font-size:20px; color:#00a65a;">
                                    +<?php echo number_format($loyalty->lifetime_earned); ?>
                                </span>
                                <p class="description-text">Lifetime Earned</p>
                            </div>
                        </div>
                        <div class="col-sm-3 text-center">
                            <div class="description-block border-right">
                                <span class="description-header" style="font-size:20px; color:#f39c12;">
                                    <?php echo number_format($loyalty->lifetime_redeemed); ?>
                                </span>
                                <p class="description-text">Lifetime Redeemed</p>
                            </div>
                        </div>
                        <div class="col-sm-3 text-center">
                            <div class="description-block">
                                <?php if ($customer && $customer->contact_no): ?>
                                    <span class="description-header" style="font-size:16px;">
                                        <i class="fa fa-phone text-muted"></i> <?php echo Html::encode($customer->contact_no); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($customer && $customer->email): ?>
                                    <p class="description-text">
                                        <i class="fa fa-envelope text-muted"></i> <?php echo Html::encode($customer->email); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (Yii::$app->user->hasFlash('success')): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <i class="fa fa-check"></i> <?php echo Yii::$app->user->getFlash('success'); ?>
        </div>
    <?php endif; ?>

    <!-- Transaction Grid -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-exchange"></i> Transaction History</h3>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <?php echo GridView::widget([
                            'id'           => 'transaction-grid',
                            'type'         => 'striped bordered condensed',
                            'dataProvider' => $dataProvider,
                            'filter'       => null,
                            'pager'        => true,
                            'columns' => [

                                /* Date */
                                [
                                    'header'      => 'Date',
                                    'format' => 'raw',
                                    'value'       => function($data) {
                                        return '<small class="text-muted">'
                                            . date('d M Y', strtotime($data->created_at)) . '</small><br>'
                                            . '<small class="text-muted">' . date('H:i', strtotime($data->created_at)) . '</small>';
                                    },
                                    'htmlOptions' => ['style' => 'width:100px; white-space:nowrap;'],
                                ],

                                /* Type */
                                [
                                    'header'      => 'Type',
                                    'format' => 'raw',
                                    'value'       => function($data) {
                                        $map = [
                                            'EARN'   => 'success',
                                            'REDEEM' => 'warning',
                                            'BONUS'  => 'info',
                                            'EXPIRE' => 'danger',
                                            'ADJUST' => 'primary',
                                        ];
                                        $cls = isset($map[$data->transaction_type]) ? $map[$data->transaction_type] : 'default';
                                        return '<span class="label label-' . $cls . '" style="font-size:11px;">'
                                            . Html::encode($data->transaction_type) . '</span>';
                                    },
                                    'htmlOptions' => ['class' => 'text-center', 'style' => 'width:80px;'],
                                ],

                                /* Points */
                                [
                                    'header'      => 'Points',
                                    'format' => 'raw',
                                    'value'       => function($data) {
                                        $pts = floatval($data->points);
                                        $positive = ($data->transaction_type === 'EARN'   || $data->transaction_type === 'BONUS')
                                                 || ($data->transaction_type === 'ADJUST' && $pts > 0);
                                        $negative = ($data->transaction_type === 'REDEEM' || $data->transaction_type === 'EXPIRE')
                                                 || ($data->transaction_type === 'ADJUST' && $pts < 0);
                                        $cls  = $positive ? 'text-success' : ($negative ? 'text-danger' : 'text-muted');
                                        $sign = $positive ? '+' : ($negative ? '' : ''); // negative sign already in value
                                        return '<strong class="' . $cls . '">'
                                            . $sign . number_format($pts) . '</strong>';
                                    },
                                    'htmlOptions' => ['class' => 'text-center', 'style' => 'width:80px;'],
                                ],

                                /* Order # */
                                [
                                    'header'      => 'Order #',
                                    'format' => 'raw',
                                    'value'       => function($data) {
                                        if (!$data->order_id) {
                                            return '<span class="text-muted">—</span>';
                                        }
                                        return Html::a(
                                            '#' . $data->order_id, Gx::url(['/order/view', 'id' => $data->order_id]),
                                            ['class' => 'text-info']
                                        );
                                    },
                                    'htmlOptions' => ['class' => 'text-center', 'style' => 'width:80px;'],
                                ],

                                /* Description */
                                [
                                    'header'      => 'Description',
                                    'format' => 'raw',
                                    'value'       => function($data) {
                                        return '<small>' . Html::encode($data->description) . '</small>';
                                    },
                                ],

                            ],
                            'itemsCssClass' => 'table',
                            'summaryText'   => 'Showing {start}-{end} of {count} transactions',
                            'emptyText'     => 'No transactions found for this customer.',
                        ]); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.loyalty-tier { display:inline-block; padding:3px 8px; border-radius:3px; font-size:12px; font-weight:700; white-space:nowrap; }
.loyalty-tier.gold   { background-color:#FFD700; color:#333; }
.loyalty-tier.silver { background-color:#C0C0C0; color:#333; }
.loyalty-tier.bronze { background-color:#cd7f32; color:#fff; }
.loyalty-tier.none   { background-color:#d2d6de; color:#555; }
.description-block { margin-bottom:0; }
.description-block .description-text { text-transform:uppercase; font-size:11px; color:#aaa; margin:4px 0 0; }
.description-block .description-header { display:block; }
.border-right { border-right:1px solid #f4f4f4; }
.grid-view table tbody td { vertical-align:middle; }
</style>
