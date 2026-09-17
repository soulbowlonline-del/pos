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
}
