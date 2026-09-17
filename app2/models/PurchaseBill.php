<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/PurchaseBill.php (Yii 1). */
class PurchaseBill extends ActiveRecord
{
    public const STATUS_UNAPPROVED = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_RECEIVED = 2;

    public static function tableName()
    {
        return '{{%purchase_bill}}';
    }

    public function getVendor()
    {
        return $this->hasOne(Vendor::class, ['id' => 'vendor_id']);
    }
}
