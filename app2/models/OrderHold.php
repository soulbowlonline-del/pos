<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/OrderHold.php (Yii 1). */
class OrderHold extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%order_hold}}';
    }

    public function getModePayment()
    {
        return $this->hasOne(PaymentMode::class, ['id' => 'mode_of_payment']);
    }

    public function getModeDelivery()
    {
        return $this->hasOne(PaymentMode::class, ['id' => 'mode_of_delivery']);
    }

    public function getOrderItems()
    {
        // Line items had no explicit order, so the two frameworks listed
        // them differently within the same order. Ordered by id on both.
        // Yii 1 returns held-order lines in descending id order. Its relation
        // has no ORDER BY and the 'order' option on a Yii 1 HAS_MANY did not
        // take effect here, so that sequence is the database's rather than
        // anything chosen; this matches it explicitly so the two agree. When
        // this read path is served only by Yii 2 the order can be revisited.
        return $this->hasMany(OrderHoldItem::class, ['order_hold_id' => 'id'])
            ->orderBy(['id' => SORT_DESC]);
    }

    /**
     * Payload from OrderHold::toArray().
     *
     * Note total_amt is the sale total plus the discount, i.e. the gross before
     * discount - the opposite sense to Order::toArray(), where total_amt is the
     * net. Reproduced as-is; the two payloads genuinely differ.
     */
    public function toApiArray()
    {
        $json = [];
        $json['id'] = (string)$this->id;
        $json['mode_of_payment'] = isset($this->modePayment) ? $this->modePayment->title : '';
        $json['mode_of_delivery'] = isset($this->modeDelivery) ? $this->modeDelivery->title : '';
        $json['qty'] = $this->qty === null ? null : (string)$this->qty;
        $json['discount_amt'] = $this->discount_amt;
        $json['total_sale'] = $this->total_amt;
        $json['total_amt'] = $this->total_amt + $this->discount_amt;
        $json['paid_amt'] = $this->paid_amt;
        $json['status'] = $this->status === null ? null : (string)$this->status;
        $json['type_id'] = $this->type_id === null ? null : (string)$this->type_id;
        $json['city_id'] = $this->city_id === null ? null : (string)$this->city_id;
        $json['state_id'] = $this->state_id === null ? null : (string)$this->state_id;
        $json['country_id'] = $this->country_id === null ? null : (string)$this->country_id;
        $json['address'] = $this->address;
        $json['note'] = $this->note;
        $json['create_time'] = $this->create_time;
        $json['customer_id'] = $this->customer_id === null ? null : (string)$this->customer_id;

        $items = [];
        foreach ($this->orderItems as $item) {
            $items[] = $item->toApiArray();
        }
        $json['order_items'] = $items;

        return $json;
    }

    /** Payload from OrderHold::toArray1(). */
    public function toApiArray1()
    {
        return [
            'id' => (string)$this->id,
            'create_time' => $this->create_time,
        ];
    }
}
