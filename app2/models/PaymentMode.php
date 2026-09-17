<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/PaymentMode.php (Yii 1). */
class PaymentMode extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%payment_mode}}';
    }
}
