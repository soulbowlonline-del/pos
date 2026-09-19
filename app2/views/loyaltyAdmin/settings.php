<?php
/**
 * Ported from protected/views/LoyaltyAdmin/settings.php.
 */

use app\components\Ui;
use app\models\LoyaltySettings;
use yii\helpers\Html;
?>
<?php
$this->title = 'Loyalty Program Settings';
$this->params['breadcrumbs'] = [
    'Loyalty Admin' => ['index'],
    'Settings',
];
?>

<div class="loyalty-settings content">
    <div class="page-header">
        <h1>
            <i class="fa fa-cog"></i> Loyalty Program Settings
            <small>Configure loyalty program parameters</small>
        </h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Program Configuration</h3>
                </div>

                <?php if (Yii::$app->user->hasFlash('success')): ?>
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <i class="fa fa-check"></i> <?php echo Yii::$app->user->getFlash('success'); ?>
                    </div>
                <?php endif; ?>

                <?php if (Yii::$app->user->hasFlash('error')): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <i class="fa fa-ban"></i> <?php echo Yii::$app->user->getFlash('error'); ?>
                    </div>
                <?php endif; ?>

                <form action="" method="post" class="form-horizontal">
                    <div class="box-body">
                        <?php foreach ($settings as $setting): ?>
                            <div class="form-group">
                                <label class="col-sm-4 control-label">
                                    <?php 
                                    $labels = [
                                        'is_active' => 'Program Status',
                                        'earn_rate' => 'Earn Rate',
                                        'min_redeem_points' => 'Minimum Redeem Points',
                                        'point_value' => 'Point Value (₹)',
                                        'expiry_months' => 'Points Expiry (Months)',
                                    ];
                                    echo isset($labels[$setting->setting_key]) ? $labels[$setting->setting_key] : ucwords(str_replace('_', ' ', $setting->setting_key));
                                    ?>
                                </label>
                                <div class="col-sm-8">
                                    <?php if ($setting->setting_key == 'is_active'): ?>
                                        <div class="radio-inline">
                                            <label>
                                                <input type="radio" name="settings[<?php echo $setting->setting_key; ?>]" 
                                                       value="1" <?php echo $setting->setting_value == '1' ? 'checked' : ''; ?>>
                                                <span class="text-success"><i class="fa fa-check"></i> Active</span>
                                            </label>
                                        </div>
                                        <div class="radio-inline">
                                            <label>
                                                <input type="radio" name="settings[<?php echo $setting->setting_key; ?>]" 
                                                       value="0" <?php echo $setting->setting_value == '0' ? 'checked' : ''; ?>>
                                                <span class="text-danger"><i class="fa fa-times"></i> Inactive</span>
                                            </label>
                                        </div>
                                    <?php else: ?>
                                        <div class="input-group">
                                            <input type="number" 
                                                   name="settings[<?php echo $setting->setting_key; ?>]" 
                                                   value="<?php echo Html::encode($setting->setting_value); ?>" 
                                                   class="form-control" 
                                                   step="<?php echo in_array($setting->setting_key, ['point_value']) ? '0.01' : '1'; ?>"
                                                   min="0"
                                                   required>
                                            <span class="input-group-addon">
                                                <?php
                                                $units = [
                                                    'earn_rate' => 'pts/₹100',
                                                    'min_redeem_points' => 'points',
                                                    'point_value' => '₹',
                                                    'expiry_months' => 'months',
                                                ];
                                                echo isset($units[$setting->setting_key]) ? $units[$setting->setting_key] : '';
                                                ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <small class="help-block text-muted">
                                        <?php echo Html::encode($setting->description); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($settings)): ?>
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i> 
                                No settings found. 
                                <a href="<?php echo Ui::to('initializeSettings'); ?>" class="btn btn-warning btn-sm">
                                    <i class="fa fa-magic"></i> Initialize Default Settings
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($settings)): ?>
                        <div class="box-footer">
                            <div class="col-sm-offset-4 col-sm-8">
                                <button type="submit" class="btn btn-info">
                                    <i class="fa fa-save"></i> Save Settings
                                </button>
                                <a href="<?php echo Ui::to('initializeSettings'); ?>" class="btn btn-warning">
                                    <i class="fa fa-refresh"></i> Reset to Defaults
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="col-md-4">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">Settings Guide</h3>
                </div>
                <div class="box-body">
                    <h5><i class="fa fa-toggle-on text-success"></i> Program Status</h5>
                    <p><small>Enable or disable the entire loyalty program. When inactive, no points will be earned or redeemed.</small></p>

                    <h5><i class="fa fa-plus text-info"></i> Earn Rate</h5>
                    <p><small>Points earned for every ₹100 spent. For example: 1 means customer gets 1 point per ₹100.</small></p>

                    <h5><i class="fa fa-minus text-warning"></i> Minimum Redeem</h5>
                    <p><small>Minimum points required before customer can redeem. Prevents very small redemptions.</small></p>

                    <h5><i class="fa fa-money text-success"></i> Point Value</h5>
                    <p><small>Monetary value of each point in rupees. For example: 1 means each point = ₹1.</small></p>

                    <h5><i class="fa fa-clock-o text-danger"></i> Points Expiry</h5>
                    <p><small>Number of months after which points expire. Set to 0 for no expiry.</small></p>
                </div>
            </div>

            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">Current Impact</h3>
                </div>
                <div class="box-body">
                    <div id="settings-preview">
                        <div class="row">
                            <div class="col-xs-12">
                                <h5>Example Calculation:</h5>
                                <div class="well well-sm">
                                    <strong>Customer spends ₹1000</strong><br>
                                    <span id="earn-calculation">
                                        Points earned: <?php echo floor(1000/100) * (LoyaltySettings::getEarnRate() ?: 1); ?>
                                    </span><br>
                                    <span id="value-calculation">
                                        Point value: ₹<?php echo (floor(1000/100) * (LoyaltySettings::getEarnRate() ?: 1)) * (LoyaltySettings::getPointValue() ?: 1); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="callout callout-info">
                            <h5><i class="fa fa-info-circle"></i> Note</h5>
                            <p><small>Changes to settings will apply to all future transactions. Existing customer points remain unaffected.</small></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Program Statistics -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">Program Statistics</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="description-block border-right">
                                <span class="description-percentage text-green">
                                    <?php 
                                    $activeCustomers = (new \yii\db\Query())->select('COUNT(*)')
                                        ->from('tbl_customer_loyalty')
                                        ->where('total_points > 0')
                                        ->scalar();
                                    echo number_format($activeCustomers ?: 0);
                                    ?>
                                </span>
                                <h5 class="description-header">Active</h5>
                                <span class="description-text">Customers</span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="description-block border-right">
                                <span class="description-percentage text-blue">
                                    <?php 
                                    $totalEarned = (new \yii\db\Query())->select('SUM(lifetime_earned)')
                                        ->from('tbl_customer_loyalty')
                                        ->scalar();
                                    echo number_format($totalEarned ?: 0);
                                    ?>
                                </span>
                                <h5 class="description-header">Total Earned</h5>
                                <span class="description-text">Points</span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="description-block border-right">
                                <span class="description-percentage text-yellow">
                                    <?php 
                                    $totalRedeemed = (new \yii\db\Query())->select('SUM(lifetime_redeemed)')
                                        ->from('tbl_customer_loyalty')
                                        ->scalar();
                                    echo number_format($totalRedeemed ?: 0);
                                    ?>
                                </span>
                                <h5 class="description-header">Total Redeemed</h5>
                                <span class="description-text">Points</span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="description-block border-right">
                                <span class="description-percentage text-red">
                                    <?php 
                                    $totalValue = (new \yii\db\Query())->select('SUM(total_points)')
                                        ->from('tbl_customer_loyalty')
                                        ->scalar();
                                    echo '₹' . number_format(($totalValue ?: 0) * (LoyaltySettings::getPointValue() ?: 1));
                                    ?>
                                </span>
                                <h5 class="description-header">Outstanding</h5>
                                <span class="description-text">Value</span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="description-block border-right">
                                <span class="description-percentage text-purple">
                                    <?php 
                                    $avgPoints = (new \yii\db\Query())->select('AVG(total_points)')
                                        ->from('tbl_customer_loyalty')
                                        ->where('total_points > 0')
                                        ->scalar();
                                    echo number_format($avgPoints ?: 0);
                                    ?>
                                </span>
                                <h5 class="description-header">Avg Points</h5>
                                <span class="description-text">Per Customer</span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="description-block">
                                <span class="description-percentage text-navy">
                                    <?php 
                                    $redemptionRate = 0;
                                    if ($totalEarned > 0) {
                                        $redemptionRate = ($totalRedeemed / $totalEarned) * 100;
                                    }
                                    echo number_format($redemptionRate, 1) . '%';
                                    ?>
                                </span>
                                <h5 class="description-header">Redemption</h5>
                                <span class="description-text">Rate</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.description-block {
    margin: 0 0 10px 0;
}

.description-block > .description-header {
    margin: 0;
    padding: 0;
    font-weight: 600;
    font-size: 16px;
}

.description-block > .description-text {
    text-transform: uppercase;
    font-size: 11px;
}

.description-block > .description-percentage {
    color: green;
    font-size: 20px;
    display: block;
}

.border-right {
    border-right: 1px solid #f4f4f4;
}

.callout {
    border-radius: 3px;
    margin: 0 0 20px 0;
    padding: 15px 30px 15px 15px;
    border-left: 5px solid #eee;
}

.callout-info {
    border-left-color: #00c0ef;
    background-color: #d9edf7;
}

.well {
    background-color: #f5f5f5;
    border: 1px solid #e3e3e3;
    border-radius: 4px;
    padding: 19px;
    margin-bottom: 20px;
}

.well-sm {
    padding: 9px;
    border-radius: 3px;
}
</style>

<script>
$(document).ready(function() {
    // Update calculations when earn rate or point value changes
    $('input[name="settings[earn_rate]"], input[name="settings[point_value]"]').on('input', function() {
        updateCalculations();
    });

    function updateCalculations() {
        var earnRate = parseFloat($('input[name="settings[earn_rate]"]').val()) || 1;
        var pointValue = parseFloat($('input[name="settings[point_value]"]').val()) || 1;
        var spentAmount = 1000;
        
        var earnedPoints = Math.floor(spentAmount / 100) * earnRate;
        var totalValue = earnedPoints * pointValue;
        
        $('#earn-calculation').text('Points earned: ' + earnedPoints);
        $('#value-calculation').text('Point value: ₹' + totalValue);
    }

    // Form validation
    $('form').on('submit', function(e) {
        var earnRate = parseFloat($('input[name="settings[earn_rate]"]').val());
        var pointValue = parseFloat($('input[name="settings[point_value]"]').val());
        
        if (earnRate <= 0) {
            alert('Earn rate must be greater than 0');
            e.preventDefault();
            return false;
        }
        
        if (pointValue <= 0) {
            alert('Point value must be greater than 0');
            e.preventDefault();
            return false;
        }
        
        var confirmed = confirm('Are you sure you want to update the loyalty program settings?');
        if (!confirmed) {
            e.preventDefault();
            return false;
        }
    });
});
</script>