<?php
/**
 * Ported from protected/views/loyaltyAdmin/reports.php.
 */

use app\components\Ui;
use app\models\CustomerLoyalty;
use yii\helpers\Html;
?>
<?php
$this->title = 'Loyalty Program Reports';
$this->params['breadcrumbs'] = [
    'Loyalty Admin' => ['index'],
    'Reports',
];
?>

<div class="loyalty-reports content">

    <!-- Summary Stats -->
    <div class="row">
        <div class="col-lg-3 col-sm-6 col-xs-12">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3><?php echo number_format($stats['active_customers'] ?: 0); ?></h3>
                    <p>Active Customers</p>
                </div>
                <div class="icon"><i class="fa fa-users"></i></div>
                <a href="<?php echo Ui::to('loyaltyAdmin/customers'); ?>" class="small-box-footer">
                    More info <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6 col-xs-12">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3><?php echo number_format($stats['total_earned'] ?: 0); ?></h3>
                    <p>Total Points Earned</p>
                </div>
                <div class="icon"><i class="fa fa-plus-circle"></i></div>
                <a href="<?php echo Ui::to('loyaltyAdmin/reports'); ?>" class="small-box-footer">
                    More info <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6 col-xs-12">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3><?php echo number_format($stats['total_redeemed'] ?: 0); ?></h3>
                    <p>Total Points Redeemed</p>
                </div>
                <div class="icon"><i class="fa fa-minus-circle"></i></div>
                <a href="<?php echo Ui::to('loyaltyAdmin/reports'); ?>" class="small-box-footer">
                    More info <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6 col-xs-12">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3>
                        <?php
                        $redemptionRate = 0;
                        if ($stats['total_earned'] > 0) {
                            $redemptionRate = ($stats['total_redeemed'] / $stats['total_earned']) * 100;
                        }
                        echo number_format($redemptionRate, 1) . '%';
                        ?>
                    </h3>
                    <p>Redemption Rate</p>
                </div>
                <div class="icon"><i class="fa fa-line-chart"></i></div>
                <a href="<?php echo Ui::to('loyaltyAdmin/reports'); ?>" class="small-box-footer">
                    More info <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Transactions -->
        <div class="col-md-8">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-exchange"></i> Recent Loyalty Transactions</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body no-padding">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th style="width:130px;">Date</th>
                                    <th>Customer</th>
                                    <th style="width:80px;">Type</th>
                                    <th style="width:70px;" class="text-right">Points</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentTransactions)): ?>
                                    <?php foreach ($recentTransactions as $trans): ?>
                                        <tr>
                                            <td class="text-nowrap">
                                                <small class="text-muted"><?php echo date('M j, Y', strtotime($trans->created_at)); ?></small><br>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($trans->created_at)); ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                $custName  = isset($trans->customer) ? trim($trans->customer->name) : '';
                                                $custPhone = isset($trans->customer) ? $trans->customer->contact_no : '';
                                                $displayName = $custName !== '' ? Html::encode($custName) : ($custPhone ? Html::encode($custPhone) : '<em class="text-muted">Unknown</em>');
                                                ?>
                                                <strong><?php echo $displayName; ?></strong>
                                                <?php if ($custName !== '' && $custPhone): ?>
                                                    <br><small class="text-muted"><?php echo Html::encode($custPhone); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $typeBadge = [
                                                    'EARN'   => 'success',
                                                    'REDEEM' => 'warning',
                                                    'BONUS'  => 'info',
                                                    'EXPIRE' => 'danger',
                                                    'ADJUST' => 'primary',
                                                ];
                                                $badge = isset($typeBadge[$trans->transaction_type]) ? $typeBadge[$trans->transaction_type] : 'default';
                                                ?>
                                                <span class="label label-<?php echo $badge; ?>" style="font-size:11px;">
                                                    <?php echo $trans->transaction_type; ?>
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <?php
                                                $pts = floatval($trans->points);
                                                // REDEEM points are stored positive in DB but represent a deduction
                                                $effectivePositive = ($trans->transaction_type === 'EARN' || $trans->transaction_type === 'BONUS')
                                                    || ($trans->transaction_type === 'ADJUST' && $pts > 0);
                                                $effectiveNegative = ($trans->transaction_type === 'REDEEM' || $trans->transaction_type === 'EXPIRE')
                                                    || ($trans->transaction_type === 'ADJUST' && $pts < 0);
                                                $ptColor = $effectivePositive ? 'text-success' : ($effectiveNegative ? 'text-danger' : 'text-muted');
                                                $sign    = $effectivePositive ? '+' : ($effectiveNegative ? '-' : '');
                                                ?>
                                                <strong class="<?php echo $ptColor; ?>">
                                                    <?php echo $sign . number_format(abs($pts)); ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <small><?php echo Html::encode($trans->description); ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted" style="padding:30px;">
                                            <i class="fa fa-info-circle fa-2x"></i><br>
                                            No recent transactions found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="col-md-4">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-trophy"></i> Top Loyalty Customers</h3>
                </div>
                <div class="box-body no-padding">
                    <?php
                    $query = CustomerLoyalty::find()->alias('t');
                    $query->with = ['customer'];
                    $query->orderBy(['total_points' => SORT_DESC]);
                    $query->limit(10);
                    $query->andWhere('total_points > 0');
                    $topCustomers = $query->all();
                    ?>

                    <?php if (!empty($topCustomers)): ?>
                        <ul class="list-group list-group-unbordered" style="margin-bottom:0;">
                            <?php
                            $rankColors = ['#f39c12', '#95a5a6', '#cd7f32']; // gold, silver, bronze
                            foreach ($topCustomers as $index => $loyalty):
                                $cName  = isset($loyalty->customer) ? trim($loyalty->customer->name) : '';
                                $cPhone = isset($loyalty->customer) ? $loyalty->customer->contact_no : '';
                                $displayLabel = $cName !== '' ? Html::encode($cName) : ($cPhone ? Html::encode($cPhone) : 'Unknown');
                                $badgeStyle = $index < 3
                                    ? 'background-color:' . $rankColors[$index] . ';color:#fff;'
                                    : 'background-color:#d2d6de;color:#333;';
                            ?>
                                <li class="list-group-item" style="padding: 10px 15px;">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <span class="badge" style="<?php echo $badgeStyle; ?> min-width:24px; text-align:center;">
                                            <?php echo $index + 1; ?>
                                        </span>
                                        <div style="flex:1; min-width:0;">
                                            <strong style="display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                                <?php echo $displayLabel; ?>
                                            </strong>
                                            <?php if ($cName !== '' && $cPhone): ?>
                                                <small class="text-muted"><?php echo Html::encode($cPhone); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-right" style="white-space:nowrap;">
                                            <strong class="text-success"><?php echo number_format($loyalty->total_points); ?></strong>
                                            <br><small class="text-muted">pts</small>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="text-center text-muted" style="padding:30px;">
                            <i class="fa fa-users fa-2x"></i><br>
                            No customers with points found.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Point Distribution & Transaction Breakdown -->
    <div class="row">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-bar-chart"></i> Point Distribution</h3>
                </div>
                <div class="box-body">
                    <?php
                    $ranges = [
                        '0–50'      => (new \yii\db\Query())->select('COUNT(*)')->from('tbl_customer_loyalty')->where('total_points BETWEEN 0 AND 50')->scalar(),
                        '51–100'    => (new \yii\db\Query())->select('COUNT(*)')->from('tbl_customer_loyalty')->where('total_points BETWEEN 51 AND 100')->scalar(),
                        '101–500'   => (new \yii\db\Query())->select('COUNT(*)')->from('tbl_customer_loyalty')->where('total_points BETWEEN 101 AND 500')->scalar(),
                        '501–1000'  => (new \yii\db\Query())->select('COUNT(*)')->from('tbl_customer_loyalty')->where('total_points BETWEEN 501 AND 1000')->scalar(),
                        '1000+'     => (new \yii\db\Query())->select('COUNT(*)')->from('tbl_customer_loyalty')->where('total_points > 1000')->scalar(),
                    ];
                    $maxCount = max(array_values($ranges)) ?: 1;
                    ?>

                    <?php foreach ($ranges as $range => $count): ?>
                        <div class="progress-group" style="margin-bottom:18px;">
                            <span class="progress-text" style="font-weight:600;"><?php echo $range; ?> pts</span>
                            <span class="pull-right"><b><?php echo $count; ?></b> customers</span>
                            <div class="progress progress-sm" style="margin-top:5px;">
                                <div class="progress-bar progress-bar-primary" style="width:<?php echo round(($count / $maxCount) * 100); ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-pie-chart"></i> Transaction Breakdown (Last 30 Days)</h3>
                </div>
                <div class="box-body">
                    <?php
                    $transactionTypes = ['EARN', 'REDEEM', 'BONUS', 'ADJUST', 'EXPIRE'];
                    $transactionCounts = [];
                    foreach ($transactionTypes as $type) {
                        $transactionCounts[$type] = (new \yii\db\Query())->select('COUNT(*)')
                            ->from('tbl_loyalty_transactions')
                            ->where('transaction_type = :type AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)', [':type' => $type])
                            ->scalar();
                    }
                    $maxTransCount = max(array_values($transactionCounts)) ?: 1;
                    $transColors = [
                        'EARN'   => 'progress-bar-success',
                        'REDEEM' => 'progress-bar-warning',
                        'BONUS'  => 'progress-bar-info',
                        'ADJUST' => 'progress-bar-primary',
                        'EXPIRE' => 'progress-bar-danger',
                    ];
                    $transIcons = [
                        'EARN'   => 'fa-plus-circle text-success',
                        'REDEEM' => 'fa-minus-circle text-warning',
                        'BONUS'  => 'fa-gift text-info',
                        'ADJUST' => 'fa-sliders text-primary',
                        'EXPIRE' => 'fa-clock-o text-danger',
                    ];
                    ?>

                    <?php foreach ($transactionCounts as $type => $count): ?>
                        <div class="progress-group" style="margin-bottom:18px;">
                            <span class="progress-text" style="font-weight:600;">
                                <i class="fa <?php echo isset($transIcons[$type]) ? $transIcons[$type] : 'fa-circle'; ?>"></i>
                                <?php echo $type; ?>
                            </span>
                            <span class="pull-right"><b><?php echo $count; ?></b> transactions</span>
                            <div class="progress progress-sm" style="margin-top:5px;">
                                <div class="progress-bar <?php echo isset($transColors[$type]) ? $transColors[$type] : 'progress-bar-default'; ?>"
                                     style="width:<?php echo round(($count / $maxTransCount) * 100); ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Summary -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-calendar"></i> Monthly Summary</h3>
                    <div class="box-tools pull-right">
                        <a href="<?php echo Ui::to('loyaltyAdmin/exportReport'); ?>" class="btn btn-success btn-sm">
                            <i class="fa fa-download"></i> Export
                        </a>
                    </div>
                </div>
                <div class="box-body no-padding">
                    <?php
                    $monthlyData = [];
                    for ($i = 5; $i >= 0; $i--) {
                        $month     = date('Y-m', strtotime("-{$i} months"));
                        $monthName = date('M Y', strtotime("-{$i} months"));

                        $earned = (new \yii\db\Query())->select('SUM(points)')
                            ->from('tbl_loyalty_transactions')
                            ->where('transaction_type = "EARN" AND DATE_FORMAT(created_at, "%Y-%m") = :month', [':month' => $month])
                            ->scalar();

                        $redeemed = (new \yii\db\Query())->select('SUM(ABS(points))')
                            ->from('tbl_loyalty_transactions')
                            ->where('transaction_type = "REDEEM" AND DATE_FORMAT(created_at, "%Y-%m") = :month', [':month' => $month])
                            ->scalar();

                        $monthlyData[] = [
                            'month'    => $monthName,
                            'earned'   => $earned ?: 0,
                            'redeemed' => $redeemed ?: 0,
                        ];
                    }
                    ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" style="margin-bottom:0;">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th class="text-center">
                                        <i class="fa fa-plus-circle text-success"></i> Points Earned
                                    </th>
                                    <th class="text-center">
                                        <i class="fa fa-minus-circle text-warning"></i> Points Redeemed
                                    </th>
                                    <th class="text-center">Net Points</th>
                                    <th class="text-center">Redemption Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthlyData as $data): ?>
                                    <tr>
                                        <td><strong><?php echo $data['month']; ?></strong></td>
                                        <td class="text-center">
                                            <span class="text-success"><strong>+<?php echo number_format($data['earned']); ?></strong></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-warning"><strong><?php echo number_format($data['redeemed']); ?></strong></span>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $net   = $data['earned'] - $data['redeemed'];
                                            $nCol  = $net >= 0 ? 'text-success' : 'text-danger';
                                            $nSign = $net >= 0 ? '+' : '';
                                            ?>
                                            <strong class="<?php echo $nCol; ?>"><?php echo $nSign . number_format($net); ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $rate = $data['earned'] > 0 ? round(($data['redeemed'] / $data['earned']) * 100, 1) : 0;
                                            $rCol = $rate > 80 ? 'text-danger' : ($rate > 50 ? 'text-warning' : 'text-success');
                                            ?>
                                            <span class="<?php echo $rCol; ?>"><?php echo $rate; ?>%</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ── Progress groups ──────────────────────────────────── */
.progress.progress-sm { height: 8px; border-radius: 4px; margin: 4px 0 0; }
.progress-bar { border-radius: 4px; }

/* ── Table tweaks ─────────────────────────────────────── */
.table > thead > tr > th {
    background-color: #f5f5f5;
    border-bottom: 2px solid #ddd;
    font-size: 12px;
    text-transform: uppercase;
    color: #555;
    letter-spacing: .03em;
}
.text-nowrap { white-space: nowrap; }

/* ── Top customers list ───────────────────────────────── */
.list-group-unbordered > .list-group-item {
    border-left: 0;
    border-right: 0;
    border-radius: 0;
}
.list-group-unbordered > .list-group-item:first-child { border-top: 0; }

/* ── Box header icons ─────────────────────────────────── */
.box-title .fa { margin-right: 5px; opacity: .75; }
</style>

<script>
$(document).ready(function() {
    setTimeout(function() { location.reload(); }, 300000);
});
</script>
