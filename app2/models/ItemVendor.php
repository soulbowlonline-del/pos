<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/ItemVendor.php (Yii 1). */
class ItemVendor extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%item_vendor}}';
    }

    public function getVendor()
    {
        return $this->hasOne(Vendor::class, ['id' => 'vendor_id']);
    }
}
