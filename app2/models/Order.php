<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * Partial Yii 2 port of protected/models/Order.php (1,246 lines).
 *
 * Ported so far: toArray1() and the helpers it needs. toArray() is 248 lines
 * and toArray2() another 90, both with their own dependency trees; they move in
 * a later increment along with the order write paths.
 */
class Order extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%order}}';
    }

    public function getCustomer()
    {
        return $this->hasOne(Customer::class, ['id' => 'customer_id']);
    }

    public function getOutlet()
    {
        return $this->hasOne(Outlet::class, ['id' => 'outlet_id']);
    }

    /**
     * Formatted bill number, reproduced from Order::getOrderBillNo().
     *
     * The financial year runs April to March, so a bill dated in months 4-12
     * belongs to year..year+1 and one dated in months 1-3 to year-1..year.
     *
     * The outlet prefix logic is inverted in the Yii 1 original:
     *
     *     if ($outlet->bill_prefix == '') { $bill_prefix = $outlet->bill_prefix; }
     *     else                            { $bill_prefix = 'B'; }
     *
     * An outlet with a configured prefix has it discarded in favour of 'B', and
     * an outlet with an empty one ends up with ''. That looks unintended - the
     * same logic in toArray1()/toArray2() is written the other way round - but
     * it is what currently produces every bill number, so it is reproduced here
     * rather than quietly corrected. Fixing it would change bill numbers on
     * printed invoices and needs to be a deliberate, separate decision.
     */
    public function getOrderBillNo()
    {
        $billNo = $this->bill_no;
        $ts = strtotime((string)$this->bill_date);
        $month = (int)date('m', $ts);

        if ($month > 3) {
            $year = (int)date('Y', $ts);
            $yearLast = $year + 1;
        } else {
            $year = (int)date('Y', $ts) - 1;
            $yearLast = (int)date('Y', $ts);
        }

        $billPrefix = 'B';
        $outlet = Outlet::findOne($this->outlet_id);
        if ($outlet) {
            $billPrefix = ($outlet->bill_prefix == '') ? $outlet->bill_prefix : 'B';
        }

        return 'Gst ' . $year . '-' . $yearLast . '/' . $billPrefix . '-' . $billNo;
    }

    /** Order quantity less anything refunded. From getOrderAfterRefundQty(). */
    public function getOrderAfterRefundQty()
    {
        $itemQty = (float)OrderItem::find()
            ->where(['order_id' => $this->id])
            ->sum('qty');

        $refundIds = OrderRefund::find()
            ->select('id')
            ->where(['order_id' => $this->id])
            ->column();

        if ($refundIds) {
            $refund = 0;
            foreach ($refundIds as $refundId) {
                $refund += (float)OrderRefundItem::find()
                    ->where(['order_refund_id' => $refundId])
                    ->sum('qty');
            }
            if ($refund != 0) {
                $itemQty = $itemQty - $refund;
            }
        }

        return round($itemQty);
    }

    /** Order total less anything refunded. From getOrderAfterRefundAmount(). */
    public function getOrderAfterRefundAmount()
    {
        $amount = $this->total_amt;

        $refundOrders = OrderRefund::findAll(['order_id' => $this->id]);
        if ($refundOrders) {
            $refund = 0;
            foreach ($refundOrders as $refundOrder) {
                $refund += $refundOrder->total_amt;
            }
            if ($refund != 0) {
                $amount = $amount - $refund;
            }
        }

        return round($amount);
    }

    /**
     * Payload from Order::toArray1() - the compact list form.
     *
     * Named toApiArray1() because yii\base\Model declares toArray() as part of
     * Arrayable; the numbered variants follow the same convention for clarity.
     */
    public function toApiArray1()
    {
        return [
            'id' => (string)$this->id,
            'bill_no' => $this->getOrderBillNo(),
            'bill_date' => $this->bill_date,
            'create_time' => $this->create_time,
            'customer_name' => isset($this->customer) ? $this->customer->name : '',
            'total_amt' => $this->getOrderAfterRefundAmount(),
            'qty' => $this->getOrderAfterRefundQty(),
            'customer_id' => isset($this->customer_id) ? (string)$this->customer_id : '',
            'is_enable_wa' => isset($this->customer->is_enable_wa)
                ? (string)$this->customer->is_enable_wa
                : '0',
        ];
    }
}
