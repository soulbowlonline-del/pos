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

    /**
     * BaseWhatsappLogs requires number and message, and the Yii 1 caller saves
     * with validation on - so a send with no phone number, or one that comes
     * back with no message, silently logs nothing. Reproduced here because the
     * row's absence is observable: a blank contact_no leaves no trace either
     * way, and skipping validation would have the ported stack write a row
     * where the old one did not.
     */
    public function rules()
    {
        return [
            [['number', 'message'], 'required'],
            [['id'], 'integer'],
        ];
    }
}
