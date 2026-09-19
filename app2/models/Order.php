<?php
namespace app\models;

use app\components\Criteria;

use app\components\Ui;

use yii\data\ActiveDataProvider;

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
    // Declared on the Yii 1 model and not columns: the forms post
    // to these and the actions assign them.
    public $start_date;
    public $end_date;
    public $columns;
    public $item_id;
    public $min_amt;
    public $max_amt;

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

    /** GxActiveRecord::getRelatedDataProvider(): the rows of a relation. */
    public function getRelatedDataProvider($relation, $config = [])
    {
        $getter = 'get' . ucfirst($relation);
        if (!method_exists($this, $getter)) {
            throw new \yii\base\InvalidArgumentException(
                get_class($this) . ' does not have relation "' . $relation . '".');
        }

        return new ActiveDataProvider(array_merge(
            ['query' => $this->$getter(), 'pagination' => ['pageSize' => Ui::PAGE_SIZE]],
            $config));
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'qty' => 'Qty',
            'discount_amt' => 'Discount Amt',
            'total_amt' => 'Total Amt',
            'min_amt' => 'Minimum Total Amount',
            'max_amt' => 'Maximum Total Amount',
            'paid_amt' => 'Paid Amt',
            'status' => 'Status',
            'type_id' => 'Type',
            'city_id' => 'City',
            'state_id' => 'State',
            'country_id' => 'Country',
            'address' => 'Address',
            'note' => 'Note',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'customer_id' => 'Customer',
            'updated_by' => 'User',
            'city' => 'City',
            'country' => 'Country',
            'customer' => 'Customer',
            'state' => 'State',
            'updatedBy' => 'User',
            'orderItems' => 'OrderItems',
            'orderRefunds' => 'OrderRefunds',
        ];
    }

    public function processLoyaltyEarning() {
        // Process loyalty points after order is completed
                if ($this->status == 0 && $this->customer_id) {
                        return LoyaltyService::processOrderEarn($this);
                }
                return false;
        }

    public function getLoyaltyEarnedPoints() {
                if ($this->customer_id) {
                        return LoyaltyService::calculateEarnedPoints($this->total_amt);
                }
                return 0;
        }

    public function getCustomerLoyaltyInfo() {
                if ($this->customer_id) {
                        return LoyaltyService::getCustomerLoyaltyInfo($this->customer_id);
                }
                return null;
        }

    public static function getOrderRecord(){
            // One grouped scan instead of 12 separate full-table COUNTs over tbl_order
            // (~1.3M rows). Returns the same 12 comma-separated monthly counts (Jan..Dec).
            $list = array_fill(1, 12, 0);
            // Cache this historical monthly aggregate for 1h (opt-in, this query only).
            // A dashboard chart of monthly order counts tolerates up-to-1h staleness.
            $rows = Yii::$app->db->cache(3600)->createCommand()
                ->select('MONTH(create_time) AS m, COUNT(*) AS c')
                ->from(Order::model()->tableName())
                ->group('MONTH(create_time)')
                ->queryAll();
            foreach($rows as $row){
                $m = (int)$row['m'];
                if($m >= 1 && $m <= 12){
                    $list[$m] = (int)$row['c'];
                }
            }
            return implode(',', $list);
        }

    public function toArray1() {
            $model = $this;
            $json_entry = null;
            $bill_prefix = 'B';
            if ($model) {
                $outlet = Outlet::findOne($model->outlet_id);
                if($outlet){
                    if($outlet->bill_prefix != ''){
                        $bill_prefix = $outlet->bill_prefix;
                    }else{
                    $bill_prefix = 'B';
                    }

                }
                $json_list = [];
                $json_entry = [];
                $json_entry ['id'] = $model->id;
                $json_entry ['bill_no'] = $model->getOrderBillNo();
                $json_entry ['bill_date'] = $model->bill_date;
                $json_entry ['create_time'] = $model->create_time;
                $json_entry ['customer_name'] = isset($model->customer)?$model->customer->name:'';
                $json_entry ['total_amt'] = $model->getOrderAfterRefundAmount();
                $json_entry ['qty'] = $model->getOrderAfterRefundQty();

                $json_entry ['customer_id'] = isset($model->customer_id)?$model->customer_id:'';
                $json_entry ['is_enable_wa'] = isset($model->customer->is_enable_wa)?$model->customer->is_enable_wa:'0';

            }
            return $json_entry;
        }

    public function getColumns($selectcolumns = []){
            if(!empty($selectcolumns)){
                $selected = $selectcolumns;
            }else{
                $selected = [
                        'bill_no',
                                'bill_date',
                                'customer_id',
                                'mode_of_payment',
                                'employee_id',
                                'total_amt',
                                'discount_amt',
                                'refund_amt',
                                'refund_by',
                                'tax_amt',
                                'outlet'

                ];

            }

            if($selected){
                foreach($selected as $select){
                    if($select == 'bill_no'){
                        $columns[] = [
                                'label' => 'Bill No',
                                'value' => function ($data) {
                                return $data->getOrderBillNo();
                                }
                                ];
                    }
                    if($select == 'customer_id'){
                        $columns[] = [
                                'label' => 'Customer',
                                'value' => function ($data) {
                                    return isset ( $data->customer ) ? $data->customer : "";
                                }
                        ];
                    }
                    if($select == 'mode_of_payment'){
                        $columns[] = [
                                'label' => 'Mode Of Payment',
                                'value' => function ($data) {
                                    return isset ( $data->modePayment ) ? $data->modePayment : "";
                                }
                        ];
                    }
                    else if($select == 'employee_id'){
                        $columns[] = [
                                'label' => 'Employee',
                                'value' => function ($data) {
                                return isset ( $data->createUser ) ? $data->createUser : "";
                                }
                                ];
                    }

                    else if($select == 'outlet'){
                        $columns[] = [
                                'label' => 'Outlet',
                                'value' => function ($data) {
                                return isset ( $data->outlet ) ? $data->outlet : "";
                                }
                                ];
                    }
                    else if($select == 'tax_amt'){
                        $columns[] = [
                                'label' => 'Tax Amount',
                                'value' => function ($data) {
                                return $data->getOrderTaxAmount();
                                }
                                ];
                    }
                    else if($select == 'refund_amt'){
                        $columns[] = [
                                'label' => 'Refund Amount',
                                'value' => function ($data) {
                                return $data->getOrderRefundAmount();
                                }
                                ];
                    }
                    else if($select == 'refund_by'){
                        $columns[] = [
                                'label' => 'Refund By',
                                'value' => function ($data) {
                                return $data->getOrderRefundBy();
                                }
                                ];
                    }

                    else if($select == 'total_amt'){
                        $columns[] = [
                                'label' => 'Total Amount',
                                'value' => function ($data) {
                                return $data->getOrderTotalAmount();
                                }
                                ];
                    }else if($select == 'discount_amt'){
                        $columns[] = [
                                'label' => 'Total Discount',
                                'value' => function ($data) {
                                return $data->getOrderTotaldiscountAmount();
                                }
                                ];
                    }
                    else{
                        $columns[] = $select;
                    }
                }
            }

            /*     $columns[] =

            array (

                        'bill_no',
                        'bill_date',
                        array (
                                'label' => 'Customer',
                                'value' => function ($data) {
                                    return isset ( $data->customer ) ? $data->customer : "";
                                }
                        ),

                        'total_amt',
                        'discount_amt',
                        'paid_amt',
                        array (
                                'label' => 'Mode Of Payment',
                                'value' => function ($data) {
                                    return Order::getPaymentTypeOptions ( $data->mode_of_payment );
                                }
                        ),
                        array (
                                'label' => 'Mode Of Delivery',
                                'value' => function ($data) {
                                    return Order::getDeliveryTypeOptions ( $data->mode_of_delivery );
                                }
                        ),
                        array (
                                'label' => 'Order Type',
                                'value' => function ($data) {
                                    return Order::getTypeOptions ( $data->type_id );
                                }
                        ),


                        array (
                                'label' => 'Outlet',
                                'value' => function ($data) {
                                return isset ( $data->outlet ) ? $data->outlet : "";
                                }
                                )
                )*/


            return $columns;
        }

    public function getOrderTaxAmount(){
            $tax = 0;
            if ((Yii::$app->session ['order_start_date'] != '') && (Yii::$app->session ['order_end_date'] != '')) {
                if(($this->bill_date >= Yii::$app->session ['order_start_date']  ) && ($this->bill_date <= Yii::$app->session ['order_end_date'])){
                    $orderitems = OrderItem::findAll(['order_id'=>$this->id]);
                }else{
                    Yii::warning( var_export( $this->id , true), '$this->id');
                    return $tax;
                }
            }else{
            $orderitems = OrderItem::findAll(['order_id'=>$this->id]);
            }

            if($orderitems){
                foreach($orderitems as $orderitem){
                    $tax = $tax + $orderitem['tax_amount'];
                }
            }
            return $tax;
        }

    public function getOrderTotalAmount(){
            $total_amt = 0;

            if ((Yii::$app->session ['order_start_date'] != '') && (Yii::$app->session ['order_end_date'] != '')) {
                if(($this->bill_date >= Yii::$app->session ['order_start_date']  ) && ($this->bill_date <= Yii::$app->session ['order_end_date'])){
                    return $this->total_amt ;
                }else{

                    return $total_amt;
                }
            }else{
                $total_amt = $this->total_amt;
            }


            return $total_amt;
        }

    public function getOrderTotaldiscountAmount(){
            $total_amt = 0;

            if ((Yii::$app->session ['order_start_date'] != '') && (Yii::$app->session ['order_end_date'] != '')) {
                if(($this->bill_date >= Yii::$app->session ['order_start_date']  ) && ($this->bill_date <= Yii::$app->session ['order_end_date'])){
                    return $this->discount_amt ;
                }else{

                    return $total_amt;
                }
            }else{
                $total_amt = $this->discount_amt;
            }


            return $total_amt;
        }

    public function getOrderRefundAmount(){
            $total_amt = 0;
            if ((Yii::$app->session ['order_start_date'] != '') && (Yii::$app->session ['order_end_date'] != '')) {
                $orderrefund = OrderRefund::findOne(['order_id'=>$this->id]);
                if($orderrefund){
                    $refunddate = date('Y-m-d', strtotime($orderrefund->create_time));
                    if(($refunddate >= Yii::$app->session ['order_start_date']  ) && ($refunddate <= Yii::$app->session ['order_end_date'])){
                        $total_amt = $orderrefund->total_amt;
                    }
                }
            }else{
                $orderrefund = OrderRefund::findOne(['order_id'=>$this->id]);
                if($orderrefund){
                    $refunddate = date('Y-m-d', strtotime($orderrefund->create_time));

                        $total_amt = $orderrefund->total_amt;

                }
            }


            return $total_amt;
        }

    public function getOrderRefundBy(){

            $username = '';
            if ((Yii::$app->session ['order_start_date'] != '') && (Yii::$app->session ['order_end_date'] != '')) {
                $orderrefund = OrderRefund::findOne(['order_id'=>$this->id]);
                if($orderrefund){
                    $refunddate = date('Y-m-d', strtotime($orderrefund->create_time));
                    if(($refunddate >= Yii::$app->session ['order_start_date']  ) && ($refunddate <= Yii::$app->session ['order_end_date'])){
                        $orderRefundItem = OrderRefundItem::findOne(['order_refund_id'=>$orderrefund->id]);
                        if($orderRefundItem){
                            $username = isset($orderRefundItem->createUser)?$orderRefundItem->createUser:"";
                        }
                    }
                }
            }else{
                $orderrefund = OrderRefund::findOne(['order_id'=>$this->id]);
                if($orderrefund){
                    $orderRefundItem = OrderRefundItem::findOne(['order_refund_id'=>$orderrefund->id]);
                    if($orderRefundItem){
                        $username = isset($orderRefundItem->createUser)?$orderRefundItem->createUser:"";
                    }
                }
            }

            return $username;
        }

    public function getUserwiseColumns($selectcolumns = []){
            if(!empty($selectcolumns)){
                $selected = $selectcolumns;
            }else{
                $selected = [
                        'username' ,
                        'amount',


                ];

            }

            if($selected){
                foreach($selected as $select){
                    if($select == 'username'){
                        $columns[] = [
                                'label' => 'Username',
                                'value' => function ($data) {
                                return isset ( $data->createUser ) ? $data->createUser : "";
                                }
                                ];
                    }
                    else if($select == 'amount'){
                        $columns[] =[
                                'label' => 'Net Amount',
                                'value' => function ($data) {
                                return $data->getTotalNetAmount ();
                                }
                                ];
                    }

                    else{
                        $columns[] = $select;
                    }
                }
            }




            return $columns;
        }

    /**
     * The order this model's listings use.
     *
     * The grid's own sort when search() names one, otherwise whatever
     * defaultScope() applies. Both the admin grid and the index listing
     * read this, so the two cannot drift apart.
     */
    public static function listingOrder()
    {
        return ['id' => SORT_DESC];
    }

    public function getTotalGrossAmountData(){
             $total = 0;
            $query = OrderItem::find();
            if((Yii::$app->session['item_id'] != '')){
                $query->andWhere(['item_id' => Yii::$app->session['item_id']]);
            }
            if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query->select('sum(price*qty) as price,sum(tax_amount) as tax_amount ,sum(discount_amt) as discount_amt');
            $query->andWhere('create_user_id ='.$this->create_user_id);


            $orderitem = $query->one();
            $order_amt = $orderitem->price + $orderitem->tax_amount + $orderitem->discount_amt ;

            return round($order_amt);

        }

    public function getUserTotalRefundAmountData(){
            $total = 0;


            $query = OrderRefundItem::find();
            if((Yii::$app->session['item_id'] != '')){
                $query->andWhere(['item_id' => Yii::$app->session['item_id']]);
            }
            if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query->select('sum(price*qty) as price,sum(tax_amt) as tax_amt');
            $query->andWhere('create_user_id ='.$this->create_user_id);
            $orderrefunditem = $query->one();
            $order_refund_amt = $orderrefunditem->price + $orderrefunditem->tax_amt ;


            $total = $order_refund_amt;

            return round($total);
        }

    public function getUserTotalDiscountAmountData(){
            $total = 0;


            $query = Order::find();

            if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query->select('sum(discount_amt) as discount_amt');
            $query->andWhere('create_user_id ='.$this->create_user_id);
            $discountorder = $query->one();

            $total =  $discountorder->discount_amt;

            return round($total);
        }

    /**
     * GxActiveRecord::getItemOptions(): the active items, as id => 'title(mrp)',
     * for the item dropdowns.
     *
     * Restricted to a vendor's own items when the signed-in user holds the
     * Vendor role, and again when a vendor id is passed. Both filters compare
     * Item.id against ItemVendor.item_detail_id, which is what Yii 1 does. It
     * reads like a mistake, but it is the list these dropdowns have always
     * shown, so it is ported as it stands rather than corrected here.
     *
     * An empty id list is not "no filter": Yii 1's addInCondition() degrades to
     * 0=1 and ['id' => []] does the same, so a vendor with no items gets an
     * empty dropdown rather than every item in the catalogue.
     */
    public function getItemOptions($vendor_id = null)
    {
        $query = Item::find();

        $role = UserRole::findOne(['title' => 'Vendor']);
        $user = Yii::$app->user->model;
        if ($user && $role && $user->role_id == $role->id) {
            $query->andWhere(['id' => self::vendorItemDetailIds(
                ['create_user_id' => $user->id])]);
        }
        if ($vendor_id !== null) {
            $query->andWhere(['id' => self::vendorItemDetailIds(['id' => $vendor_id])]);
        }
        $query->andWhere('status = ' . Item::STATUS_ACTIVE);
        $query->orderBy('title asc');

        $list = [];
        foreach ($query->all() as $item) {
            $list[$item->id] = $item->title . '(' . $item->mrp . ')';
        }

        return $list;
    }

    /**
     * GxActiveRecord::getItemOptionIdsInBarcode(): the ids of the items an
     * itemDetail admin filter matches, which that grid then filters item_id by.
     *
     * The values are bound rather than interpolated into the condition as Yii 1
     * does. For every value the grid can actually produce the two are the same
     * query; this is not a fix for a reported problem, only a refusal to build
     * the same hole again.
     */
    public function getItemOptionIdsInBarcode($match_item_id, $match_mrp, $match_hsn_code,
        $match_product_code, $match_purchase_price, $match_company_id, $is_vendor)
    {
        $query = Item::find();

        if ($match_item_id != null) {
            $query->andWhere('title LIKE :title', [':title' => trim($match_item_id) . '%']);
        }
        if ($is_vendor == 1) {
            $user = Yii::$app->user->model;
            $query->andWhere(['id' => self::vendorItemDetailIds(
                ['create_user_id' => $user->id])]);
        }
        if ($match_company_id != null) {
            Criteria::compare($query, 'company_id', $match_company_id, true);
        }
        if ($match_mrp != null) {
            $query->andWhere(['mrp' => $match_mrp]);
        }
        if ($match_hsn_code != null) {
            $query->andWhere(['hsn_code' => $match_hsn_code]);
        }
        if ($match_product_code != null) {
            $query->andWhere(['item_code' => $match_product_code]);
        }
        if ($match_purchase_price != null) {
            Criteria::compare($query, 'purchase_price', $match_purchase_price);
        }

        return $query->select('id')->column();
    }

    /**
     * GxActiveRecord::getItemOptionIds(): the ids of the items the signed-in
     * user may see.
     *
     * getItemOptions() filters on status and this does not, because Yii 1
     * does not: the barcode dropdown this feeds lists inactive items too.
     */
    public function getItemOptionIds()
    {
        $query = Item::find();

        $role = UserRole::findOne(['title' => 'Vendor']);
        $user = Yii::$app->user->model;
        if ($user && $role && $user->role_id == $role->id) {
            $query->andWhere(['id' => self::vendorItemDetailIds(
                ['create_user_id' => $user->id])]);
        }

        return $query->select('id')->column();
    }

    /**
     * GxActiveRecord::getItemOptionbarcodes(): item detail id => bar code, for
     * the items getItemOptionIds() allows.
     */
    public function getItemOptionbarcodes()
    {
        $list = [];
        foreach (ItemDetail::find()->where(['item_id' => $this->getItemOptionIds()])
                     ->all() as $itemDetail) {
            $list[$itemDetail->id] = $itemDetail->bar_code;
        }

        return $list;
    }

    /** GxActiveRecord::getItemCustomerName(): the customer on this row's order. */
    public function getItemCustomerName()
    {
        $customer = Customer::findOne($this->order->customer_id);

        return $customer ? $customer->name : '';
    }

    /**
     * GxActiveRecord::getSessionStartDate(): 1 April of the selected session's
     * opening year, or '' when no session is selected.
     */
    public function getSessionStartDate()
    {
        $years = self::selectedSessionYears();

        return isset($years[0]) ? $years[0] . '-04-01' : '';
    }

    /** GxActiveRecord::getSessionEndDate(): 31 March of its closing year. */
    public function getSessionEndDate()
    {
        $years = self::selectedSessionYears();

        return isset($years[1]) ? $years[1] . '-03-31' : '';
    }

    /**
     * The two years in the selected session's name, which is '<from>-<to>'.
     * The financial year runs 1 April to 31 March, which is where the two
     * dates above come from.
     */
    private static function selectedSessionYears()
    {
        $id = Yii::$app->session['select_session_id'];
        if ($id === null || $id === '') {
            return [];
        }
        $session = Session::findOne($id);

        return $session ? explode('-', $session->name) : [];
    }

    /** GxActiveRecord::getVendorDataOptions(): the active vendors, id => name. */
    public function getVendorDataOptions()
    {
        $list = [];
        $query = Vendor::find()->where(['status' => Vendor::STATUS_ACTIVE]);
        // Yii 1 reaches these through findAllByAttributes(), which applies the
        // model's defaultScope; the order is what the dropdown shows.
        $query->orderBy(Vendor::defaultOrder() ?: []);
        foreach ($query->all() as $vendor) {
            $list[$vendor->id] = $vendor->name;
        }

        return $list;
    }

    /** The item_detail_ids ItemVendor holds for the matching vendor. */
    private static function vendorItemDetailIds($condition)
    {
        $vendor = Vendor::findOne($condition);
        if ($vendor === null) {
            return [];
        }

        return ItemVendor::find()->where(['vendor_id' => $vendor->id])
            ->select('item_detail_id')->column();
    }

    /**
     * GxActiveRecord::getCompanyBarcode(): 'readOnly' when the item
     * detail's bar code is the company's own, and an empty string
     * otherwise. The grids use the result as an html attribute, so a
     * barcode belonging to the company cannot be edited in place.
     */
    public function getCompanyBarcode($id)
    {
        $itemDetail = ItemDetail::findOne($id);

        return $itemDetail && $itemDetail->company_bar_code == ItemDetail::IS_COMPANY
            ? 'readOnly'
            : '';
    }

    public function getTotalNetAmount(){
             $total = 0;
            $query1 = OrderItem::find();
            if((Yii::$app->session['item_id'] != '')){
                $query1->andWhere(['item_id' => Yii::$app->session['item_id']]);
            }
            if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
                $query1->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query1->select('sum(price*qty) as price,sum(tax_amount) as tax_amount');
            $query1->andWhere('create_user_id ='.$this->create_user_id);
            $orderitem = $query1->one();
            $order_amt = $orderitem->price + $orderitem->tax_amount;

            $query2 = OrderRefundItem::find();
            if((Yii::$app->session['item_id'] != '')){
                $query2->andWhere(['item_id' => Yii::$app->session['item_id']]);
            }
            if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
                $query2->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query2->select('sum(price*qty) as price,sum(tax_amt) as tax_amt');
            $query2->andWhere('create_user_id ='.$this->create_user_id);
            $orderrefunditem = $query2->one();
            $order_refund_amt = $orderrefunditem->price + $orderrefunditem->tax_amt ;
            $total = $order_amt - $order_refund_amt;
            Yii::warning( var_export($order_amt, true), '$order_amt');
            Yii::warning( var_export($this->create_user_id, true), '$$this->create_user_id');
            Yii::warning( var_export($order_refund_amt, true), '$$order_refund_amt');


            $query1_2 = Order::find();

            if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
                $query1_2->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query1_2->select('sum(discount_amt) as discount_amt');
            $query1_2->andWhere('create_user_id ='.$this->create_user_id);
            $discountorder = $query1_2->one();
            //$total = $total - $discountorder->discount_amt;

            //Yii::warning( var_export($orderitems, true), '$orderitems');
            /* if($orderitems){

                foreach ($orderitems as $orderitem){
                    $qty = $orderitem->qty;

                    $refund = 0;
                    $query = OrderRefund::find();
                    $query->andWhere('order_id ='.$orderitem->order_id);
                    if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
                        $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
                    }
                    $orderRefund = $query->one();
                    if($orderRefund){
                        $query3 = OrderRefundItem::find();
                        $query3->andWhere('order_refund_id ='.$orderRefund->id);
                        $query3->andWhere('item_detail_id ='.$orderitem->item_detail_id);
                        if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
                            $query3->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
                        }
                        $query3->andWhere('item_id ='.$orderitem->item_id);
                        $query3->select('sum(total_amt) as total_amt');
                        $orderRefundItem = $query3->one();
                        $refund = $orderRefundItem->total_amt;

                    }
                    $amt = ($orderitem->total_amt) - ($refund);
                    $total = $total + $amt;
                    /* Yii::warning( var_export($orderitem->id, true), '$order_item_id');
                    Yii::warning( var_export($amt, true), '$order_amt');
                    Yii::warning( var_export($total, true), '$order_total');
                }
            } */
            return round($total);
        }

    public function getTotalNetAmountData(){
             $total = 0;
            $query1 = OrderItem::find();
            if((Yii::$app->session['item_id'] != '')){
                $query1->andWhere(['item_id' => Yii::$app->session['item_id']]);
            }
            if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
                $query1->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query1->select('sum(price*qty) as price,sum(tax_amount) as tax_amount');
            $query1->andWhere('create_user_id ='.$this->create_user_id);
            $orderitem = $query1->one();
            $order_amt = $orderitem->price + $orderitem->tax_amount;

            $query2 = OrderRefundItem::find();
            if((Yii::$app->session['item_id'] != '')){
                $query2->andWhere(['item_id' => Yii::$app->session['item_id']]);
            }
            if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
                $query2->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query2->select('sum(price*qty) as price,sum(tax_amt) as tax_amt');
            $query2->andWhere('create_user_id ='.$this->create_user_id);
            $orderrefunditem = $query2->one();
            $order_refund_amt = $orderrefunditem->price + $orderrefunditem->tax_amt ;
             $total = $order_amt - $order_refund_amt ;
            Yii::warning( var_export($order_amt, true), '$order_amt');
            Yii::warning( var_export($this->create_user_id, true), '$$this->create_user_id');
            Yii::warning( var_export($order_refund_amt, true), '$$order_refund_amt');


            $query3 = Order::find();

            if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
                $query3->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query3->select('sum(discount_amt) as discount_amt');
            $query3->andWhere('create_user_id ='.$this->create_user_id);
            $discountorder = $query3->one();


         // $total = $total - $discountorder->discount_amt;
         $total = $total;

            //Yii::warning( var_export($orderitems, true), '$orderitems');
            /* if($orderitems){

                foreach ($orderitems as $orderitem){
                    $qty = $orderitem->qty;

                    $refund = 0;
                    $query = OrderRefund::find();
                    $query->andWhere('order_id ='.$orderitem->order_id);
                    if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
                        $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
                    }
                    $orderRefund = $query->one();
                    if($orderRefund){
                        $query3_2 = OrderRefundItem::find();
                        $query3_2->andWhere('order_refund_id ='.$orderRefund->id);
                        $query3_2->andWhere('item_detail_id ='.$orderitem->item_detail_id);
                        if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
                            $query3_2->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
                        }
                        $query3_2->andWhere('item_id ='.$orderitem->item_id);
                        $query3_2->select('sum(total_amt) as total_amt');
                        $orderRefundItem = $query3_2->one();
                        $refund = $orderRefundItem->total_amt;

                    }
                    $amt = ($orderitem->total_amt) - ($refund);
                    $total = $total + $amt;
                    /* Yii::warning( var_export($orderitem->id, true), '$order_item_id');
                    Yii::warning( var_export($amt, true), '$order_amt');
                    Yii::warning( var_export($total, true), '$order_total');
                }
            } */
            return round($total);
        }

    public function getTotalGrossAmount(){

            $total = 0;
            $query1 = OrderItem::find();
            if((Yii::$app->session['item_id'] != '')){
                $query1->andWhere(['item_id' => Yii::$app->session['item_id']]);
            }
            if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
                $query1->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query1->select('sum(price*qty) as price');
            $query1->andWhere('create_user_id ='.$this->create_user_id);
            $orderitem = $query1->one();
            $order_amt = $orderitem->price;

            $query2 = OrderRefundItem::find();
            if((Yii::$app->session['item_id'] != '')){
                $query2->andWhere(['item_id' => Yii::$app->session['item_id']]);
            }
            if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
                $query2->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query2->select('sum(price*qty) as price');
            $query2->andWhere('create_user_id ='.$this->create_user_id);
            $orderrefunditem = $query2->one();
            $order_refund_amt = $orderrefunditem->price;

            $total = $order_amt - $order_refund_amt;


            /* if($orderitems){

                foreach ($orderitems as $orderitem){
                    $qty = $orderitem->qty;
                    $refund = 0;
                     $query = OrderRefund::find();
                    $query->andWhere('order_id ='.$orderitem->order_id);
                    if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
                        $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
                    }
                    $orderRefund = $query->one();
                    if($orderRefund){
                        $query3 = OrderRefundItem::find();
                        $query3->andWhere('order_refund_id ='.$orderRefund->id);
                        $query3->andWhere('item_detail_id ='.$orderitem->item_detail_id);
                        if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
                            $query3->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
                        }
                        $query3->select('sum(total_amt) as total_amt,sum(tax_amt) as tax_amt');
                        $query3->andWhere('item_id ='.$orderitem->item_id);
                        $orderRefundItem = $query3->one();

                        $refund = $orderRefundItem->total_amt - $orderRefundItem->tax_amt;
                    }
                    $amt = (($orderitem->total_amt)-($orderitem->tax_amount))- ($refund);
                    $total = $total + $amt;
                }
            } */
            return $total;
        }

    public function getValTotalNetAmount($start_date,$end_date,$item_id){
            $total = 0;
            $query1 = OrderItem::find();
            if((!empty($item_id))){
                $query1->andWhere(['item_id' => $item_id]);
            }
            if(($start_date != '') && ($end_date != '')){
                $query1->andWhere(['between', 'date(create_time)', $start_date, $end_date]);
            }
            $query1->andWhere('create_user_id ='.$this->create_user_id);
            $orderitems = $query1->all();

            if($orderitems){

                foreach ($orderitems as $orderitem){
                    $qty = $orderitem->qty;
                    $refund = 0;
                    $query = OrderRefund::find();
                    $query->andWhere('order_id ='.$orderitem->order_id);
                    if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
                        $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
                    }
                    $orderRefund = $query->one();
                    if($orderRefund){
                        $query3 = OrderRefundItem::find();
                        $query3->andWhere('order_refund_id ='.$orderRefund->id);
                        $query3->andWhere('item_detail_id ='.$orderitem->item_detail_id);
                        if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
                            $query3->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
                        }
                        $query3->andWhere('item_id ='.$orderitem->item_id);
                        $orderRefundItems = $query3->all();
                        if($orderRefundItems){

                            foreach($orderRefundItems as $orderRefundItem){
                                $refund = $refund + ($orderRefundItem->total_amt);
                            }
                            /* $qty = $qty - $refundqty;
                            if($qty <0){
                                $qty = 0;
                            } */
                        }

                    }
                    $amt = ($orderitem->total_amt)- ($refund);
                    $total = $total + $amt;
                }
            }
            return round($total);
        }

    /**
     * Yii 1's userwisesearch(): a listing of its own, converted as written.
     */
    public function userwisesearch()
    {

		$query = self::find()->alias('t');
		 if(($this->start_date != '' && $this->start_date != null) && ($this->end_date != '' && $this->end_date != null)){
			$query->andWhere(['between', 'bill_date', $this->start_date, $this->end_date]);
		}
		if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
			$query->andWhere(['between', 'bill_date', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
		} 
		// $criteria->select ='t.*, sum(total_amt) as total_amt ';
		$query->groupBy('create_user_id');
		// MySQL 5.7 sorted GROUP BY results implicitly; MySQL 8.0 does not. Order
		// explicitly by the grouped columns to preserve the previous output order.
		$query->orderBy(['create_user_id' => SORT_ASC]);
		Criteria::compare($query, 'id', $this->id);
		Criteria::compare($query, 'bill_no', $this->bill_no);
		Criteria::compare($query, 'qty', $this->qty);
		Criteria::compare($query, 'discount_amt', $this->discount_amt);
		if($this->total_amt != '0.000'){
		Criteria::compare($query, 'total_amt', $this->total_amt);
		}
		Criteria::compare($query, 'paid_amt', $this->paid_amt);
		 Criteria::compare($query, 'status', $this->status);
		Criteria::compare($query, 'type_id', $this->type_id); 
	 	Criteria::compare($query, 'city_id', $this->city_id);
		Criteria::compare($query, 'state_id', $this->state_id);
		Criteria::compare($query, 'country_id', $this->country_id); 
		Criteria::compare($query, 'address', $this->address, true);
		Criteria::compare($query, 'note', $this->note, true);
		Criteria::compare($query, 'create_time', $this->create_time, true);
		Criteria::compare($query, 'update_time', $this->update_time, true);
		Criteria::compare($query, 'customer_id', $this->customer_id);
		Criteria::compare($query, 'updated_by', $this->updated_by); 
		// $orders = Order::model()->findAll($criteria);
		// echo"<pre>"; print_r($orders); die;
	   /*  if($orders){
			$taxable = 0;
			$total = 0;
			foreach($orders as $order){
				$taxable = $taxable + $order->getTotalGrossAmount();
				$total = $total + $order->getTotalNetAmount();
				//Yii::warning( var_export($total, true), '$total');
			}
		
			Yii::$app->session ['gross_total']=round($taxable);
			Yii::$app->session ['gross_total_amt']=round($total);
		} */
		$taxable = 0;
		$total = 0;
		Yii::$app->session ['gross_total']=round($taxable);
		Yii::$app->session ['gross_total_amt']=round($total);
		return new ActiveDataProvider([
		    'query' => $query,
		    'sort' => ['defaultOrder' => []],
		    'pagination' => ['pageSize' => Ui::PAGE_SIZE],
		]);
    }
}
