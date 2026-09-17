<?php
namespace app\models;

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
}
