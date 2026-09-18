<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/Organization.php (Yii 1). */
class Organization extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%organization}}';
    }
}
