<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/B2bPurchaseBill.php (Yii 1). */
class B2bPurchaseBill extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%b2bpurchase_bill}}';
    }

    /** tally/b2bsales reaches the vendor's state through this. */
    public function getVendor()
    {
        return $this->hasOne(Vendor::class, ['id' => 'vendor_id']);
    }
}
