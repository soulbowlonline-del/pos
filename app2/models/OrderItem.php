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

    public function getTax()
    {
        return $this->hasOne(Tax::class, ['id' => 'tax_id']);
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

    /**
     * Payload from OrderItem::toArray1(), which differs from toArray() in
     * exactly three places, all of them key names rather than values:
     * no hsn_code, tax_amt instead of tax_amount, and cess_amount/igst_amount
     * instead of cess_amt/igst_amt. Built by rewriting toApiArray() rather
     * than duplicating sixty lines, so the two cannot drift apart.
     */
    public function toApiArray1($return = 0)
    {
        $row = $this->toApiArray($return);

        unset($row['hsn_code']);

        $renamed = [];
        foreach ($row as $key => $value) {
            if ($key === 'tax_amount') {
                $renamed['tax_amt'] = $value;
            } elseif ($key === 'cess_amt') {
                $renamed['cess_amount'] = $value;
            } elseif ($key === 'igst_amt') {
                $renamed['igst_amount'] = $value;
            } else {
                $renamed[$key] = $value;
            }
        }

        return $renamed;
    }

    /**
     * Payload from OrderItem::getTaxArray(), used by order/reprint for the tax
     * summary lines. The Yii 1 version opens with a loop that sums cgst, sgst,
     * cess and igst across every line sharing this order and tax id, and then
     * never reads the totals - the keys it emits are this line's own amounts.
     * The loop is left out here rather than reproduced: it costs a query per
     * summary row and cannot affect the output.
     *
     * cgst_per and friends come off the Tax row as tax_val1..tax_val4. That
     * mapping is the one recorded in docs/live-bugs-found.md as suspect
     * (getSgstPercent() reads tax_val1 elsewhere); reproduced, not corrected.
     */
    public function getTaxApiArray()
    {
        $order = Order::findOne($this->order_id);
        $itemDetail = $this->itemDetail;

        $out = [];
        $out['hsn_code'] = $this->item ? $this->item->hsn_code : '';
        $out['total_amt'] = $this->total_amt;
        $out['price'] = $this->qty * $this->price;
        $out['tax_amount'] = $this->tax_amount;
        $out['unit_name'] = $this->item ? Item::getMeasurementTypeOptions($this->item->unit) : '';
        $out['qty'] = $this->qty;
        // Yii 1 emits the stringified column; Yii 2's AR casts it to int
        $out['tax_id'] = $this->tax_id === null ? null : (string)$this->tax_id;
        $out['tax_percent'] = $itemDetail->getItemTaxPercent();
        $out['cgst_per'] = $this->tax ? $this->tax->tax_val1 : '';
        $out['sgst_per'] = $this->tax ? $this->tax->tax_val2 : '';
        $out['cess_per'] = $this->tax ? $this->tax->tax_val3 : '';
        $out['igst_per'] = $this->tax ? $this->tax->tax_val4 : '';
        $out['cgst_amt'] = $this->cgst_amt;
        $out['sgst_amt'] = $this->sgst_amt;
        $out['cess_amount'] = $this->cess_amt;
        $out['igst_amount'] = $this->igst_amt;
        if ($order) {
            $out['bill_date'] = date('d-m-Y', strtotime($order->bill_date));
        }

        return $out;
    }

    /**
     * GxActiveRecord::remove_format() in Yii 1 - every model inherits it.
     * Strips thousands separators from a posted number.
     */
    public function remove_format($text)
    {
        return str_replace(',', '', $text);
    }

    /**
     * Yii 1's getTaxValueID(): an IGST tax maps to the GST tax whose two halves
     * add up to the same rate; anything else maps to itself.
     *
     * The final else reads $tax->id when $tax is null, so an unknown tax id is
     * a fatal. Reproduced.
     */
    public function getTaxValueID($taxId)
    {
        $tax = Tax::findOne($taxId);
        if ($tax) {
            if ($tax->type_id == Tax::TYPE_IGST) {
                $half = $tax->tax_val4 / 2;
                $gst = Tax::find()
                    ->where('tax_val1 = :a', [':a' => $half])
                    ->andWhere('tax_val2 = :b', [':b' => $half])
                    ->andWhere('type_id = :t', [':t' => Tax::TYPE_GST])
                    ->orderBy(['id' => SORT_ASC])
                    ->one();
                if ($gst) {
                    return $gst->id;
                }
            } else {
                return $tax->id;
            }
        }
        return $tax->id;   // null here in Yii 1 too
    }
}
