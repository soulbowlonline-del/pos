<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/Item.php (Yii 1). */
class Item extends ActiveRecord
{
    public const STATUS_ACTIVE = 0;

    public static function tableName()
    {
        return '{{%item}}';
    }

    /** Unit labels, indexed as stored. From BaseItem::getMeasurementTypeOptions(). */
    public static function getMeasurementTypeOptions($id = null)
    {
        $list = [
            'PCS-PIECES',
            'Box',
            'Case',
            'KGS-KILOGRAMS',
            'ML',
            'NOS',
            'PCS',
            'PETI',
            'TIN',
        ];
        if ($id === null) {
            return $list;
        }
        if (is_numeric($id)) {
            // Yii 1 indexed directly; an out-of-range unit yielded null.
            return isset($list[$id]) ? $list[$id] : null;
        }
        return $id;
    }

    public function getCategory()
    {
        return $this->hasOne(ItemCategory::class, ['id' => 'category_id']);
    }

    public function getSubcategory()
    {
        return $this->hasOne(ItemCategory::class, ['id' => 'sub_category_id']);
    }

    public function getCompany()
    {
        return $this->hasOne(ItemCompany::class, ['id' => 'company_id']);
    }

    /**
     * Yii 1's getItemBarcodes(): despite the plural, this returns the bar code
     * of the item's first active detail row, or '' when it has none.
     */
    public function getItemBarcodes()
    {
        $detail = ItemDetail::find()
            ->where(['item_id' => $this->id, 'status' => ItemDetail::STATUS_ACTIVE])
            ->orderBy(['id' => SORT_ASC])
            ->one();

        return $detail ? $detail->bar_code : '';
    }

    /**
     * Yii 1's getTotalRemainingQuantity(): positive stock movements less the
     * absolute value of the negative ones, to three decimal places via bcsub.
     * Rows with no item_detail_id are excluded. Two queries rather than a
     * single SUM because bcsub over the PHP-side accumulation is what produces
     * the string the API returns.
     */
    public function getTotalRemainingQuantity()
    {
        $add = '0.000';
        $sub = '0.000';

        $positive = ItemStock::find()
            ->where(['item_id' => $this->id])
            ->andWhere('balance_qty > 0.000')
            ->andWhere('item_detail_id IS NOT NULL')
            ->orderBy(['id' => SORT_ASC])
            ->all();
        foreach ($positive as $stock) {
            $add = $add + $stock->balance_qty;
        }

        $negative = ItemStock::find()
            ->where(['item_id' => $this->id])
            ->andWhere('balance_qty < 0.000')
            ->andWhere('item_detail_id IS NOT NULL')
            ->orderBy(['id' => SORT_ASC])
            ->all();
        foreach ($negative as $stock) {
            $sub = $sub + abs($stock->balance_qty);
        }

        return bcsub((string)$add, (string)$sub, 3);
    }

    /**
     * Yii 1 declares this on Item with the foreign key 'item_detail_id', so it
     * matches item_vendor rows whose item_detail_id equals this *item's* id.
     * That looks like a mistake in the relation - the column holds item detail
     * ids elsewhere - but item/barcode reads it, so it is reproduced as
     * declared rather than corrected. Ordered by id; Yii 1 leaves it unordered
     * and the caller takes [0].
     */
    public function getItemVendors()
    {
        return $this->hasMany(ItemVendor::class, ['item_detail_id' => 'id'])
            ->orderBy(['id' => SORT_ASC]);
    }

    /**
     * Yii 1's getOutletTotalRemainingQuantity(): the balance of one item detail
     * at one outlet, summed in PHP rather than by the database - which matters,
     * because it starts at the integer 0 and not the string '0.000', so the
     * result is a float where getTotalRemainingQuantity() returns a bcsub
     * string.
     */
    public function getOutletTotalRemainingQuantity($itemDetailId, $outletId)
    {
        $remaining = 0;
        $stocks = ItemStock::find()
            ->where(['item_id' => $this->id, 'item_detail_id' => $itemDetailId, 'outlet_id' => $outletId])
            ->orderBy(['id' => SORT_ASC])
            ->all();
        foreach ($stocks as $stock) {
            $remaining += $stock->balance_qty;
        }
        return $remaining;
    }
}
