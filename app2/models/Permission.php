<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/Permission.php (Yii 1). */
class Permission extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%permission}}';
    }
}
