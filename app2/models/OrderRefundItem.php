<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/OrderRefundItem.php (Yii 1). */
class OrderRefundItem extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%order_refund_item}}';
    }
}
