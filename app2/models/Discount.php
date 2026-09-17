<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/Discount.php (Yii 1). */
class Discount extends ActiveRecord
{
    public const STATUS_ACTIVE = 0;
    public const TYPE_PERCENTAGE = 1;

    public static function tableName()
    {
        return '{{%discount}}';
    }

    public static function getTypeOptions($id = null)
    {
        $list = ['amount', '%age'];
        if ($id === null) {
            return $list;
        }
        if (is_numeric($id)) {
            return isset($list[$id]) ? $list[$id] : null;
        }
        return $id;
    }

    public static function getDiscountTypeOptions($id = null)
    {
        $list = ['Order', 'Item'];
        if ($id === null) {
            return $list;
        }
        if (is_numeric($id)) {
            return isset($list[$id]) ? $list[$id] : null;
        }
        return $id;
    }

    /** Payload from Discount::toArray(), key for key. */
    public function toApiArray()
    {
        return [
            'id' => (string)$this->id,
            'title' => $this->title,
            'amount' => $this->amount,
            'applicable_amt' => $this->applicable_amt,
            'type_id' => $this->type_id === null ? null : (string)$this->type_id,
            'type_name' => self::getTypeOptions($this->type_id),
            'discount_type' => $this->discount_type === null ? null : (string)$this->discount_type,
            'discount_type_name' => self::getDiscountTypeOptions($this->discount_type),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
        ];
    }
}
