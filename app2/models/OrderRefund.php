<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/OrderRefund.php (Yii 1). */
class OrderRefund extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%order_refund}}';
    }
}
