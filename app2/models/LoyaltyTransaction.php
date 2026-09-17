<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/LoyaltyTransaction.php (Yii 1). */
class LoyaltyTransaction extends ActiveRecord
{
    public const TYPE_EARN = 'EARN';
    public const TYPE_REDEEM = 'REDEEM';
    public const TYPE_BONUS = 'BONUS';
    public const TYPE_EXPIRE = 'EXPIRE';
    public const TYPE_ADJUST = 'ADJUST';

    public static function tableName()
    {
        return '{{%loyalty_transactions}}';
    }

    /**
     * created_at is a DEFAULT_GENERATED CURRENT_TIMESTAMP column, so it is left
     * to the database exactly as the Yii 1 model did - no TimestampBehavior.
     */
    public function rules()
    {
        return [
            [['customer_id', 'transaction_type', 'points'], 'required'],
            [['customer_id', 'order_id', 'created_by'], 'integer'],
            [['points', 'reference_amount'], 'number'],
            [['description'], 'string', 'max' => 255],
            [['transaction_type'], 'in', 'range' => [
                self::TYPE_EARN, self::TYPE_REDEEM, self::TYPE_BONUS,
                self::TYPE_EXPIRE, self::TYPE_ADJUST,
            ]],
        ];
    }
}
