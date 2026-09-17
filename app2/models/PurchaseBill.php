<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/PurchaseBill.php (Yii 1). */
class PurchaseBill extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%purchase_bill}}';
    }
}
