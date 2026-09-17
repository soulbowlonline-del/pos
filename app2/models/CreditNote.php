<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/CreditNote.php (Yii 1). */
class CreditNote extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%credit_note}}';
    }
}
