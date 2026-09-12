<?php
class LoyaltyService
{
    private static $settings = null;

    public static function getSettings() {
        if (self::$settings === null) {
            $criteria = new CDbCriteria();
            $settingsData = LoyaltySettings::model()->findAll($criteria);
            self::$settings = array();
            foreach ($settingsData as $setting) {
                self::$settings[$setting->setting_key] = $setting->setting_value;
            }
        }
        return self::$settings;
    }

    public static function calculateEarnedPoints($orderAmount) {
        $settings = self::getSettings();
        $earnRate = floatval(isset($settings['earn_rate']) ? $settings['earn_rate'] : 1);
        return floor($orderAmount / 100) * $earnRate;
    }

    public static function processOrderEarn($order) {
        if (!$order->customer_id || $order->status != 0) {
            return false;
        }

        $settings = self::getSettings();
        if (!(isset($settings['is_active']) ? $settings['is_active'] : 1)) {
            return false;
        }

        $earnedPoints = self::calculateEarnedPoints($order->total_amt);
        if ($earnedPoints <= 0) {
            return false;
        }

        $loyalty = CustomerLoyalty::getOrCreateCustomerLoyalty($order->customer_id);
        return $loyalty->addPoints(
            $earnedPoints,
            $order->id,
            "Earned {$earnedPoints} points for order #{$order->id}"
        );
    }

    public static function processOrderRedeem($orderId, $pointsToRedeem) {
        $order = Order::model()->findByPk($orderId);
        if (!$order || !$order->customer_id) {
            return false;
        }

        $settings = self::getSettings();
        $minRedeem = intval(isset($settings['min_redeem_points']) ? $settings['min_redeem_points'] : 50);
        
        if ($pointsToRedeem < $minRedeem) {
            return false;
        }

        $loyalty = CustomerLoyalty::getOrCreateCustomerLoyalty($order->customer_id);
        return $loyalty->redeemPoints(
            $pointsToRedeem,
            $order->id,
            "Redeemed {$pointsToRedeem} points for order #{$order->bill_no}"
        );
    }

    /**
     * Pre-process redemption before order completion
     * Creates redemption record without order_id
     */
    public static function preProcessRedemption($customerId, $pointsToRedeem) {
        $settings = self::getSettings();
        $minRedeem = intval(isset($settings['min_redeem_points']) ? $settings['min_redeem_points'] : 50);
        
        // Validate minimum redemption
        if ($pointsToRedeem < $minRedeem) {
            return array(
                'success' => false,
                'message' => "Minimum {$minRedeem} points required for redemption"
            );
        }

        $loyalty = CustomerLoyalty::getOrCreateCustomerLoyalty($customerId);
        
        // Check if customer has enough points
        if ($loyalty->total_points < $pointsToRedeem) {
            return array(
                'success' => false,
                'message' => 'Insufficient points available'
            );
        }

        $transaction = Yii::app()->db->beginTransaction();
        try {
            // Update loyalty totals
            $loyalty->total_points -= $pointsToRedeem;
            $loyalty->lifetime_redeemed += $pointsToRedeem;
            $loyalty->save();

            // Create transaction record without order_id (will be updated later)
            $loyaltyTrans = new LoyaltyTransaction();
            $loyaltyTrans->customer_id = $customerId;
            $loyaltyTrans->order_id = null; // Will be set later
            $loyaltyTrans->transaction_type = 'REDEEM';
            $loyaltyTrans->points = $pointsToRedeem;
            $loyaltyTrans->description = "Pre-redeemed {$pointsToRedeem} points (pending order)";
            $loyaltyTrans->save();

            $transaction->commit();
            
            return array(
                'success' => true,
                'redemption_id' => $loyaltyTrans->id,
                'remaining_points' => $loyalty->total_points,
                'message' => 'Points pre-redeemed successfully'
            );
        } catch (Exception $e) {
            $transaction->rollback();
            return array(
                'success' => false,
                'message' => 'Failed to process redemption: ' . $e->getMessage()
            );
        }
    }

    /**
     * Update redemption record with order ID after order completion
     */
    public static function updateRedemptionWithOrderId($redemptionId, $orderId) {
        try {
            $loyaltyTrans = LoyaltyTransaction::model()->findByPk($redemptionId);
            
            if (!$loyaltyTrans) {
                return false;
            }
            
            // Verify it's a redemption transaction without order_id
            if ($loyaltyTrans->transaction_type !== 'REDEEM' || $loyaltyTrans->order_id !== null) {
                return false;
            }
            
            // Get order details for better description
            // $order = Order::model()->findByPk($orderId);
            // $billNo = $order ? $order->bill_no : $orderId;
            
            // Update with order information
            $loyaltyTrans->order_id = $orderId;
            $loyaltyTrans->description = "Redeemed {$loyaltyTrans->points} points for order #{$orderId}";
            
            return $loyaltyTrans->save();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Rollback a pre-redemption if order fails
     */
    public static function rollbackPreRedemption($redemptionId) {
        $transaction = Yii::app()->db->beginTransaction();
        try {
            $loyaltyTrans = LoyaltyTransaction::model()->findByPk($redemptionId);
            
            if (!$loyaltyTrans || $loyaltyTrans->transaction_type !== 'REDEEM') {
                return false;
            }
            
            // If order_id is already set, don't rollback
            if ($loyaltyTrans->order_id !== null) {
                return false;
            }
            
            // Restore customer points
            $loyalty = CustomerLoyalty::getOrCreateCustomerLoyalty($loyaltyTrans->customer_id);
            $loyalty->total_points += $loyaltyTrans->points;
            $loyalty->lifetime_redeemed -= $loyaltyTrans->points;
            $loyalty->save();
            
            // Update transaction as rolled back
            $loyaltyTrans->transaction_type = 'ADJUST';
            $loyaltyTrans->points = -$loyaltyTrans->points; // Make it negative to show rollback
            $loyaltyTrans->description = "Rollback - " . $loyaltyTrans->description;
            $loyaltyTrans->save();
            
            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollback();
            return false;
        }
    }

    /**
     * Deduct earned loyalty points when a refund is processed.
     * The caller passes the exact number of earned points to deduct.
     *
     * @param int $orderId    Original order ID
     * @param int $earnPoints Earned points to deduct (must be > 0)
     * @return int|false      Points deducted on success, false on failure
     */
    public static function processRefundDeductPoints($orderId, $earnPoints = 0) {
        $earnPoints = intval($earnPoints);
        if ($earnPoints <= 0) {
            return false;
        }

        $order = Order::model()->findByPk($orderId);
        if (!$order || !$order->customer_id) {
            return false;
        }

        $loyalty = CustomerLoyalty::getOrCreateCustomerLoyalty($order->customer_id);

        // Cap: never let total_points go below zero
        $pointsToDeduct = min($earnPoints, $loyalty->total_points);

        if ($pointsToDeduct <= 0) {
            return false;
        }

        try {
            $loyalty->total_points    -= $pointsToDeduct;
            $loyalty->lifetime_earned -= $pointsToDeduct;
            $loyalty->save();

            $loyaltyTrans = new LoyaltyTransaction();
            $loyaltyTrans->customer_id      = $order->customer_id;
            $loyaltyTrans->order_id         = $orderId;
            $loyaltyTrans->transaction_type = 'ADJUST';
            $loyaltyTrans->points           = -$pointsToDeduct;
            $loyaltyTrans->description      = "Deducted {$pointsToDeduct} earned points due to refund on order #{$orderId}";
            $loyaltyTrans->save();

            return $pointsToDeduct;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function getCustomerLoyaltyInfo($customerId) {
        $loyalty = CustomerLoyalty::getOrCreateCustomerLoyalty($customerId);
        $settings = self::getSettings();
        
        return array(
            'total_points' => $loyalty->total_points,
            'lifetime_earned' => $loyalty->lifetime_earned,
            'lifetime_redeemed' => $loyalty->lifetime_redeemed,
            'min_redeem' => intval(isset($settings['min_redeem_points']) ? $settings['min_redeem_points'] : 50),
            'point_value' => floatval(isset($settings['point_value']) ? $settings['point_value'] : 1),
        );
    }
}