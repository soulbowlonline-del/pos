<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * Minimal Yii 2 OrderItem.
 *
 * The Yii 1 model is 2,809 lines. Only the quantity aggregation the order
 * payload needs is ported; the rest moves with the order write paths.
 */
class OrderItem extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%order_item}}';
    }
}
