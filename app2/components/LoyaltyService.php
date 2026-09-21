<?php
namespace app\components;

use Yii;
use Throwable;
use app\models\CustomerLoyalty;
use app\models\LoyaltySettings;
use app\models\LoyaltyTransaction;
use app\models\Order;

/**
 * Yii 2 port of protected/components/LoyaltyService.php.
 *
 * Behaviour is reproduced exactly, including the fallback defaults (50 minimum
 * redeem points, point value 1, earn rate 1) that apply when a setting row is
 * missing, and the message strings, which clients surface to cashiers.
 *
 * Two deviations from the original, both deliberate and both confined to
 * failure handling rather than the happy path:
 *
 *  1. rollbackPreRedemption() in the Yii 1 version returns false from inside
 *     its try block without committing or rolling back, leaving the transaction
 *     open for the rest of the request. Here those paths roll back first. The
 *     caller sees the same false, and no data changes either way.
 *
 *  2. processRefundDeductPoints() in the Yii 1 version performs two writes with
 *     no transaction, so a failure between them deducts points without leaving
 *     a transaction record - a silent discrepancy in a customer's balance.
 *     Here the pair is atomic. Successful calls behave identically.
 *
 * CustomerLoyalty::addPoints() has its transaction commented out in the
 * original; that one is left as-is because changing it would alter the EARN
 * path, which is driven from order completion and is not ported yet.
 */
class LoyaltyService
{
    private static $settings;

    public static function getSettings()
    {
        if (self::$settings === null) {
            self::$settings = LoyaltySettings::asMap();
        }
        return self::$settings;
    }

    private static function minRedeem()
    {
        $settings = self::getSettings();
        return intval(isset($settings['min_redeem_points']) ? $settings['min_redeem_points'] : 50);
    }

    public static function calculateEarnedPoints($orderAmount)
    {
        $settings = self::getSettings();
        $earnRate = floatval(isset($settings['earn_rate']) ? $settings['earn_rate'] : 1);
        return floor($orderAmount / 100) * $earnRate;
    }

    public static function getCustomerLoyaltyInfo($customerId)
    {
        $loyalty = CustomerLoyalty::getOrCreate($customerId);
        $settings = self::getSettings();

        return [
            'total_points' => $loyalty->total_points,
            'lifetime_earned' => $loyalty->lifetime_earned,
            'lifetime_redeemed' => $loyalty->lifetime_redeemed,
            'min_redeem' => intval(isset($settings['min_redeem_points']) ? $settings['min_redeem_points'] : 50),
            'point_value' => floatval(isset($settings['point_value']) ? $settings['point_value'] : 1),
        ];
    }

    /** Legacy path: redeem against a completed order. */
    /**
     * Credits the customer for a completed order.
     *
     * The one method of this service that was not ported. Its only caller is
     * Order::afterSave(), which was not ported either, so nothing named it and
     * nothing missed it - the port saved orders and credited no points at all
     * while Yii 1 credited every one.
     *
     * `status != 0` is Yii 1's test and is kept: 0 is a completed sale here,
     * and a held or cancelled order earns nothing. The settings row's
     * is_active defaults to on when it is missing, as it does there.
     */
    public static function processOrderEarn($order)
    {
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

        $loyalty = CustomerLoyalty::getOrCreate($order->customer_id);

        return $loyalty->addPoints(
            $earnedPoints,
            $order->id,
            "Earned {$earnedPoints} points for order #{$order->id}"
        );
    }

    public static function processOrderRedeem($orderId, $pointsToRedeem)
    {
        $order = Order::findOne($orderId);
        if (!$order || !$order->customer_id) {
            return false;
        }
        if ($pointsToRedeem < self::minRedeem()) {
            return false;
        }

        $loyalty = CustomerLoyalty::getOrCreate($order->customer_id);
        return $loyalty->redeemPoints(
            $pointsToRedeem,
            $order->id,
            "Redeemed {$pointsToRedeem} points for order #{$order->bill_no}"
        );
    }

    /**
     * Reserves points before the order exists, recording a REDEEM row with a
     * null order_id that updateRedemptionWithOrderId() later fills in.
     */
    public static function preProcessRedemption($customerId, $pointsToRedeem)
    {
        $minRedeem = self::minRedeem();
        if ($pointsToRedeem < $minRedeem) {
            return [
                'success' => false,
                'message' => "Minimum {$minRedeem} points required for redemption",
            ];
        }

        $loyalty = CustomerLoyalty::getOrCreate($customerId);
        if ($loyalty->total_points < $pointsToRedeem) {
            return [
                'success' => false,
                'message' => 'Insufficient points available',
            ];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $loyalty->total_points -= $pointsToRedeem;
            $loyalty->lifetime_redeemed += $pointsToRedeem;
            $loyalty->save(false);

            $trans = new LoyaltyTransaction();
            $trans->customer_id = $customerId;
            $trans->order_id = null;
            $trans->transaction_type = LoyaltyTransaction::TYPE_REDEEM;
            $trans->points = $pointsToRedeem;
            $trans->description = "Pre-redeemed {$pointsToRedeem} points (pending order)";
            $trans->save(false);

            $transaction->commit();

            return [
                'success' => true,
                // Yii 1 returned this as a string, because it came straight from
                // \PDO::lastInsertId(). Yii 2's ActiveRecord casts the primary key
                // to int, which would change the JSON type for every existing
                // client. Cast back so the payload is unchanged.
                'redemption_id' => (string)$trans->id,
                'remaining_points' => $loyalty->total_points,
                'message' => 'Points pre-redeemed successfully',
            ];
        } catch (Throwable $e) {
            $transaction->rollBack();
            return [
                'success' => false,
                'message' => 'Failed to process redemption: ' . $e->getMessage(),
            ];
        }
    }

    public static function updateRedemptionWithOrderId($redemptionId, $orderId)
    {
        try {
            $trans = LoyaltyTransaction::findOne($redemptionId);
            if (!$trans) {
                return false;
            }
            // Only an unattached redemption may be claimed by an order.
            if ($trans->transaction_type !== LoyaltyTransaction::TYPE_REDEEM || $trans->order_id !== null) {
                return false;
            }

            $trans->order_id = $orderId;
            $trans->description = "Redeemed {$trans->points} points for order #{$orderId}";

            return (bool)$trans->save(false);
        } catch (Throwable $e) {
            return false;
        }
    }

    /** Returns reserved points to the customer when an order does not complete. */
    public static function rollbackPreRedemption($redemptionId)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $trans = LoyaltyTransaction::findOne($redemptionId);

            if (!$trans || $trans->transaction_type !== LoyaltyTransaction::TYPE_REDEEM) {
                $transaction->rollBack();
                return false;
            }
            // Already attached to an order: the redemption stands.
            if ($trans->order_id !== null) {
                $transaction->rollBack();
                return false;
            }

            $loyalty = CustomerLoyalty::getOrCreate($trans->customer_id);
            $loyalty->total_points += $trans->points;
            $loyalty->lifetime_redeemed -= $trans->points;
            $loyalty->save(false);

            $trans->transaction_type = LoyaltyTransaction::TYPE_ADJUST;
            $trans->points = -$trans->points;       // negative marks the reversal
            $trans->description = 'Rollback - ' . $trans->description;
            $trans->save(false);

            $transaction->commit();
            return true;
        } catch (Throwable $e) {
            $transaction->rollBack();
            return false;
        }
    }

    /**
     * Claws back earned points when items on an order are refunded.
     *
     * @return int|false points actually deducted, or false
     */
    public static function processRefundDeductPoints($orderId, $earnPoints = 0)
    {
        $earnPoints = intval($earnPoints);
        if ($earnPoints <= 0) {
            return false;
        }

        $order = Order::findOne($orderId);
        if (!$order || !$order->customer_id) {
            return false;
        }

        $loyalty = CustomerLoyalty::getOrCreate($order->customer_id);
        // Never drive the balance negative.
        $pointsToDeduct = min($earnPoints, $loyalty->total_points);
        if ($pointsToDeduct <= 0) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $loyalty->total_points -= $pointsToDeduct;
            $loyalty->lifetime_earned -= $pointsToDeduct;
            $loyalty->save(false);

            $trans = new LoyaltyTransaction();
            $trans->customer_id = $order->customer_id;
            $trans->order_id = $orderId;
            $trans->transaction_type = LoyaltyTransaction::TYPE_ADJUST;
            $trans->points = -$pointsToDeduct;
            $trans->description = "Deducted {$pointsToDeduct} earned points due to refund on order #{$orderId}";
            $trans->save(false);

            $transaction->commit();
            return $pointsToDeduct;
        } catch (Throwable $e) {
            $transaction->rollBack();
            return false;
        }
    }
}
