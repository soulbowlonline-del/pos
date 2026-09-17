<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/Customer.php (Yii 1). */
class Customer extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%customer}}';
    }

    public static function findByPhone($phone)
    {
        return static::findOne(['contact_no' => $phone]);
    }
}
