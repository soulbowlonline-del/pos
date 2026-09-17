<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/MrsDetail.php (Yii 1) - a requisition line. */
class MrsDetail extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%mrs_detail}}';
    }
}
