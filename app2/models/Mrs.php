<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/Mrs.php (Yii 1) - a material requisition. */
class Mrs extends ActiveRecord
{
    // the full set from BaseMrs
    public const STATUS_PENDING = 0;
    public const STATUS_DONE = 1;
    public const STATUS_HALF_DONE = 2;
    public const STATUS_REJECT = 3;

    public static function tableName()
    {
        return '{{%mrs}}';
    }
}
