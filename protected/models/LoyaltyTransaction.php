<?php
Yii::import('application.models._base.BaseLoyaltyTransaction');

class LoyaltyTransaction extends BaseLoyaltyTransaction
{
    const TYPE_EARN = 'EARN';
    const TYPE_REDEEM = 'REDEEM';
    const TYPE_BONUS = 'BONUS';
    const TYPE_EXPIRE = 'EXPIRE';
    const TYPE_ADJUST = 'ADJUST';

    public $total;
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }


    public static function getLoyaltyLifetimeEarnedPoints($customerId) {
        $totalEarned = self::model()->findBySql(
            "SELECT SUM(points) as total FROM {{loyalty_transactions}} WHERE customer_id = :customerId AND transaction_type IN ('EARN')",
            [':customerId' => $customerId]
        );
        return $totalEarned->total ? $totalEarned->total : 0;
    }

    public static function getLoyaltyLifetimeRedeemedPoints($customerId) {
        $totalRedeemed = self::model()->findBySql(
            "SELECT SUM(points) as total FROM {{loyalty_transactions}} WHERE customer_id = :customerId AND transaction_type IN ('REDEEM')",
            [':customerId' => $customerId]
        );
        return $totalRedeemed->total ? $totalRedeemed->total : 0;
    }

    public static function getLoyaltyCurrentBillEarnedPoints($customerId, $billId) {
        $currentBillEarned = self::model()->findBySql(
            "SELECT SUM(points) as total FROM {{loyalty_transactions}} WHERE customer_id = :customerId AND order_id = :billId AND transaction_type IN ('EARN')",
            [':customerId' => $customerId, ':billId' => $billId]
        );
        return $currentBillEarned->total ? $currentBillEarned->total : 0;
    }

}