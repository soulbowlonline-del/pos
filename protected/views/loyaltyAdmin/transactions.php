<?php
$this->pageTitle = 'Loyalty Transactions';
$this->breadcrumbs = array(
    'Loyalty Admin' => array('index'),
    'Transactions',
);

$typeOptions = array(
    'ALL'                            => 'All Types',
    LoyaltyTransaction::TYPE_REDEEM  => 'Redeem',
    LoyaltyTransaction::TYPE_EARN    => 'Earn',
    LoyaltyTransaction::TYPE_BONUS   => 'Bonus',
    LoyaltyTransaction::TYPE_EXPIRE  => 'Expire',
    LoyaltyTransaction::TYPE_ADJUST  => 'Adjust',
);

$totalCount  = isset($summary['cnt']) ? (int)$summary['cnt'] : 0;
$totalPoints = isset($summary['total_points']) ? floatval($summary['total_points']) : 0;
$totalAbs    = isset($summary['total_abs_points']) ? floatval($summary['total_abs_points']) : 0;
?>

<div class="loyalty-transactions content">

    <!-- Filters -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-filter"></i> Filter Transactions</h3>
                </div>
                <div class="box-body">
                    <form method="get" action="<?php echo Yii::app()->createUrl('loyaltyAdmin/transactions'); ?>" class="form-inline">
                        <div class="form-group" style="margin-right:15px;">
                            <label for="filter-from-date" style="margin-right:5px;">From</label>
                            <input type="date" class="form-control" id="filter-from-date" name="from_date"
                                   value="<?php echo CHtml::encode($fromDate); ?>">
                        </div>
                        <div class="form-group" style="margin-right:15px;">
                            <label for="filter-to-date" style="margin-right:5px;">To</label>
                            <input type="date" class="form-control" id="filter-to-date" name="to_date"
                                   value="<?php echo CHtml::encode($toDate); ?>">
                        </div>
                        <div class="form-group" style="margin-right:15px;">
                            <label for="filter-type" style="margin-right:5px;">Type</label>
                            <select class="form-control" id="filter-type" name="type">
                                <?php foreach ($typeOptions as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo $type === $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-right:5px;">
                            <i class="fa fa-search"></i> Filter
                        </button>
                        <a href="<?php echo Yii::app()->createUrl('loyaltyAdmin/transactions'); ?>" class="btn btn-default">
                            <i class="fa fa-refresh"></i> Reset
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary -->
    <div class="row">
        <div class="col-sm-4 col-xs-6">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3><?php echo number_format($totalCount); ?></h3>
                    <p>Transactions</p>
                </div>
                <div class="icon"><i class="fa fa-exchange"></i></div>
            </div>
        </div>
        <div class="col-sm-4 col-xs-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3><?php echo number_format($totalPoints); ?></h3>
                    <p>Net Points</p>
                </div>
                <div class="icon"><i class="fa fa-star"></i></div>
            </div>
        </div>
        <div class="col-sm-4 col-xs-6">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3><?php echo number_format($totalAbs); ?></h3>
                    <p>Total Points (Abs)</p>
                </div>
                <div class="icon"><i class="fa fa-calculator"></i></div>
            </div>
        </div>
    </div>

    <!-- Transaction Grid -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-exchange"></i> Loyalty Transactions
                        <?php if ($type !== 'ALL'): ?>
                            <span class="label label-default" style="margin-left:8px;"><?php echo CHtml::encode($typeOptions[$type]); ?></span>
                        <?php endif; ?>
                        <?php if ($fromDate !== '' || $toDate !== ''): ?>
                            <small class="text-muted" style="margin-left:8px;">
                                <?php
                                echo ($fromDate !== '' ? date('d M Y', strtotime($fromDate)) : 'Start')
                                   . ' — '
                                   . ($toDate !== '' ? date('d M Y', strtotime($toDate)) : 'Today');
                                ?>
                            </small>
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <?php $this->widget('bootstrap.widgets.TbGridView', array(
                            'id'           => 'loyalty-transactions-grid',
                            'type'         => 'striped bordered condensed',
                            'dataProvider' => $dataProvider,
                            'filter'       => null,
                            'pager'        => true,
                            'columns' => array(

                                /* Date */
                                array(
                                    'header'      => 'Date',
                                    'type'        => 'raw',
                                    'value'       => function($data) {
                                        return '<small class="text-muted">'
                                            . date('d M Y', strtotime($data->created_at)) . '</small><br>'
                                            . '<small class="text-muted">' . date('H:i', strtotime($data->created_at)) . '</small>';
                                    },
                                    'htmlOptions' => array('style' => 'width:100px; white-space:nowrap;'),
                                ),

                                /* Customer */
                                array(
                                    'header'      => 'Customer',
                                    'type'        => 'raw',
                                    'value'       => function($data) {
                                        $customer = $data->customer;
                                        if (!$customer) {
                                            return '<span class="text-muted">—</span>';
                                        }
                                        $name  = trim($customer->name) !== '' ? CHtml::encode($customer->name) : CHtml::encode($customer->contact_no);
                                        $link  = CHtml::link(
                                            $name,
                                            array('viewTransactions', 'id' => $data->customer_id),
                                            array('class' => 'text-info')
                                        );
                                        $phone = $customer->contact_no
                                            ? '<br><small class="text-muted"><i class="fa fa-phone"></i> ' . CHtml::encode($customer->contact_no) . '</small>'
                                            : '';
                                        return $link . $phone;
                                    },
                                ),

                                /* Type */
                                array(
                                    'header'      => 'Type',
                                    'type'        => 'raw',
                                    'value'       => function($data) {
                                        $map = array(
                                            'EARN'   => 'success',
                                            'REDEEM' => 'warning',
                                            'BONUS'  => 'info',
                                            'EXPIRE' => 'danger',
                                            'ADJUST' => 'primary',
                                        );
                                        $cls = isset($map[$data->transaction_type]) ? $map[$data->transaction_type] : 'default';
                                        return '<span class="label label-' . $cls . '" style="font-size:11px;">'
                                            . CHtml::encode($data->transaction_type) . '</span>';
                                    },
                                    'htmlOptions' => array('class' => 'text-center', 'style' => 'width:80px;'),
                                ),

                                /* Points */
                                array(
                                    'header'      => 'Points',
                                    'type'        => 'raw',
                                    'value'       => function($data) {
                                        $pts = floatval($data->points);
                                        $positive = ($data->transaction_type === 'EARN'   || $data->transaction_type === 'BONUS')
                                                 || ($data->transaction_type === 'ADJUST' && $pts > 0);
                                        $negative = ($data->transaction_type === 'REDEEM' || $data->transaction_type === 'EXPIRE')
                                                 || ($data->transaction_type === 'ADJUST' && $pts < 0);
                                        $cls  = $positive ? 'text-success' : ($negative ? 'text-danger' : 'text-muted');
                                        $sign = $positive ? '+' : '';
                                        return '<strong class="' . $cls . '">'
                                            . $sign . number_format($pts) . '</strong>';
                                    },
                                    'htmlOptions' => array('class' => 'text-center', 'style' => 'width:80px;'),
                                ),

                                /* Order # */
                                array(
                                    'header'      => 'Order #',
                                    'type'        => 'raw',
                                    'value'       => function($data) {
                                        if (!$data->order_id) {
                                            return '<span class="text-muted">—</span>';
                                        }
                                        return CHtml::link(
                                            '#' . $data->order_id,
                                            array('/order/view', 'id' => $data->order_id),
                                            array('class' => 'text-info')
                                        );
                                    },
                                    'htmlOptions' => array('class' => 'text-center', 'style' => 'width:80px;'),
                                ),

                                /* Description */
                                array(
                                    'header'      => 'Description',
                                    'type'        => 'raw',
                                    'value'       => function($data) {
                                        return '<small>' . CHtml::encode($data->description) . '</small>';
                                    },
                                ),

                            ),
                            'itemsCssClass' => 'table',
                            'summaryText'   => 'Showing {start}-{end} of {count} transactions',
                            'emptyText'     => 'No transactions found for the selected filters.',
                        )); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.loyalty-transactions .form-inline .form-group { margin-bottom: 5px; }
.loyalty-transactions .small-box { border-radius:5px; position:relative; display:block; margin-bottom:15px; box-shadow:0 1px 1px rgba(0,0,0,0.1); color:#fff; padding:10px; }
.loyalty-transactions .small-box .inner h3 { margin:0 0 5px; font-size:28px; font-weight:bold; }
.loyalty-transactions .small-box .inner p { margin:0; font-size:14px; }
.loyalty-transactions .small-box .icon { position:absolute; top:10px; right:15px; font-size:40px; color:rgba(0,0,0,0.15); }
.loyalty-transactions .bg-aqua   { background-color:#00c0ef !important; }
.loyalty-transactions .bg-green  { background-color:#00a65a !important; }
.loyalty-transactions .bg-yellow { background-color:#f39c12 !important; }
.grid-view table tbody td { vertical-align:middle; }
</style>
