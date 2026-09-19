<?php
/**
 * Ported from protected/views/loyaltyAdmin/adjustPoints.php.
 */

use app\components\Ui;
use app\models\Customer;
use app\models\LoyaltySettings;
use yii\helpers\Html;
?>
<?php
$this->title = 'Adjust Customer Points';
$this->params['breadcrumbs'] = [
    'Loyalty Admin' => ['index'],
    'Adjust Points',
];
?>

<div class="loyalty-adjust-points content">
    <div class="page-header">
        <h1>
            <i class="fa fa-edit"></i> Adjust Customer Points
            <small>Manually add or remove loyalty points</small>
        </h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">Point Adjustment Form</h3>
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
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Select Customer <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="customer_id" id="customer-select" class="form-control select2" required style="width: 100%;">
                                    <option value="">-- Select Customer --</option>
                                    <?php 
                                    $customers = Customer::find()->orderBy('name ASC')->all();
                                    foreach ($customers as $customer): 
                                        $isSelected = isset($selectedCustomerId) && $selectedCustomerId == $customer->id;
                                    ?>
                                        <option value="<?php echo $customer->id; ?>" 
                                                data-phone="<?php echo Html::encode($customer->contact_no); ?>" 
                                                data-email="<?php echo Html::encode($customer->email); ?>"
                                                <?php echo $isSelected ? 'selected="selected"' : ''; ?>>
                                            <?php echo Html::encode($customer->name); ?>
                                            <?php if ($customer->contact_no): ?>
                                                (<?php echo Html::encode($customer->contact_no); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="help-block">Search and select customer by name or phone number</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Points Adjustment <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <input type="number" name="points" class="form-control" placeholder="Enter points (positive to add, negative to deduct)" required step="0.01">
                                    <span class="input-group-addon">points</span>
                                </div>
                                <small class="help-block">
                                    Enter positive number to add points, negative number to deduct points<br>
                                    <strong>Examples:</strong> 100 (adds 100 points), -50 (deducts 50 points)
                                </small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Reason/Description</label>
                            <div class="col-sm-9">
                                <textarea name="description" class="form-control" rows="3" placeholder="Enter reason for adjustment (optional)"></textarea>
                                <small class="help-block">Optional: Provide reason for this adjustment</small>
                            </div>
                        </div>

                        <!-- Customer Info Display -->
                        <div id="customer-info" style="display: none;">
                            <div class="form-group">
                                <label class="col-sm-3 control-label">Current Points</label>
                                <div class="col-sm-9">
                                    <p class="form-control-static">
                                        <span id="current-points" class="label label-info label-lg">0</span>
                                    </p>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-3 control-label">Lifetime Earned</label>
                                <div class="col-sm-9">
                                    <p class="form-control-static">
                                        <span id="lifetime-earned" class="text-success">0</span> points
                                    </p>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-3 control-label">Lifetime Redeemed</label>
                                <div class="col-sm-9">
                                    <p class="form-control-static">
                                        <span id="lifetime-redeemed" class="text-warning">0</span> points
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="box-footer">
                        <div class="col-sm-offset-3 col-sm-9">
                            <button type="submit" class="btn btn-warning">
                                <i class="fa fa-edit"></i> Adjust Points
                            </button>
                            <?php if (isset($selectedCustomerId) && $selectedCustomerId): ?>
                                <a href="<?php echo Ui::to('viewTransactions', array('id' => $selectedCustomerId)); ?>" class="btn btn-default">
                                    <i class="fa fa-arrow-left"></i> Back to Transactions
                                </a>
                            <?php else: ?>
                                <a href="<?php echo Ui::to('customers'); ?>" class="btn btn-default">
                                    <i class="fa fa-arrow-left"></i> Back to Customers
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-4">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Point Adjustment Guide</h3>
                </div>
                <div class="box-body">
                    <h5><i class="fa fa-plus-circle text-success"></i> Adding Points</h5>
                    <p><small>Use positive numbers to add points to customer account. Common scenarios:</small></p>
                    <ul class="text-sm">
                        <li>Bonus points for promotions</li>
                        <li>Compensation for service issues</li>
                        <li>Birthday/anniversary bonus</li>
                        <li>Referral rewards</li>
                    </ul>

                    <h5><i class="fa fa-minus-circle text-warning"></i> Deducting Points</h5>
                    <p><small>Use negative numbers to deduct points. Common scenarios:</small></p>
                    <ul class="text-sm">
                        <li>Manual redemption</li>
                        <li>Point correction/reversal</li>
                        <li>Account adjustments</li>
                    </ul>

                    <div class="callout callout-warning">
                        <h5><i class="fa fa-exclamation-triangle"></i> Important!</h5>
                        <p><small>Always provide a clear reason for manual point adjustments for audit purposes.</small></p>
                    </div>
                </div>
            </div>

            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">Quick Stats</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-xs-6 text-center">
                            <div class="description-block border-right">
                                <span class="description-percentage text-green">
                                    <?php echo LoyaltySettings::getEarnRate(); ?>
                                </span>
                                <h5 class="description-header">Earn Rate</h5>
                                <span class="description-text">Points per ₹100</span>
                            </div>
                        </div>
                        <div class="col-xs-6 text-center">
                            <div class="description-block">
                                <span class="description-percentage text-yellow">
                                    <?php echo LoyaltySettings::getMinRedeemPoints(); ?>
                                </span>
                                <h5 class="description-header">Min Redeem</h5>
                                <span class="description-text">Points required</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.label-lg {
    font-size: 14px;
    padding: 6px 12px;
}

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

.callout-warning {
    border-left-color: #f39c12;
    background-color: #fcf8e3;
}

.text-sm {
    font-size: 12px;
}
</style>

<script>
$(document).ready(function() {
    // Initialize Select2
    $('#customer-select').select2({
        placeholder: 'Search customer by name or phone...',
        allowClear: true
    });

    // Load customer loyalty info when customer is selected
    $('#customer-select').on('change', function() {
        var customerId = $(this).val();
        if (customerId) {
            loadCustomerLoyaltyInfo(customerId);
            $('#customer-info').show();
        } else {
            $('#customer-info').hide();
        }
    });

    // Auto-load info if a customer is pre-selected
    var preSelectedId = <?php echo (isset($selectedCustomerId) && $selectedCustomerId) ? intval($selectedCustomerId) : 'null'; ?>;
    if (preSelectedId) {
        loadCustomerLoyaltyInfo(preSelectedId);
        $('#customer-info').show();
    }

    function loadCustomerLoyaltyInfo(customerId) {
        $.ajax({
            url: '<?php echo Ui::to("getCustomerLoyaltyInfo"); ?>',
            method: 'POST',
            data: { customer_id: customerId },
            success: function(response) {
                if (response.status === 'success') {
                    var data = response.data;
                    $('#current-points').text(data.total_points || 0);
                    $('#lifetime-earned').text(data.lifetime_earned || 0);
                    $('#lifetime-redeemed').text(data.lifetime_redeemed || 0);
                }
            },
            error: function() {
                console.log('Error loading customer loyalty info');
            }
        });
    }

    // Form validation
    $('form').on('submit', function(e) {
        var customerId = $('#customer-select').val();
        var points = $('input[name="points"]').val();
        
        if (!customerId) {
            alert('Please select a customer');
            e.preventDefault();
            return false;
        }
        
        if (!points || points == 0) {
            alert('Please enter points adjustment (cannot be 0)');
            e.preventDefault();
            return false;
        }
        
        // Confirmation for large adjustments
        var absPoints = Math.abs(parseFloat(points));
        if (absPoints > 1000) {
            var action = points > 0 ? 'add' : 'deduct';
            var confirmed = confirm('You are about to ' + action + ' ' + absPoints + ' points. Are you sure?');
            if (!confirmed) {
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>