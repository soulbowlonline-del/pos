<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/ItemDiscount.php (Yii 1). */
class ItemDiscount extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%item_discount}}';
    }
}
