<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/ItemDetail.php (Yii 1). */
class ItemDetail extends ActiveRecord
{
    public const STATUS_ACTIVE = 0;

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

    /** Status labels, from BaseItemDetail::getStatusOptions(). */
    public static function getStatusOptions($id = null)
    {
        $list = ['Active', 'InActive'];
        if ($id === null) {
            return $list;
        }
        if (is_numeric($id)) {
            return isset($list[$id]) ? $list[$id] : null;
        }
        return $id;
    }

    /** Id of the tax row that applies, or 0. From getItemTax(). */
    public function getItemTax()
    {
        $tax = $this->getItemDetailTax();
        return $tax ? $tax->id : 0;
    }

    private function itemRow()
    {
        return Item::findOne($this->item_id);
    }

    public function getCategoryTitle()
    {
        $item = $this->itemRow();
        return ($item && $item->category) ? $item->category->title : '';
    }

    public function getCompanyTitle()
    {
        $item = $this->itemRow();
        return ($item && $item->company) ? $item->company->title : '';
    }

    public function getSubcategoryTitle()
    {
        $item = $this->itemRow();
        return ($item && $item->subcategory) ? $item->subcategory->title : '';
    }

    /**
     * Payload from ItemDetail::toonlineArray() - the catalogue export shape.
     *
     * The odd key names (Short_x0020_Name, Pur_x0020_Price) are XML-encoded
     * spaces from whatever consumes this feed, and are reproduced exactly.
     *
     * Note 'Tax' is only present when a tax row is found, matching the Yii 1
     * version, which sets the key inside the if.
     */
    public function toOnlineApiArray()
    {
        $item = $this->item;

        $json = [];
        $json['Code'] = isset($item) ? $item->item_code : '';
        $json['Name'] = isset($item) ? $item->title : '';
        $json['Short_x0020_Name'] = isset($item) ? $item->short_name : '';
        $json['Barcode'] = $this->bar_code;

        $tax = Tax::findOne($this->getItemTax());
        if ($tax) {
            $json['Tax'] = $tax->title;
        }

        if ($this->mrp != '0.00') {
            $json['MRP'] = isset($this->mrp) ? $this->mrp : '';
        } else {
            $json['MRP'] = isset($item) ? $item->mrp : '';
        }

        $json['PRICE'] = isset($item) ? $item->sale_price : '';
        $json['OPStock'] = $this->getStockQty();
        $json['Pur_x0020_Price'] = isset($item) ? $item->purchase_price : '';
        $json['Pur_x0020_Value'] = isset($item) ? $item->purchase_price : '';
        $json['Weight'] = isset($item) ? $item->weight : '';
        $json['Department'] = isset($item) ? $this->getCategoryTitle() : '';
        $json['Company'] = isset($item) ? $this->getCompanyTitle() : '';
        $json['Sub_x0020_Category'] = isset($item) ? $this->getSubcategoryTitle() : '';
        $json['ProdEx1'] = '';
        $json['ProdEx2'] = '';
        $json['ProdEx3'] = '';
        $json['ProdEx4'] = '';
        $json['Active'] = self::getStatusOptions($this->status);

        return $json;
    }
}
