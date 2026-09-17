<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/WhatsappLogs.php (Yii 1). */
class WhatsappLogs extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%whatsapp_logs}}';
    }
}
