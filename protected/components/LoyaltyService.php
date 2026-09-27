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

        // Order::afterSave() calls this on every save of the order, not only
        // the first, so each later save (web order/update, any re-save of the
        // row) credited the customer again. Earn once per order: skip when the
        // order already has its EARN row. Checked here rather than "only on
        // insert" so an order that is completed by a later update still earns.
        if ($order->id && LoyaltyTransaction::model()->exists(
                'order_id = :oid AND transaction_type = :type',
                array(':oid' => $order->id, ':type' => 'EARN'))) {
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
            // Update loyalty totals in the database, and only while the
            // balance still covers it. The check above reads the balance and
            // the old save() wrote back the value computed from that read, so
            // two redemptions at the same moment both passed and the customer
            // spent the same points twice.
            if (!$loyalty->changePoints(-$pointsToRedeem, 0, $pointsToRedeem, true)) {
                $transaction->rollback();
                return array(
                    'success' => false,
                    'message' => 'Insufficient points available'
                );
            }
            $loyalty->total_points -= $pointsToRedeem;
            $loyalty->lifetime_redeemed += $pointsToRedeem;

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
            
            // Update with order information - only while the row is still a
            // pending redemption. save() wrote back the whole row as read
            // above, so a rollback landing in between was undone (type and
            // points restored) after the points had already been returned.
            $description = "Redeemed {$loyaltyTrans->points} points for order #{$orderId}";
            $claimed = Yii::app()->db->createCommand(
                'UPDATE ' . $loyaltyTrans->tableName() . ' SET order_id = :oid, description = :descr'
                . ' WHERE id = :id AND transaction_type = :type AND order_id IS NULL'
            )->execute(array(':oid' => $orderId, ':descr' => $description, ':id' => $loyaltyTrans->id, ':type' => 'REDEEM'));
            if ($claimed == 0) {
                return false;
            }
            $loyaltyTrans->order_id = $orderId;
            $loyaltyTrans->description = $description;

            return true;
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
            
            // Update transaction as rolled back - first, and only if it is
            // still a pending redemption. The checks above read the row and
            // two rollbacks at once (or a rollback racing the order claiming
            // it) both passed them, so the points were returned twice.
            $points = $loyaltyTrans->points;
            $description = "Rollback - " . $loyaltyTrans->description;
            $claimed = Yii::app()->db->createCommand(
                'UPDATE ' . $loyaltyTrans->tableName() . ' SET transaction_type = :adjust, points = :points, description = :descr'
                . ' WHERE id = :id AND transaction_type = :redeem AND order_id IS NULL'
            )->execute(array(':adjust' => 'ADJUST', ':points' => -$points, ':descr' => $description,
                ':id' => $loyaltyTrans->id, ':redeem' => 'REDEEM'));
            if ($claimed == 0) {
                $transaction->rollback();
                return false;
            }
            $loyaltyTrans->transaction_type = 'ADJUST';
            $loyaltyTrans->points = -$points; // Make it negative to show rollback
            $loyaltyTrans->description = $description;

            // Restore customer points, in the database rather than from the
            // balance read here
            $loyalty = CustomerLoyalty::getOrCreateCustomerLoyalty($loyaltyTrans->customer_id);
            $loyalty->changePoints($points, 0, -$points);
            $loyalty->total_points += $points;
            $loyalty->lifetime_redeemed -= $points;

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
            // In the database, and only while the balance still covers it:
            // save() wrote back a balance computed from the read above, so a
            // redemption at the same moment was erased or the cap was missed.
            if (!$loyalty->changePoints(-$pointsToDeduct, -$pointsToDeduct, 0, true)) {
                return false;
            }
            $loyalty->total_points    -= $pointsToDeduct;
            $loyalty->lifetime_earned -= $pointsToDeduct;

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