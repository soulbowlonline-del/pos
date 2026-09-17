<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/Mrs.php (Yii 1) - a material requisition. */
class Mrs extends ActiveRecord
{
    public const STATUS_PENDING = 0;

    public static function tableName()
    {
        return '{{%mrs}}';
    }
}
