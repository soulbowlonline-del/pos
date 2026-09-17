<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * Minimal Yii 2 Order model.
 *
 * Only the fields the loyalty flow reads (customer_id, bill_no) are exercised
 * here. The full Order model is a much larger porting job and belongs with the
 * order module, not with loyalty.
 */
class Order extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%order}}';
    }
}
