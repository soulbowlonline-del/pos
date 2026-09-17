<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/Mrn.php (Yii 1) - a goods receipt note. */
class Mrn extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%mrn}}';
    }
}
