<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/OnlineOrderItem.php (Yii 1). */
class OnlineOrderItem extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%online_order_item}}';
    }
}
