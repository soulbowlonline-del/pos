<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/ScannedItems.php (Yii 1). */
class ScannedItems extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%scanned_items}}';
    }

    public function rules()
    {
        return [
            [['computer_name', 'user_id', 'item_id'], 'required'],
            [['user_id', 'item_id'], 'integer'],
            [['computer_name'], 'string', 'max' => 255],
            [['is_coupon', 'created_at'], 'safe'],
            [['is_coupon'], 'default', 'value' => null],
        ];
    }
}
