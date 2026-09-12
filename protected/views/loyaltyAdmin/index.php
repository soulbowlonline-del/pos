<?php
$this->pageTitle = 'Loyalty Program Dashboard';
$this->breadcrumbs = array(
    'Loyalty Admin' => array('index'),
    'Dashboard',
);
?>

<div class="loyalty-dashboard">
    <div class="page-header">
        <h1>
            <i class="fa fa-star"></i> Loyalty Program Dashboard
            <small>Manage customer loyalty program</small>
        </h1>
    </div>

    <div class="row">
        <!-- Quick Stats -->
        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-aqua">
                <span class="info-box-icon"><i class="fa fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Active Customers</span>
                    <span class="info-box-number">
                        <?php 
                        $criteria = new CDbCriteria();
                        $criteria->condition = 'total_points > 0';
                        echo CustomerLoyalty::model()->count($criteria);
                        ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-green">
                <span class="info-box-icon"><i class="fa fa-plus"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Points Earned</span>
                    <span class="info-box-number">
                        <?php 
                        $totalEarned = Yii::app()->db->createCommand()
                            ->select('SUM(lifetime_earned)')
                            ->from('tbl_customer_loyalty')
                            ->queryScalar();
                        echo number_format($totalEarned ?: 0);
                        ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-yellow">
                <span class="info-box-icon"><i class="fa fa-minus"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Points Redeemed</span>
                    <span class="info-box-number">
                        <?php 
                        $totalRedeemed = Yii::app()->db->createCommand()
                            ->select('SUM(lifetime_redeemed)')
                            ->from('tbl_customer_loyalty')
                            ->queryScalar();
                        echo number_format($totalRedeemed ?: 0);
                        ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-red">
                <span class="info-box-icon"><i class="fa fa-cog"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Program Status</span>
                    <span class="info-box-number">
                        <?php 
                        $isActive = LoyaltySettings::isLoyaltyActive();
                        echo $isActive ? 'ACTIVE' : 'INACTIVE';
                        ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Quick Actions -->
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-bolt"></i> Quick Actions</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <?php echo CHtml::link(
                                '<i class="fa fa-users"></i> View Customers',
                                array('customers'),
                                array('class' => 'btn btn-block btn-primary btn-lg')
                            ); ?>
                        </div>
                        <div class="col-sm-6">
                            <?php echo CHtml::link(
                                '<i class="fa fa-edit"></i> Adjust Points',
                                array('adjustPoints'),
                                array('class' => 'btn btn-block btn-warning btn-lg')
                            ); ?>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-sm-6">
                            <?php echo CHtml::link(
                                '<i class="fa fa-cog"></i> Settings',
                                array('settings'),
                                array('class' => 'btn btn-block btn-info btn-lg')
                            ); ?>
                        </div>
                        <div class="col-sm-6">
                            <?php echo CHtml::link(
                                '<i class="fa fa-bar-chart"></i> Reports',
                                array('reports'),
                                array('class' => 'btn btn-block btn-success btn-lg')
                            ); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-history"></i> Recent Transactions</h3>
                </div>
                <div class="box-body">
                    <?php 
                    $criteria = new CDbCriteria();
                    $criteria->order = 'created_at DESC';
                    $criteria->limit = 5;
                    $criteria->with = 'customer';
                    $recentTransactions = LoyaltyTransaction::model()->findAll($criteria);
                    
                    if (!empty($recentTransactions)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-condensed">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Type</th>
                                        <th>Points</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentTransactions as $trans): ?>
                                        <tr>
                                            <td><?php echo isset($trans->customer) ? $trans->customer->name : 'N/A'; ?></td>
                                            <td>
                                                <span class="label label-<?php echo $trans->transaction_type == 'EARN' ? 'success' : 'warning'; ?>">
                                                    <?php echo $trans->transaction_type; ?>
                                                </span>
                                            </td>
                                            <td><?php echo number_format($trans->points); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($trans->created_at)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No transactions found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Program Settings Overview -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-info-circle"></i> Current Program Settings</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Earn Rate:</strong><br>
                            <span class="text-info"><?php echo LoyaltySettings::getEarnRate(); ?> points per ₹100</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Min Redeem:</strong><br>
                            <span class="text-info"><?php echo LoyaltySettings::getMinRedeemPoints(); ?> points</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Point Value:</strong><br>
                            <span class="text-info">₹<?php echo LoyaltySettings::getPointValue(); ?> per point</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Points Expiry:</strong><br>
                            <span class="text-info"><?php echo LoyaltySettings::getExpiryMonths(); ?> months</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.info-box {
    border-radius: 5px;
    margin-bottom: 15px;
    min-height: 90px;
    padding: 10px;
    position: relative;
    background-color: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
}

.info-box-icon {
    border-radius: 50%;
    color: rgba(255,255,255,0.8);
    display: block;
    font-size: 18px;
    height: 45px;
    line-height: 45px;
    text-align: center;
    width: 45px;
    float: left;
}

.info-box-content {
    margin-left: 60px;
}

.info-box-text {
    display: block;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
}

.info-box-number {
    display: block;
    font-weight: bold;
    font-size: 18px;
}

.bg-aqua { background-color: #00c0ef !important; color: #fff; }
.bg-green { background-color: #00a65a !important; color: #fff; }
.bg-yellow { background-color: #f39c12 !important; color: #fff; }
.bg-red { background-color: #dd4b39 !important; color: #fff; }

.box {
    border-radius: 3px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
    margin-bottom: 20px;
    background: #fff;
}

.box-header {
    border-bottom: 1px solid #f4f4f4;
    color: #444;
    display: block;
    padding: 10px 15px;
    position: relative;
}

.box-title {
    font-size: 18px;
    margin: 0;
    line-height: 1;
}

.box-body {
    border-top-left-radius: 0;
    border-top-right-radius: 0;
    border-bottom-right-radius: 3px;
    border-bottom-left-radius: 3px;
    padding: 10px 15px;
}
</style>