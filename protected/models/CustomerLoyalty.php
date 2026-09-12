<?php
Yii::import('application.models._base.BaseCustomerLoyalty');

class CustomerLoyalty extends BaseCustomerLoyalty
{
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public static function getOrCreateCustomerLoyalty($customerId) {
        $loyalty = self::model()->findByAttributes(['customer_id' => $customerId]);
        if (!$loyalty) {
            $loyalty = new self();
            $loyalty->customer_id = $customerId;
            $loyalty->total_points = 0;
            $loyalty->lifetime_earned = 0;
            $loyalty->lifetime_redeemed = 0;
            $loyalty->save();
        }
        // echo "<pre>";print_r($loyalty); die;
        return $loyalty;
    }

    public function asArray() {
        return [
            'customer_id' => $this->customer_id,
            'total_points' => $this->total_points,
            'lifetime_earned' => $this->lifetime_earned,
            'lifetime_redeemed' => $this->lifetime_redeemed,
        ];
    }

    public function addPoints($points, $orderId = null, $description = 'Points earned') {
        // $transaction = Yii::app()->db->beginTransaction();
        try {
            // Update loyalty totals
            $this->total_points += $points;
            $this->lifetime_earned += $points;
            $this->save();

            // Create transaction record
            $loyaltyTrans = new LoyaltyTransaction();
            $loyaltyTrans->customer_id = $this->customer_id;
            $loyaltyTrans->order_id = $orderId;
            $loyaltyTrans->transaction_type = 'EARN';
            $loyaltyTrans->points = $points;
            $loyaltyTrans->description = $description;
            $loyaltyTrans->save();

            // $transaction->commit();
            return true;
        } catch (Exception $e) {
            // $transaction->rollback();
            // echo "Error adding points: " . $e->getMessage();
            return false;
        }
    }

    public function redeemPoints($points, $orderId = null, $description = 'Points redeemed') {
        if ($this->total_points < $points) {
            return false; // Insufficient points
        }

        $transaction = Yii::app()->db->beginTransaction();
        try {
            // Update loyalty totals
            $this->total_points -= $points;
            $this->lifetime_redeemed += $points;
            $this->save();

            // Create transaction record
            $loyaltyTrans = new LoyaltyTransaction();
            $loyaltyTrans->customer_id = $this->customer_id;
            $loyaltyTrans->order_id = $orderId;
            $loyaltyTrans->transaction_type = 'REDEEM';
            $loyaltyTrans->points = $points;
            $loyaltyTrans->description = $description;
            $loyaltyTrans->save();

            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollback();
            return false;
        }
    }
}