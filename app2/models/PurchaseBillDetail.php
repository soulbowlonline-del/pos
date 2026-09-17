<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * Minimal Yii 2 PurchaseBillDetail.
 *
 * Only the sale-tax lookup the item payload needs is ported. The Yii 1 model is
 * 670 lines and its toArray1() drives the Tally purchase reports through eleven
 * tax arithmetic helpers; those move with the purchase and billing module.
 */
class PurchaseBillDetail extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%purchase_bill_detail}}';
    }

    /** Latest sale_tax_id recorded against an item detail, or 0. */
    public static function getLatestSaleTaxIdByItemDetailId($itemDetailId)
    {
        $detail = static::find()
            ->where(['item_detail_id' => $itemDetailId])
            ->orderBy(['id' => SORT_DESC])
            ->limit(1)
            ->one();
        return $detail ? $detail->sale_tax_id : 0;
    }

    public function getItem()
    {
        return $this->hasOne(Item::class, ['id' => 'item_id']);
    }
}
