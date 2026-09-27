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

    /**
     * Moves the balance with one UPDATE in the database. The callers used to
     * change total_points on the row read at the start of the request and
     * save() it back, so two requests at the same moment overwrote each other:
     * points were spent twice, or a credit was lost. With $debit the row only
     * changes while total_points still covers the amount taken off.
     * Returns whether the row changed.
     */
    public function changePoints($total, $earned, $redeemed, $debit = false) {
        $sql = 'UPDATE ' . $this->tableName() . ' SET total_points = COALESCE(total_points, 0) + :total,'
            . ' lifetime_earned = COALESCE(lifetime_earned, 0) + :earned,'
            . ' lifetime_redeemed = COALESCE(lifetime_redeemed, 0) + :redeemed WHERE id = :id';
        $params = array(':total' => $total, ':earned' => $earned, ':redeemed' => $redeemed, ':id' => $this->id);
        if ($debit) {
            $sql .= ' AND COALESCE(total_points, 0) >= :need';
            $params[':need'] = -$total;
        }
        return $this->getDbConnection()->createCommand($sql)->execute($params) > 0;
    }

    public function addPoints($points, $orderId = null, $description = 'Points earned') {
        // $transaction = Yii::app()->db->beginTransaction();
        try {
            // Update loyalty totals in the database (see changePoints())
            $this->changePoints($points, $points, 0);
            $this->total_points += $points;
            $this->lifetime_earned += $points;

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
            // Update loyalty totals in the database, and only while the
            // balance still covers it: the check above is on a balance read
            // earlier, and two redemptions at once both passed it.
            if (!$this->changePoints(-$points, 0, $points, true)) {
                $transaction->rollback();
                return false; // Insufficient points
            }
            $this->total_points -= $points;
            $this->lifetime_redeemed += $points;

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