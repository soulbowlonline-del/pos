<?php
namespace app\models;

use Yii;

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
        return $this->hasMany(OrderItem::class, ['order_id' => 'id'])
            ->orderBy(['id' => SORT_ASC]);
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

    /**
     * Payload from Order::toArray() - the full form, with line items.
     *
     * The Yii 1 version computes a $bill_prefix at the top of the method and
     * never uses it (getOrderBillNo() derives its own), so it is not reproduced.
     */
    public function toApiArray()
    {
        $json = [];
        $json['id'] = (string)$this->id;
        $json['bill_no'] = $this->getOrderBillNo();
        $json['bill_date'] = $this->bill_date;
        $json['mode_of_payment'] = isset($this->modePayment) ? $this->modePayment->title : '';
        $json['mode_of_delivery'] = isset($this->modeDelivery) ? $this->modeDelivery->title : '';
        $json['qty'] = $this->qty === null ? null : (string)$this->qty;
        $json['discount_amt'] = $this->discount_amt;
        $json['total_amt'] = $this->total_amt;
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
        foreach ($this->orderItems as $orderItem) {
            $items[] = $orderItem->toApiArray();
        }
        $json['order_items'] = $items;

        return $json;
    }

    /**
     * Payload from Order::toArray2() - the bill view, with loyalty totals.
     *
     * Differs from toApiArray() in three ways: total_amt here is the gross
     * (sale total plus discount) with the net exposed separately as total_sale,
     * the loyalty figures are included, and the line items are rendered in
     * their "return" form, which emits is_return rather than box.
     */
    public function toApiArray2()
    {
        $json = [];
        $json['id'] = (string)$this->id;
        $json['bill_no'] = $this->getOrderBillNo();
        $json['bill_date'] = $this->bill_date;
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
        $json['is_mobile'] = $this->is_mobile === null ? null : (string)$this->is_mobile;
        $json['gross_total_amt'] = $this->gross_total_amt;
        $json['customer_name'] = isset($this->customer) ? $this->customer->name : '';

        // Most recent redemption against this order, if any.
        $redeemed = LoyaltyTransaction::find()
            ->where(['order_id' => $this->id, 'transaction_type' => LoyaltyTransaction::TYPE_REDEEM])
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC]) // id breaks the 1-second tie
            ->one();
        $json['redeemed_points'] = $redeemed ? $redeemed->points : 0;

        $json['lifetime_earn'] = LoyaltyTransaction::getLoyaltyLifetimeEarnedPoints($this->customer_id);
        $json['lifetime_redeem'] = LoyaltyTransaction::getLoyaltyLifetimeRedeemedPoints($this->customer_id);
        $json['current_bill_earn'] = LoyaltyTransaction::getLoyaltyCurrentBillEarnedPoints($this->customer_id, $this->id);

        $items = [];
        foreach ($this->orderItems as $orderItem) {
            // $return = 1 switches the item payload's 'box' key to 'is_return'.
            $items[] = $orderItem->toApiArray(1);
        }
        $json['order_items'] = $items;

        return $json;
    }

    /**
     * Yii 1's UpdateStock(): takes $qty off this order's outlet stock for one
     * item detail, writes a StockLog line, and raises a requisition if the
     * result falls to or below the item's minimum.
     *
     * Three branches, and they are not symmetric:
     *
     *   - enough in the first positive-balance row: deduct, log the quantity.
     *   - not enough: zero that row, log what was actually there, then recurse
     *     with the remainder - so a sale spanning three batches writes three
     *     log lines. The recursion is on the same item detail, so it picks up
     *     the next positive row each time.
     *   - no positive row at all: the balance is driven negative. This branch
     *     reads getStockQty() *after* saving, so previous_qty is computed from
     *     the new figure rather than the old one, unlike the other two, which
     *     use the locked read. Reproduced.
     *
     * The first two lock the stock rows with SELECT ... FOR UPDATE before
     * computing the figure they log, so a concurrent sale cannot move it
     * between the read and the write. The third does not.
     */
    public function UpdateStock($qty, $itemDetailId)
    {
        $itemDetail = ItemDetail::findOne($itemDetailId);
        // touched before the null check below, as in Yii 1
        $itemDetail->update_time = date('Y-m-d H:i:s');
        $itemDetail->save(false, ['update_time']);

        if (!$itemDetail) {
            return;
        }

        $item = Item::findOne($itemDetail->item_id);
        $remainQty = $qty;
        $quantity = $qty;

        $vendorId = 0;
        if ($item !== null) {
            $item->update_time = date('Y-m-d H:i:s');
            $item->save(false, ['update_time']);
            // item_detail_id matched against the ITEM id - the same mismatch
            // noted on Item::getItemVendors()
            $vendor = ItemVendor::find()
                ->where('item_detail_id = :i', [':i' => $item->id])
                ->orderBy(['id' => SORT_DESC])
                ->one();
            if ($vendor) {
                $vendorId = $vendor->vendor_id;
            }
        }

        $itemStock = ItemStock::find()
            ->where([
                'item_id' => $itemDetail->item_id,
                'item_detail_id' => $itemDetail->id,
                'outlet_id' => $this->outlet_id,
            ])
            ->andWhere('balance_qty > 0')
            ->orderBy(['id' => SORT_ASC])
            ->one();

        if ($itemStock) {
            if ($itemStock->balance_qty >= $quantity) {
                $itemStock->balance_qty = $itemStock->balance_qty - $quantity;
                $itemStock->tax_id = $itemDetail->tax_id;
                if ($itemStock->save()) {
                    $currentQty = $this->lockedStockQty($itemDetail);
                    $this->writeOrderStockLog($itemDetail, $item, $itemStock, $vendorId,
                        $currentQty, $currentQty + $quantity, $quantity);
                    if ($itemStock->isnetLessMin()) {
                        $itemStock->createMrs();
                    }
                }
                return true;
            }

            $balance = $itemStock->balance_qty;
            $itemStock->balance_qty = 0;
            $itemStock->tax_id = $itemDetail->tax_id;
            if ($itemStock->save()) {
                $currentQty = $this->lockedStockQty($itemDetail);
                $this->writeOrderStockLog($itemDetail, $item, $itemStock, $vendorId,
                    $currentQty, $currentQty + $balance, abs($balance));
                if ($itemStock->isnetLessMin()) {
                    $itemStock->createMrs();
                }
            }

            $remainQty = $remainQty - $balance;
            if ($remainQty > 0) {
                $this->UpdateStock($remainQty, $itemDetailId);
            }
            return;
        }

        // nothing positive to take from: drive the balance negative
        $itemStock = ItemStock::find()
            ->where([
                'item_id' => $itemDetail->item_id,
                'item_detail_id' => $itemDetail->id,
                'outlet_id' => $this->outlet_id,
            ])
            ->orderBy(['id' => SORT_ASC])
            ->one();

        if (!$itemStock) {
            return;
        }

        $balance = $itemStock->balance_qty;
        if ($balance == 0) {
            $itemStock->balance_qty = bcsub((string)$itemStock->balance_qty, (string)$quantity, 3);
        }
        if ($balance < 0) {
            $itemStock->balance_qty = '-' . bcadd((string)abs($itemStock->balance_qty), (string)$quantity, 3);
        }
        $itemStock->tax_id = $itemDetail->tax_id;

        if ($itemStock->save()) {
            // getStockQty() after the save, so this reads the new figure
            $this->writeOrderStockLog($itemDetail, $item, $itemStock, $vendorId,
                $itemDetail->getStockQty(), $itemDetail->getStockQty() + $quantity, $quantity);
            if ($itemStock->isnetLessMin()) {
                $itemStock->createMrs();
            }
        }
    }

    /** The balance across rows locked for this transaction. */
    private function lockedStockQty($itemDetail)
    {
        $rows = Yii::$app->db->createCommand(
            'SELECT balance_qty FROM tbl_item_stock
              WHERE item_id = :item_id AND item_detail_id IS NOT NULL
              ORDER BY id ASC FOR UPDATE',
            [':item_id' => (int)$itemDetail->item_id]
        )->queryAll(\PDO::FETCH_NUM);

        return $itemDetail->calculateLockedStockQty($rows);
    }

    private function writeOrderStockLog($itemDetail, $item, $itemStock, $vendorId, $current, $previous, $qty)
    {
        $log = new StockLog();
        $log->item_detail_id = $itemDetail->id;
        $log->item_id = $item->id;
        $log->batch_no = $itemStock->batch_number;
        $log->current_qty = $current;
        $log->previous_qty = $previous;
        $log->Qty = $qty;
        $log->outlet_id = $this->outlet_id;
        if ($vendorId != null) {
            $log->vendor_id = $vendorId;
        }
        $log->type_id = StockLog::TYPE_ORDER;
        $log->save();
    }

    /**
     * Yii 1's SendSms(): a templated order confirmation through uengage.
     *
     * The API token was a literal in protected/models/Order.php and is read
     * from the environment now - it is in this repository's history and needs
     * rotating. Goes through the outbound stub when one is configured, like
     * every other outward call.
     *
     * Yii 1 wraps the cURL in a try/catch that cannot fire (curl_exec does not
     * throw), and sends nothing at all when the customer has no phone number.
     */
    public function SendSms()
    {
        $customer = Customer::findOne($this->customer_id);
        if (!$customer) {
            return;
        }
        $contact = $customer->contact_no;
        if ($contact == '') {
            return;
        }

        if ($this->online_order_id === null) {
            $orderNo = $this->bill_no;
        } else {
            $onlineOrder = OnlineOrder::findOne($this->online_order_id);
            $orderNo = $onlineOrder ? $onlineOrder->order_id : $this->bill_no;
        }

        $url = 'https://www.uengage.in/ueapi/sendTemplate';
        $fields = [
            'longSms' => '1',
            'apiToken' => getenv('POS_UENGAGE_TOKEN'),
            'mobileNo' => $contact,
            'senderId' => 'SOLBOL',
            'templateId' => '2422',
            'param' => $customer->name . '::' . $orderNo . '::In and Out::' . $this->bill_no
                . '::http://61.2.241.71/pos/order/pdf?id=' . $this->id,
        ];

        if (class_exists('PosOutbound') && \PosOutbound::isStubbed()) {
            \PosOutbound::intercept(\PosOutbound::CHANNEL_HTTP, 'POST ' . $url, $fields);
            return;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'Order' : 'Orders';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'address';
    }

    /** GxActiveRecord::__toString(): the representing column, or the id. */
    public function __toString()
    {
        $value = $this->hasAttribute('address') ? $this->address : null;

        return (string) ($value === null || $value === '' ? $this->id : $value);
    }

    /**
     * The ordering Yii 1's defaultScope() put on every query for this
     * model. Null means Yii 1 applied none, and neither should this:
     * an order Yii 1 never applied is an order the user never saw.
     */
    public static function defaultOrder()
    {
        return null;
    }

    /**
     * GxActiveRecord::isAllowCreate(): whether the session the operator
     * has selected is the current financial year.
     *
     * The year runs April to March, so a month past April belongs to
     * year..year+1 and anything earlier to year-1..year. Session names
     * are '<from>-<to>'. False when no session is selected, which is what
     * stops the create button appearing.
     */
    public function isAllowCreate()
    {
        $month = (int) date('m');
        $year = $month > 4 ? (int) date('Y') : (int) date('Y') - 1;
        $yearadd = $year + 1;

        $selected = Yii::$app->session['select_session_id'];
        if ($selected === null || $selected === '') {
            return false;
        }

        $session = Session::findOne($selected);
        if ($session === null) {
            return false;
        }
        $parts = explode('-', $session->name);

        return isset($parts[0], $parts[1])
            && $parts[0] == $year && $parts[1] == $yearadd;
    }

    /** Views ask the model whether the current role may reach a route. */
    public function checkPermission($url)
    {
        return \app\components\Access::check($url);
    }

    /**
     * GxActiveRecord::getRelationLabel(). The generated attributeLabels()
     * above already resolves a relation or foreign key to the related
     * model's label, so this is the attribute label.
     */
    public function getRelationLabel($name, $n = null)
    {
        return $this->getAttributeLabel($name);
    }

    /**
     * GxActiveRecord::getTotals(): the SUM of one column over a set of
     * ids, which the grids use for a footer row.
     *
     * The column and table names are interpolated, as in Yii 1 - the
     * call sites pass literals. The ids are bound, which Yii 1 did not:
     * they come from the data provider rather than the request, so this
     * is not a fix for anything, only a refusal to build the same hole
     * again.
     */
    public function getTotals($ids, $columnname, $tablename)
    {
        if (empty($ids)) {
            return null;
        }

        $placeholders = [];
        $params = [];
        foreach (array_values($ids) as $i => $id) {
            $placeholders[] = ':id' . $i;
            $params[':id' . $i] = $id;
        }

        return Yii::$app->db->createCommand(
            'SELECT SUM(' . $columnname . ') FROM ' . $tablename
            . ' WHERE id IN (' . implode(',', $placeholders) . ')', $params)
            ->queryScalar();
    }

    public static function getTypeOptions($id = null)
    {
		$list = ["Offline","Online"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public static function getPaymentTypeOptions($id = null)
    {
		$list = ["B2B","Expense","Cash"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public static function getDeliveryTypeOptions($id = null)
    {
		$list = ["Offline","Online"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public static function getStatusOptions($id = null)
    {
		$list = ["Draft","Published","Archive"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public function getCity()
    {
        return $this->hasOne(City::class, ['id' => 'city_id']);
    }

    public function getCountry()
    {
        return $this->hasOne(Country::class, ['id' => 'country_id']);
    }

    public function getCreateUser()
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
    }

    public function getState()
    {
        return $this->hasOne(State::class, ['id' => 'state_id']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }

    public function getOrderRefunds()
    {
        return $this->hasMany(OrderRefund::class, ['order_id' => 'id']);
    }
}
