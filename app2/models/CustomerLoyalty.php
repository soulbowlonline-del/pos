<?php
namespace app\models;

use Yii;
use Throwable;
use yii\db\ActiveRecord;

/** Ported from protected/models/CustomerLoyalty.php (Yii 1). */
class CustomerLoyalty extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%customer_loyalty}}';
    }

    /**
     * Returns the customer's loyalty row, creating a zeroed one if absent.
     * Mirrors getOrCreateCustomerLoyalty() on the Yii 1 side, including the
     * side effect of inserting a row on first read.
     */
    public static function getOrCreate($customerId)
    {
        $loyalty = static::findOne(['customer_id' => $customerId]);
        if ($loyalty === null) {
            $loyalty = new static();
            $loyalty->customer_id = $customerId;
            $loyalty->total_points = 0;
            $loyalty->lifetime_earned = 0;
            $loyalty->lifetime_redeemed = 0;
            $loyalty->save(false);
        }
        return $loyalty;
    }

    /** Payload from CustomerLoyalty::asArray() on the Yii 1 side. */
    public function asArray()
    {
        return [
            'customer_id' => $this->customer_id === null ? null : (string)$this->customer_id,
            'total_points' => $this->total_points,
            'lifetime_earned' => $this->lifetime_earned,
            'lifetime_redeemed' => $this->lifetime_redeemed,
        ];
    }

    /**
     * Credits points and records an EARN transaction.
     *
     * The Yii 1 original has its beginTransaction/commit/rollback lines
     * commented out, so the two writes are not atomic there. That is preserved
     * rather than silently changed - see the note on the write paths in
     * LoyaltyService.
     */
    public function addPoints($points, $orderId = null, $description = 'Points earned')
    {
        try {
            $this->total_points += $points;
            $this->lifetime_earned += $points;
            $this->save(false);

            $trans = new LoyaltyTransaction();
            $trans->customer_id = $this->customer_id;
            $trans->order_id = $orderId;
            $trans->transaction_type = LoyaltyTransaction::TYPE_EARN;
            $trans->points = $points;
            $trans->description = $description;
            $trans->save(false);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Debits points and records a REDEEM transaction, atomically.
     */
    public function redeemPoints($points, $orderId = null, $description = 'Points redeemed')
    {
        if ($this->total_points < $points) {
            return false;               // insufficient points
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $this->total_points -= $points;
            $this->lifetime_redeemed += $points;
            $this->save(false);

            $trans = new LoyaltyTransaction();
            $trans->customer_id = $this->customer_id;
            $trans->order_id = $orderId;
            $trans->transaction_type = LoyaltyTransaction::TYPE_REDEEM;
            $trans->points = $points;
            $trans->description = $description;
            $trans->save(false);

            $transaction->commit();
            return true;
        } catch (Throwable $e) {
            $transaction->rollBack();
            return false;
        }
    }
}
