<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/UserRole.php (Yii 1). */
class UserRole extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%user_role}}';
    }
}
