<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/OrderHold.php (Yii 1). */
class OrderHold extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%order_hold}}';
    }

    /** Payload from OrderHold::toArray1(). */
    public function toApiArray1()
    {
        return [
            'id' => (string)$this->id,
            'create_time' => $this->create_time,
        ];
    }
}
