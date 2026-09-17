<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/Outlet.php (Yii 1). */
class Outlet extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%outlet}}';
    }
}
