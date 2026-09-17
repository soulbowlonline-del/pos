<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/ItemDetail.php (Yii 1). */
class ItemDetail extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%item_detail}}';
    }

    public function getItem()
    {
        return $this->hasOne(Item::class, ['id' => 'item_id']);
    }

    public function getItemStock()
    {
        // An item detail has one stock row per batch. Neither framework
        // ordered this relation, so each picked whichever row the database
        // happened to return and the batch number on an order line was
        // effectively arbitrary - in practice the highest id, i.e. the most
        // recent batch. That is now explicit on both sides.
        return $this->hasOne(ItemStock::class, ['item_detail_id' => 'id'])
            ->orderBy(['id' => SORT_DESC]);
    }

    /**
     * Stock on hand: positive balances less the absolute value of negative ones.
     *
     * Reproduced from ItemDetail::getStockQty(), including its use of bcsub at
     * scale 3 - which returns a string, and that string is what the API has
     * always emitted for stock_qty.
     */
    public function getStockQty()
    {
        $add = '0.000';
        $sub = '0.000';

        $positive = ItemStock::find()
            ->where(['item_id' => $this->item_id])
            ->andWhere(['>', 'balance_qty', 0])
            ->andWhere(['is not', 'item_detail_id', null])
            ->orderBy(['id' => SORT_ASC])
            ->all();
        foreach ($positive as $stock) {
            $add = $add + $stock->balance_qty;
        }

        $negative = ItemStock::find()
            ->where(['item_id' => $this->item_id])
            ->andWhere(['<', 'balance_qty', 0])
            ->andWhere(['is not', 'item_detail_id', null])
            ->orderBy(['id' => SORT_ASC])
            ->all();
        foreach ($negative as $stock) {
            $sub = $sub + abs($stock->balance_qty);
        }

        return bcsub((string)$add, (string)$sub, 3);
    }

    /**
     * The tax row that applies, preferring the most recent purchase sale tax
     * over the item's own when they differ. From getItemDetailTax().
     */
    public function getItemDetailTax()
    {
        $saleTaxId = PurchaseBillDetail::getLatestSaleTaxIdByItemDetailId($this->id);
        $taxId = $this->tax_id;
        if ($saleTaxId && $saleTaxId != $this->tax_id) {
            $taxId = $saleTaxId;
        }
        return Tax::findOne(['id' => $taxId]);
    }

    /** Combined tax percentage across the four components. */
    public function getItemTaxPercent()
    {
        $tax = $this->getItemDetailTax();
        if (!$tax) {
            return 0;
        }
        return $tax->tax_val1 + $tax->tax_val2 + $tax->tax_val3 + $tax->tax_val4;
    }
}
