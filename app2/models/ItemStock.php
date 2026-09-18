<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/ItemStock.php (Yii 1). */
class ItemStock extends ActiveRecord
{
    public const TYPE_ADDED = 0;
    public const TYPE_SUBSTRACT = 1;

    public static function tableName()
    {
        return '{{%item_stock}}';
    }
}
