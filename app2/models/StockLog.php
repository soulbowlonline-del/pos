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
    // the full set from BaseStockLog, so a caller cannot reach for one that
    // happens not to have been ported yet
    public const TYPE_ADDED = 0;
    public const TYPE_SUBSTRACTED = 1;
    public const TYPE_EXPIRED = 2;
    public const TYPE_RETURNED = 3;
    public const TYPE_ORDER = 4;
    public const TYPE_ADJUSTED = 5;
    public const TYPE_REFUND = 6;
    public const TYPE_B2B = 7;

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
