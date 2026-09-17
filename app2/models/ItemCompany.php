<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/ItemCompany.php (Yii 1). */
class ItemCompany extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%item_company}}';
    }
}
