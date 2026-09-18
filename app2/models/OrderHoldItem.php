<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/OrderHoldItem.php (Yii 1). */
class OrderHoldItem extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%order_hold_item}}';
    }

    public function getItemDetail()
    {
        return $this->hasOne(ItemDetail::class, ['id' => 'item_detail_id']);
    }

    public function getItem()
    {
        return $this->hasOne(Item::class, ['id' => 'item_id']);
    }

    public function getDiscount()
    {
        return $this->hasOne(Discount::class, ['id' => 'discount_id']);
    }

    /**
     * Payload from OrderHoldItem::toArray().
     *
     * Deliberately not shared with OrderItem::toApiArray(): the key names differ
     * (tax_amt rather than tax_amount, cess_amount/igst_amount rather than
     * cess_amt/igst_amt), there is no hsn_code and no refund adjustment. Held
     * orders have not been billed, so nothing has been refunded against them.
     */
    public function toApiArray()
    {
        $itemDetail = $this->itemDetail;

        $json = [];
        $json['item_id'] = (string)$itemDetail->id;
        $json['bar_code'] = $itemDetail->bar_code;
        $json['item_name'] = isset($itemDetail->item) ? $itemDetail->item->title : '';
        $json['item_desc'] = isset($itemDetail->item) ? $itemDetail->item->short_name : '';
        $json['unit_name'] = isset($this->item)
            ? Item::getMeasurementTypeOptions($this->item->unit)
            : '';
        $json['is_coupon'] = isset($this->item) && $this->item->is_coupon !== null
            ? (string)$this->item->is_coupon
            : '';
        $json['box'] = 0;
        $json['qty'] = $this->qty;
        $json['stock_qty'] = $itemDetail->getStockQty();
        $json['sale_rate'] = $this->sale_rate;
        $json['base_price'] = $this->price;
        $json['mrp'] = $this->mrp;

        $json['batch_numbers'] = '';
        $itemStock = $itemDetail->itemStock;
        if (!empty($itemStock)) {
            $json['batch_numbers'] = $itemStock->batch_number;
        }

        $json['discount_id'] = $this->discount_id === null ? null : (string)$this->discount_id;
        $json['discount_val'] = isset($this->discount) ? $this->discount->amount : '0';
        $json['discount_type'] = isset($this->discount) ? (string)$this->discount->type_id : '1';
        $json['discount_amt'] = $this->discount_amt;
        $json['tax_id'] = $this->tax_id === null ? null : (string)$this->tax_id;
        $json['tax_percent'] = $itemDetail->getItemTaxPercent();
        $json['tax_amt'] = $this->tax_amount;
        $json['total_amount'] = $this->total_amt;
        $json['cgst_per'] = $this->cgst_per;
        $json['sgst_per'] = $this->sgst_per;
        $json['cess_per'] = $this->cess_per;
        $json['igst_per'] = $this->igst_per;
        $json['cgst_amt'] = $this->cgst_amt;
        $json['sgst_amt'] = $this->sgst_amt;
        $json['cess_amount'] = $this->cess_amt;
        $json['igst_amount'] = $this->igst_amt;

        return $json;
    }

    /**
     * GxActiveRecord::remove_format() in Yii 1, which every model inherits -
     * including this one, which item/order calls it on for a held order.
     * app2's models extend yii\db\ActiveRecord directly, so the ones that need
     * it carry their own copy.
     */
    public function remove_format($text)
    {
        return str_replace(',', '', $text);
    }
}
