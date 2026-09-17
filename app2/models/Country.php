<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/Country.php (Yii 1). */
class Country extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%country}}';
    }

    /**
     * Payload from Country::toArray(). Named toApiArray() because yii\base\Model
     * declares toArray() as part of Arrayable.
     *
     * id is cast to string: Yii 1 served every column as a string and Yii 2's
     * ActiveRecord type-casts from the schema.
     */
    public function toApiArray()
    {
        return [
            'id' => (string)$this->id,
            'title' => isset($this->title) ? $this->title : '',
        ];
    }
}
