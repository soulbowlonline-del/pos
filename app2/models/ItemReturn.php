<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/ItemReturn.php (Yii 1). */
class ItemReturn extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%item_return}}';
    }

    public function getVendor()
    {
        return $this->hasOne(Vendor::class, ['id' => 'vendor_id']);
    }
}
