<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * Ported from protected/models/StockLog.php (Yii 1).
 *
 * Note the capitalised `Qty` column - it is spelt that way in the table and the
 * Yii 1 model, and the refund path writes it.
 */
class StockLog extends ActiveRecord
{
    public const TYPE_REFUND = 6;

    public static function tableName()
    {
        return '{{%stock_log}}';
    }

    public function rules()
    {
        return [
            [['item_detail_id', 'item_id', 'batch_no', 'Qty'], 'required'],
            [['item_detail_id', 'item_id', 'outlet_id', 'vendor_id', 'type_id', 'status'], 'integer'],
            [['batch_no'], 'string', 'max' => 255],
            [['create_time', 'update_time', 'Qty', 'current_qty', 'previous_qty'], 'safe'],
            [['outlet_id', 'vendor_id', 'type_id', 'status', 'create_time', 'update_time'],
             'default', 'value' => null],
        ];
    }
}
