<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/Tax.php (Yii 1). */
class Tax extends ActiveRecord
{
    public const TYPE_GST = 0;
    public const TYPE_IGST = 1;

    public static function tableName()
    {
        return '{{%tax}}';
    }
}
