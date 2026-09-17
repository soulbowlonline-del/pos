<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/Tax.php (Yii 1). */
class Tax extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%tax}}';
    }
}
