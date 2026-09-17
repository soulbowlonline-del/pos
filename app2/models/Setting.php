<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/Setting.php (Yii 1). */
class Setting extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%setting}}';
    }

    /** Payload from Setting::toArray(). */
    public function toApiArray()
    {
        return [
            'days' => $this->days === null ? null : (string)$this->days,
            'date' => date('d/m/Y', strtotime((string)$this->create_time)),
            'current_date' => date('d/m/Y'),
        ];
    }
}
