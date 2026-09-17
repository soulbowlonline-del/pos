<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/ItemStock.php (Yii 1). */
class ItemStock extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%item_stock}}';
    }
}
