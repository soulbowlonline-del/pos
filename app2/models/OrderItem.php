<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * Partial Yii 2 port of protected/models/OrderItem.php (2,809 lines).
 *
 * toArray() and the relations it needs are ported; the reporting column
 * builders (getTaxColumns, getGroupHSNTaxColumns, getColumns and the rest, some
 * 1,700 lines between them) drive the Excel reports and move with those.
 */
class OrderItem extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%order_item}}';
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

    public function getItemOrderMrp()
    {
        return $this->mrp;
    }

    /**
     * Payload from OrderItem::toArray(), key for key and in the same order.
     *
     * Quantities and totals are reported net of any refund against the same
     * item on the same order, which is why the refund lookup happens first.
     *
     * @param int $return 0 emits a 'box' key, anything else emits 'is_return'
     */
    public function toApiArray($return = 0)
    {
        // Yii 1 takes the first refund for the order, not all of them.
        $refundOrderItem = null;
        $refundOrder = OrderRefund::find()->where(['order_id' => $this->order_id])->one();
        if ($refundOrder) {
            $refundOrderItem = OrderRefundItem::find()
                ->where([
                    'order_refund_id' => $refundOrder->id,
                    'item_detail_id' => $this->item_detail_id,
                    'item_id' => $this->item_id,
                ])
                ->one();
        }

        $itemDetail = $this->itemDetail;
        $json = [];
        $json['item_id'] = (string)$itemDetail->id;
        $json['bar_code'] = $itemDetail->bar_code;
        $json['item_name'] = isset($itemDetail->item) ? $itemDetail->item->title : '';
        $json['item_desc'] = isset($itemDetail->item) ? $itemDetail->item->short_name : '';
        $json['hsn_code'] = isset($itemDetail->item) ? $itemDetail->item->hsn_code : '';
        $json['unit_name'] = isset($this->item)
            ? Item::getMeasurementTypeOptions($this->item->unit)
            : '';
        $json['is_coupon'] = isset($this->item) && $this->item->is_coupon !== null
            ? (string)$this->item->is_coupon
            : '';

        if ($return == 0) {
            $json['box'] = 0;
        } else {
            $json['is_return'] = 0;
        }

        $json['qty'] = $refundOrderItem === null
            ? $this->qty
            : $this->qty - $refundOrderItem->qty;

        $json['stock_qty'] = $itemDetail->getStockQty();
        $json['sale_rate'] = $this->sale_rate;
        $json['base_price'] = $this->price;
        $json['mrp'] = $this->getItemOrderMrp();

        $json['batch_numbers'] = '';
        $itemStock = $itemDetail->itemStock;
        if (!empty($itemStock)) {
            $json['batch_numbers'] = $itemStock->batch_number;
        }

        $json['discount_id'] = $this->discount_id === null ? null : (string)$this->discount_id;
        $json['discount_val'] = isset($this->discount) ? $this->discount->amount : '0';
        $json['discount_type'] = isset($this->discount)
            ? (string)$this->discount->type_id
            : '1';
        $json['discount_amt'] = $this->discount_amt;
        $json['tax_id'] = $this->tax_id === null ? null : (string)$this->tax_id;
        $json['tax_percent'] = $itemDetail->getItemTaxPercent();
        $json['tax_amount'] = $this->tax_amount;

        $json['total_amount'] = $refundOrderItem === null
            ? $this->total_amt
            : $this->total_amt - $refundOrderItem->total_amt;

        $json['cgst_per'] = $this->cgst_per;
        $json['sgst_per'] = $this->sgst_per;
        $json['cess_per'] = $this->cess_per;
        $json['igst_per'] = $this->igst_per;
        $json['cgst_amt'] = $this->cgst_amt;
        $json['sgst_amt'] = $this->sgst_amt;
        $json['cess_amt'] = $this->cess_amt;
        $json['igst_amt'] = $this->igst_amt;

        if ($refundOrderItem === null) {
            $json['refund_qty'] = 0;
            $json['refund_amount'] = 0;
        } else {
            $json['refund_qty'] = $refundOrderItem->qty;
            $json['refund_amount'] = $refundOrderItem->total_amt;
        }

        return $json;
    }
}
