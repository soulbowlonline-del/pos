<?php
$this->pageTitle = 'Customer Loyalty Points';
$this->breadcrumbs = array(
    'Loyalty Admin' => array('index'),
    'Customers',
);

$totalCustomers  = Yii::app()->db->createCommand()->select('COUNT(*)')->from('tbl_customer_loyalty')->queryScalar();
$activeCustomers = Yii::app()->db->createCommand()->select('COUNT(*)')->from('tbl_customer_loyalty')->where('total_points > 0')->queryScalar();
$avgPoints       = Yii::app()->db->createCommand()->select('AVG(total_points)')->from('tbl_customer_loyalty')->where('total_points > 0')->queryScalar();
$totalPointsSum  = Yii::app()->db->createCommand()->select('SUM(total_points)')->from('tbl_customer_loyalty')->queryScalar();
$pointValue      = LoyaltySettings::getPointValue();
?>

<div class="loyalty-customers content">

    <!-- Summary Stats -->
    <div class="row">
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3><?php echo number_format($totalCustomers ?: 0); ?></h3>
                    <p>Total Customers</p>
                </div>
                <div class="icon"><i class="fa fa-users"></i></div>
                <a href="<?php echo Yii::app()->createUrl('loyaltyAdmin/customers'); ?>" class="small-box-footer">
                    More info <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3><?php echo number_format($activeCustomers ?: 0); ?></h3>
                    <p>Active Customers</p>
                </div>
                <div class="icon"><i class="fa fa-star"></i></div>
                <a href="<?php echo Yii::app()->createUrl('loyaltyAdmin/customers'); ?>" class="small-box-footer">
                    More info <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3><?php echo number_format($avgPoints ?: 0); ?></h3>
                    <p>Avg Points / Customer</p>
                </div>
                <div class="icon"><i class="fa fa-bar-chart"></i></div>
                <a href="<?php echo Yii::app()->createUrl('loyaltyAdmin/reports'); ?>" class="small-box-footer">
                    More info <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3>&#8377;<?php echo number_format(($totalPointsSum ?: 0) * $pointValue, 0); ?></h3>
                    <p>Total Points Value</p>
                </div>
                <div class="icon"><i class="fa fa-money"></i></div>
                <a href="<?php echo Yii::app()->createUrl('loyaltyAdmin/reports'); ?>" class="small-box-footer">
                    More info <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Customer Grid -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-users"></i> Customer Loyalty List</h3>
                    <div class="box-tools pull-right">
                        <?php echo CHtml::link(
                            '<i class="fa fa-plus"></i> Adjust Points',
                            array('adjustPoints'),
                            array('class' => 'btn btn-success btn-sm')
                        ); ?>
                        <?php echo CHtml::link(
                            '<i class="fa fa-download"></i> Export',
                            array('exportCustomers'),
                            array('class' => 'btn btn-info btn-sm')
                        ); ?>
                    </div>
                </div>

                <!-- Search / Filter Bar -->
                <div class="box-body" style="border-bottom:1px solid #f4f4f4; padding-bottom:10px;">
                    <form method="get" action="" class="form-inline">
                        <div class="form-group" style="margin-right:8px;">
                            <div class="input-group input-group-sm">
                                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                                <input type="text" name="search_name" class="form-control" placeholder="Search by name"
                                       value="<?php echo CHtml::encode(isset($searchName) ? $searchName : ''); ?>" style="width:180px;">
                            </div>
                        </div>
                        <div class="form-group" style="margin-right:8px;">
                            <div class="input-group input-group-sm">
                                <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                                <input type="text" name="search_phone" class="form-control" placeholder="Search by mobile"
                                       value="<?php echo CHtml::encode(isset($searchPhone) ? $searchPhone : ''); ?>" style="width:160px;">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa fa-search"></i> Search
                        </button>
                        <?php if (!empty($searchName) || !empty($searchPhone)): ?>
                            <a href="<?php echo Yii::app()->createUrl('loyaltyAdmin/customers'); ?>" class="btn btn-default btn-sm" style="margin-left:4px;">
                                <i class="fa fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
                <?php if (Yii::app()->user->hasFlash('success')): ?>
                    <div class="alert alert-success alert-dismissible" style="margin:10px 15px 0;">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <i class="fa fa-check"></i> <?php echo Yii::app()->user->getFlash('success'); ?>
                    </div>
                <?php endif; ?>
                <?php if (Yii::app()->user->hasFlash('error')): ?>
                    <div class="alert alert-danger alert-dismissible" style="margin:10px 15px 0;">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <i class="fa fa-ban"></i> <?php echo Yii::app()->user->getFlash('error'); ?>
                    </div>
                <?php endif; ?>

                <div class="box-body">
                  <div class="table-responsive">
                    <?php $this->widget('bootstrap.widgets.TbGridView', array(
                        'id'           => 'customer-loyalty-grid',
                        'type'         => 'striped bordered condensed',
                        'dataProvider' => $dataProvider,
                        'filter'       => null,
                        'pager'        => true,
                        'columns' => array(

                            /* Customer */
                            array(
                                'header'      => 'Customer',
                                'type'        => 'raw',
                                'value'       => function($data) {
                                    $customer = $data->customer;
                                    if (!$customer) {
                                        return '<span class="text-muted"><i class="fa fa-user-times"></i> N/A</span>';
                                    }
                                    $displayName = trim($customer->name);
                                    if ($displayName) {
                                        $html = '<strong>' . CHtml::encode($displayName) . '</strong>';
                                        if ($customer->contact_no) {
                                            $html .= '<br><small class="text-muted"><i class="fa fa-phone"></i> ' . CHtml::encode($customer->contact_no) . '</small>';
                                        }
                                    } else {
                                        $html = '<strong><i class="fa fa-phone" style="color:#aaa;"></i> ' . CHtml::encode($customer->contact_no) . '</strong>';
                                    }
                                    if ($customer->email) {
                                        $html .= '<br><small class="text-muted"><i class="fa fa-envelope"></i> ' . CHtml::encode($customer->email) . '</small>';
                                    }
                                    return $html;
                                },
                                'htmlOptions' => array('style' => 'width:220px;'),
                            ),

                            /* Tier */
                            array(
                                'header'      => 'Tier',
                                'type'        => 'raw',
                                'value'       => function($data) {
                                    $pts = (int)$data->total_points;
                                    if ($pts >= 5000) {
                                        return '<span class="loyalty-tier gold"><i class="fa fa-trophy"></i> Gold</span>';
                                    } elseif ($pts >= 1000) {
                                        return '<span class="loyalty-tier silver"><i class="fa fa-star"></i> Silver</span>';
                                    } elseif ($pts > 0) {
                                        return '<span class="loyalty-tier bronze"><i class="fa fa-star-o"></i> Bronze</span>';
                                    }
                                    return '<span class="loyalty-tier none"><i class="fa fa-circle-o"></i> None</span>';
                                },
                                'htmlOptions' => array('class' => 'text-center', 'style' => 'width:85px;'),
                            ),

                            /* Current Points */
                            array(
                                'header'      => 'Points',
                                'type'        => 'raw',
                                'value'       => function($data) {
                                    $pts = (int)$data->total_points;
                                    $cls = $pts > 0 ? 'badge-points-pos' : 'badge-points-zero';
                                    return '<span class="points-badge ' . $cls . '">' . number_format($pts) . '</span>';
                                },
                                'htmlOptions' => array('class' => 'text-center', 'style' => 'width:90px;'),
                            ),

                            /* Lifetime Earned */
                            array(
                                'header'      => '<i class="fa fa-plus-circle" style="color:#00a65a;"></i>&nbsp;Earned',
                                'type'        => 'raw',
                                'value'       => function($data) {
                                    return '<span class="text-success"><strong>' . number_format($data->lifetime_earned) . '</strong></span>';
                                },
                                'htmlOptions' => array('class' => 'text-center', 'style' => 'width:100px;'),
                            ),

                            /* Lifetime Redeemed */
                            array(
                                'header'      => '<i class="fa fa-minus-circle" style="color:#f39c12;"></i>&nbsp;Redeemed',
                                'type'        => 'raw',
                                'value'       => function($data) {
                                    return '<span class="text-warning"><strong>' . number_format($data->lifetime_redeemed) . '</strong></span>';
                                },
                                'htmlOptions' => array('class' => 'text-center', 'style' => 'width:100px;'),
                            ),

                            /* Cash Value */
                            array(
                                'header'      => 'Cash Value',
                                'type'        => 'raw',
                                'value'       => function($data) {
                                    $pv  = LoyaltySettings::getPointValue();
                                    $val = $data->total_points * $pv;
                                    return '<span class="text-info"><i class="fa fa-rupee"></i>&nbsp;' . number_format($val, 2) . '</span>';
                                },
                                'htmlOptions' => array('class' => 'text-center', 'style' => 'width:100px;'),
                            ),

                            /* Status */
                            array(
                                'header'      => 'Status',
                                'type'        => 'raw',
                                'value'       => function($data) {
                                    if ($data->status == 1) {
                                        return '<span class="label label-success"><i class="fa fa-check"></i> Active</span>';
                                    }
                                    return '<span class="label label-danger"><i class="fa fa-times"></i> Inactive</span>';
                                },
                                'htmlOptions' => array('class' => 'text-center', 'style' => 'width:80px;'),
                            ),

                            /* Actions */
                            array(
                                'header'      => 'Actions',
                                'type'        => 'raw',
                                'value'       => function($data) {
                                    $txBtn = CHtml::link(
                                        '<i class="fa fa-list-alt"></i>',
                                        array('viewTransactions', 'id' => $data->customer_id),
                                        array(
                                            'class'          => 'btn btn-xs btn-info',
                                            'title'          => 'View Transactions',
                                            'data-toggle'    => 'tooltip',
                                            'data-placement' => 'top',
                                        )
                                    );
                                    $adjBtn = CHtml::link(
                                        '<i class="fa fa-pencil"></i>',
                                        array('adjustPoints', 'customer_id' => $data->customer_id),
                                        array(
                                            'class'          => 'btn btn-xs btn-warning',
                                            'title'          => 'Adjust Points',
                                            'data-toggle'    => 'tooltip',
                                            'data-placement' => 'top',
                                        )
                                    );
                                    return '<div class="btn-group">' . $txBtn . $adjBtn . '</div>';
                                },
                                'htmlOptions' => array('class' => 'text-center', 'style' => 'width:80px;'),
                            ),

                        ),
                        'itemsCssClass' => 'table',
                        'summaryText'   => 'Showing {start}-{end} of {count} customers',
                        'emptyText'     => 'No loyalty customers found.',
                    )); ?>
                  </div>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
/* Tier badges */
.loyalty-tier { display:inline-block; padding:3px 8px; border-radius:3px; font-size:11px; font-weight:700; white-space:nowrap; }
.loyalty-tier.gold   { background-color:#FFD700; color:#333; }
.loyalty-tier.silver { background-color:#C0C0C0; color:#333; }
.loyalty-tier.bronze { background-color:#cd7f32; color:#fff; }
.loyalty-tier.none   { background-color:#d2d6de; color:#555; }
/* Points badge */
.points-badge { display:inline-block; padding:4px 10px; border-radius:10px; font-size:13px; font-weight:700; }
.badge-points-pos  { background-color:#00a65a; color:#fff; }
.badge-points-zero { background-color:#d2d6de; color:#555; }
/* Grid alignment */
.grid-view table tbody td { vertical-align:middle; }
</style>

<script>
$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
</script>