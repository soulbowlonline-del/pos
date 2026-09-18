<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/MrsAdjust.php (Yii 1). */
class MrsAdjust extends ActiveRecord
{
    public const STATUS_PENDING = 0;
    public const STATUS_DONE = 1;

    public static function tableName()
    {
        return '{{%mrs_adjust}}';
    }
}
