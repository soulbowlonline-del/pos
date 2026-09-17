<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/Customer.php (Yii 1). */
class Customer extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%customer}}';
    }

    public static function findByPhone($phone)
    {
        return static::findOne(['contact_no' => $phone]);
    }

    public static function getUserByContactNo($contactNo)
    {
        return static::findOne(['contact_no' => $contactNo]);
    }

    /**
     * Payload from Customer::toArray().
     *
     * Note the side effect, carried over deliberately: reading a customer
     * creates a zeroed loyalty row if none exists. That means the list actions
     * write, which is surprising but is what the Yii 1 version does, and
     * changing it here would make the two implementations disagree.
     */
    public function toApiArray()
    {
        return [
            'id' => (string)$this->id,
            'name' => isset($this->name) ? $this->name : '',
            'contact_no' => isset($this->contact_no) ? $this->contact_no : '',
            'loyalty' => CustomerLoyalty::getOrCreate($this->id)->asArray(),
        ];
    }

    /** Payload from Customer::toArray1() - the list variant, with is_enable_wa. */
    public function toApiArray1()
    {
        return [
            'id' => (string)$this->id,
            'name' => isset($this->name) ? $this->name : '',
            'contact_no' => isset($this->contact_no) ? $this->contact_no : '',
            'is_enable_wa' => isset($this->is_enable_wa)
                ? (string)$this->is_enable_wa
                : 0,
            'loyalty' => CustomerLoyalty::getOrCreate($this->id)->asArray(),
        ];
    }
}
