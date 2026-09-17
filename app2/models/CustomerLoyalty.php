<?php
namespace app\models;

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
     *
     * Mirrors CustomerLoyalty::getOrCreateCustomerLoyalty() in the Yii 1 model,
     * including the side effect of inserting a row on first read.
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
}
