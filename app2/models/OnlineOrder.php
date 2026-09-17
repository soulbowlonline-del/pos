<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * Ported from protected/models/OnlineOrder.php (Yii 1).
 *
 * toApiArray() is the port of toArray(). Its $withItems flag is Yii 1's $val:
 * off, the payload is the order header; on, it also carries the line items,
 * taken from the POS order if one has been raised against this online order
 * and from the online order's own lines if not.
 */
class OnlineOrder extends ActiveRecord
{
    public const STATUS_PENDING = 'Pending';
    public const STATUS_PROCESSING = 'Processing';
    public const STATUS_CANCELLED = 'Cancelled';
    public const STATUS_COMPLETED = 'Completed';

    public const ORDERSTATUS_PENDING = 0;
    public const ORDERSTATUS_PACKED = 1;
    public const ORDERSTATUS_SHIPPED = 2;
    public const ORDERSTATUS_COMPLETED = 3;
    public const ORDERSTATUS_CANCELLED = 4;

    public const ORDER_NOT_SHIPPED = 0;
    public const ORDER_SHIPPED = 1;

    public const TYPE_NEW = 0;
    public const TYPE_OPEN = 1;

    public static function tableName()
    {
        return '{{%online_order}}';
    }

    /**
     * BaseOnlineOrder's rules, which matter because the Yii 1 write paths save
     * with validation on. The last one is the reason: a `default` rule with
     * setOnEmpty rewrites every listed attribute to NULL when it is empty, so
     * saving an order to change its status also nulls any blank name, street
     * or phone on the row. That is visible both in the response and in the
     * table, so the port has to do it too.
     */
    public function rules()
    {
        return [
            [['order_id'], 'required'],
            [['order_id', 'item_count', 'type_id', 'create_user_id', 'updated_by'], 'integer'],
            [['grand_total'], 'number'],
            [['first_name', 'last_name', 'street', 'city', 'telephone', 'zip_code',
              'country', 'delivery_slot', 'ship_name', 'status'], 'string', 'max' => 255],
            [['create_time', 'update_time', 'order_date', 'delivery_boy', 'delivery_telephone',
              'payment_method', 'delivery_method', 'mobile', 'order_status',
              'delivery_boy_id', 'picker_id', 'is_shipped'], 'safe'],
            [['item_count', 'grand_total', 'first_name', 'last_name', 'street', 'city',
              'telephone', 'zip_code', 'country', 'delivery_slot', 'ship_name', 'type_id',
              'status', 'create_time', 'update_time', 'create_user_id', 'updated_by'],
             'default', 'value' => null],
        ];
    }

    /**
     * Yii 1 reads this table with PDO::ATTR_STRINGIFY_FETCHES and does no type
     * casting of its own, so every column arrives as a string. Yii 2 sets the
     * same PDO attribute but then casts int and float columns back to PHP
     * types in ActiveRecord::populateRecord(), which shows up directly in the
     * JSON.
     *
     * Casting the payload instead does not work, because the write paths
     * matter too: cancelOrder assigns the *int* 4 to order_status and Yii 1
     * echoes an int, while a blanket (string) cast would emit "4". Skipping
     * Yii 2's typecast reproduces both at once - a column read from the
     * database stays a string, a value just assigned keeps the type it was
     * assigned - and means the payload needs no casts at all.
     */
    public static function populateRecord($record, $row)
    {
        \yii\db\BaseActiveRecord::populateRecord($record, $row);
    }

    public function getPicker()
    {
        return $this->hasOne(User::class, ['id' => 'picker_id']);
    }

    public function getDeliveryBoy()
    {
        return $this->hasOne(User::class, ['id' => 'delivery_boy_id']);
    }

    public function getOnlineOrderItems()
    {
        return $this->hasMany(OnlineOrderItem::class, ['order_id' => 'id']);
    }

    /** Yii 1's getCustomerName(): no first name means no name at all. */
    public function getCustomerName()
    {
        if ($this->first_name != '' && $this->last_name == '') {
            return $this->first_name;
        }
        if ($this->first_name != '' && $this->last_name != '') {
            return $this->first_name . ' ' . $this->last_name;
        }
        return '';
    }

    /**
     * The Yii 1 lookups here all went through CDbCriteria::compare(), which
     * drops the condition entirely when the value is NULL or '' - so a row
     * with no delivery method does not fail to match a payment mode, it
     * matches the *first* one. A plain where() instead generates
     * "title IS NULL" and finds nothing, which is what the first draft of this
     * port did: it disagreed with Yii 1 on 10 of 177 live rows.
     *
     * Ordered by id so "first" is a defined row rather than whatever MySQL
     * happened to return; the Yii 1 side is ordered to match.
     */
    private static function firstBy($query, $column, $value)
    {
        if ($value !== null && $value !== '') {
            $query->andWhere([$column => $value]);
        }
        return $query->orderBy(['id' => SORT_ASC])->one();
    }

    /**
     * No casts here on purpose. The connection sets
     * PDO::ATTR_STRINGIFY_FETCHES, as the Yii 1 one does, so anything read
     * from the database is already a string - and a write path that has just
     * assigned an int (cancelOrder setting order_status, say) should report
     * that int, which is what Yii 1 does. Casting broke exactly those cases.
     */
    public function toApiArray($withItems = false)
    {
        $paymentMode = self::firstBy(PaymentMode::find(), 'title', $this->payment_method);
        $deliveryMode = self::firstBy(PaymentMode::find(), 'title', $this->delivery_method);

        // Yii 1 orders this by id asc and takes the first match on the phone
        // number, so a duplicated number resolves to the oldest customer - and
        // a NULL number resolves to customer 1, for the reason in firstBy().
        $customer = self::firstBy(Customer::find(), 'contact_no', $this->mobile);

        $out = [];
        $out['id'] = $this->id;
        // the literal string '1' when there is no matching customer, as in Yii 1
        // these three come off other models, which still typecast, and are
        // always read from the database - never assigned - so casting them
        // is safe where casting this model's own columns was not
        $out['customer_id'] = $customer ? (string)$customer->id : '1';
        $out['order_no'] = $this->order_id === null ? '' : $this->order_id;
        $out['order_date'] = $this->order_date === null ? '' : $this->order_date;
        $out['item_count'] = $this->item_count === null ? '' : $this->item_count;
        $out['grand_total'] = $this->grand_total === null ? '' : $this->grand_total;
        $out['customer_name'] = $this->getCustomerName();
        $out['customer_contact_no'] = $this->mobile === null ? '' : $this->mobile;
        $out['last_name'] = $this->last_name === null ? '' : $this->last_name;
        $out['address'] = $this->street === null ? '' : $this->street;
        $out['city'] = $this->city === null ? '' : $this->city;
        $out['country'] = $this->country === null ? '' : $this->country;
        $out['mobile'] = $this->mobile === null ? '' : $this->mobile;
        $out['payment_method'] = $this->payment_method === null ? '' : $this->payment_method;
        $out['delivery_method'] = $this->delivery_method === null ? '' : $this->delivery_method;
        $out['payment_method_id'] = $paymentMode ? (string)$paymentMode->id : '';
        $out['delivery_method_id'] = $deliveryMode ? (string)$deliveryMode->id : '';
        $out['order_status'] = $this->order_status;
        $out['is_shipped'] = $this->is_shipped;
        $out['telephone'] = $this->telephone === null ? '' : $this->telephone;
        $out['order_from'] = $this->order_from === null ? '' : $this->order_from;
        $out['comment'] = $this->comment === null ? '' : $this->comment;
        $out['zip_code'] = $this->zip_code === null ? '' : $this->zip_code;
        $out['delivery_slot'] = $this->delivery_slot === null ? '' : $this->delivery_slot;
        $out['ship_name'] = $this->ship_name === null ? '' : $this->ship_name;
        $out['picker_id'] = $this->picker_id === null ? '' : $this->picker_id;
        $out['picker_name'] = $this->picker ? $this->picker->full_name : '';
        $out['delivery_boy_name'] = $this->deliveryBoy ? $this->deliveryBoy->full_name : '';
        $out['delivery_boy_id'] = $this->delivery_boy_id === null ? '' : $this->delivery_boy_id;

        $posOrder = Order::find()->where(['online_order_id' => $this->id])->one();
        $out['discount_amt'] = $posOrder ? $posOrder->discount_amt : '0.00';

        if ($withItems) {
            // Yii 1 never initialises $json_list on this path, so an online
            // order with no matchable lines emits null for order_items rather
            // than an empty array - and warns on PHP 8. Reproduced: see
            // docs/php8-fragility-sweep.md.
            $list = null;

            if ($posOrder === null) {
                $lines = OnlineOrderItem::find()
                    ->where(['order_id' => $this->id])
                    ->orderBy(['id' => SORT_ASC])
                    ->all();

                foreach ($lines as $onlineItem) {
                    $item = self::firstBy(Item::find(), 'item_code', $onlineItem->product_code);
                    if (!$item) {
                        continue;
                    }
                    $itemDetail = self::firstBy(
                        ItemDetail::find()->andWhere(['item_id' => $item->id]),
                        'bar_code',
                        $onlineItem->barcode
                    );
                    if ($itemDetail === null) {
                        // getItemBarcodes() returns '' when the item has no active
                        // detail row, and Yii 1's compare() drops an empty
                        // condition - so this falls back to the item's first
                        // detail rather than matching nothing. firstBy() keeps
                        // that behaviour; a plain where() dropped a line here.
                        $itemDetail = self::firstBy(
                            ItemDetail::find()->andWhere(['item_id' => $item->id]),
                            'bar_code',
                            $item->getItemBarcodes()
                        );
                    }
                    if ($itemDetail) {
                        $list[] = $itemDetail->toOnlineOrderApiArray($onlineItem);
                    }
                }
            } else {
                foreach ($posOrder->orderItems as $orderItem) {
                    $list[] = $orderItem->toApiArray1();
                }
            }

            $out['order_items'] = $list;
        }

        return $out;
    }
}
