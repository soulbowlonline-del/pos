<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/RolePermission.php (Yii 1). */
class RolePermission extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%role_permission}}';
    }
}
