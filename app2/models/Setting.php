<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/Setting.php (Yii 1). */
class Setting extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%setting}}';
    }
}
