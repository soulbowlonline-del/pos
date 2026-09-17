<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * Ported from protected/models/StockAdjustLog.php (Yii 1).
 *
 * The rules matter: the Yii 1 writer saves with validation on, so a row that
 * fails `required` is silently not written and the caller reports NOK. The
 * `default` rule nulls empty values, as elsewhere in this schema.
 */
class StockAdjustLog extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%stock_adjust_log}}';
    }

    public function rules()
    {
        return [
            [['date', 'item_detail_id', 'item_id', 'mrp', 'current_stock', 'actual_stock', 'adjusted'],
             'required'],
            [['item_detail_id', 'item_id', 'outlet_id', 'type_id', 'status'], 'integer'],
            [['mrp'], 'number'],
            [['create_time', 'update_time', 'remarks'], 'safe'],
            [['outlet_id', 'type_id', 'status', 'create_time', 'update_time'],
             'default', 'value' => null],
        ];
    }

    public function getCreateUser()
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
    }
}
