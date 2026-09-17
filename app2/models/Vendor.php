<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/Vendor.php (Yii 1). */
class Vendor extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%vendor}}';
    }

    /** A vendor may hang off a parent whose name is used in reporting. */
    public function getParentvendor()
    {
        return $this->hasOne(Vendor::class, ['id' => 'parent_id']);
    }
}
