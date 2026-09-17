<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/Emp.php (Yii 1). Only outlet_id is read here. */
class Emp extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%emp}}';
    }
}
