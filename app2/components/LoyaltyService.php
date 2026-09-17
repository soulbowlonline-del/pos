<?php
namespace app\components;

use app\models\CustomerLoyalty;
use app\models\LoyaltySettings;

/**
 * Yii 2 port of the read path of protected/components/LoyaltyService.php.
 *
 * Only getCustomerLoyaltyInfo() is ported so far. The write paths
 * (preProcessRedemption, updateRedemptionWithOrderId, and the earn/redeem
 * transaction handling) remain on the Yii 1 side and are still served from
 * /api/loyalty/*. Defaults below are copied verbatim from the Yii 1 version so
 * a missing setting behaves identically.
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
}
