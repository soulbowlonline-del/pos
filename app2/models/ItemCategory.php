<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/ItemCategory.php (Yii 1). */
class ItemCategory extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%item_category}}';
    }
}
