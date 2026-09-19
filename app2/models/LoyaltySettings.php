<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/** Ported from protected/models/LoyaltySettings.php (Yii 1). */
class LoyaltySettings extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%loyalty_settings}}';
    }

    /**
     * @return array setting_key => setting_value, as LoyaltyService::getSettings()
     *               built it on the Yii 1 side
     */
    public static function asMap()
    {
        $map = [];
        foreach (static::find()->all() as $row) {
            $map[$row->setting_key] = $row->setting_value;
        }
        return $map;
    }

    /**
     * The settings the application reads through this model.
     *
     * The Yii 1 model carries these; the port was written for the API, which
     * only ever needed the map, so the class arrived with tableName() and
     * asMap() and nothing else. Four loyaltyAdmin pages call them directly
     * from their views, and settings died on "Call to undefined method
     * LoyaltySettings::getEarnRate()".
     */
    public static function getValue($key, $defaultValue = null)
    {
        $setting = static::findOne(['setting_key' => $key]);
        return $setting ? $setting->setting_value : $defaultValue;
    }

    public static function setValue($key, $value, $description = null)
    {
        $setting = static::findOne(['setting_key' => $key]);

        if (!$setting) {
            $setting = new self();
            $setting->setting_key = $key;
        }

        $setting->setting_value = $value;
        if ($description !== null) {
            $setting->description = $description;
        }

        return $setting->save();
    }

    public static function getAllSettings()
    {
        $settings = static::find()->all();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->setting_key] = $setting->setting_value;
        }

        return $result;
    }

    public static function getLoyaltySettings()
    {
        $defaults = [
            'earn_rate' => 1,
            'min_redeem_points' => 50,
            'point_value' => 1,
            'expiry_months' => 12,
            'is_active' => 1,
        ];

        $settings = self::getAllSettings();

        return array_merge($defaults, $settings);
    }

    public static function initializeDefaults()
    {
        $defaults = [
            'earn_rate' => ['value' => '1', 'description' => 'Points earned per 100 spent'],
            'min_redeem_points' => ['value' => '50', 'description' => 'Minimum points required for redemption'],
            'point_value' => ['value' => '1', 'description' => 'Value of 1 point in currency'],
            'expiry_months' => ['value' => '12', 'description' => 'Points expiry in months (0 = no expiry)'],
            'is_active' => ['value' => '1', 'description' => 'Loyalty program active status'],
        ];

        $transaction = Yii::$app->db->beginTransaction();

        try {
            foreach ($defaults as $key => $data) {
                $existing = static::findOne(['setting_key' => $key]);
                if (!$existing) {
                    $setting = new self();
                    $setting->setting_key = $key;
                    $setting->setting_value = $data['value'];
                    $setting->description = $data['description'];
                    $setting->save();
                }
            }

            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            // rollback() in Yii 1, rollBack() here.
            $transaction->rollBack();
            return false;
        }
    }

    public static function isLoyaltyActive()
    {
        return (bool) self::getValue('is_active', 0);
    }

    public static function getEarnRate()
    {
        return (float) self::getValue('earn_rate', 1);
    }

    public static function getMinRedeemPoints()
    {
        return (int) self::getValue('min_redeem_points', 50);
    }

    public static function getPointValue()
    {
        return (float) self::getValue('point_value', 1);
    }

    public static function getExpiryMonths()
    {
        return (int) self::getValue('expiry_months', 12);
    }
}
