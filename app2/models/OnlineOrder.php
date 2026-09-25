<?php
namespace app\models;

use app\components\Ui;

use app\components\Criteria;

use yii\data\ActiveDataProvider;

use Yii;

use app\components\LegacyActiveRecord as ActiveRecord;

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
    // Yii 1 hands out column values as strings; the option helpers
    // compare them loosely and answer wrongly for an integer 0.
    use LegacyColumnTypes;

    // Declared on the Yii 1 model and not columns: the forms post
    // to these and the actions assign them.
    public $start_date;
    public $end_date;

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
            [['start_date', 'end_date'], 'safe'],  // form-only, declared on the Yii 1 model
            [['id', 'order_id', 'item_count', 'grand_total', 'first_name', 'last_name', 'street', 'city', 'telephone', 'zip_code', 'country', 'delivery_slot', 'ship_name', 'type_id', 'status', 'create_time', 'update_time', 'create_user_id', 'updated_by'], 'safe', 'on' => 'search'],
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
     * Yii 1 reads this table with \PDO::ATTR_STRINGIFY_FETCHES and does no type
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
     * \PDO::ATTR_STRINGIFY_FETCHES, as the Yii 1 one does, so anything read
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

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'OnlineOrder' : 'OnlineOrders';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'first_name';
    }

    /**
     * GxActiveRecord::__toString(): the representing column's value.
     *
     * Empty when that value is null. Yii 1 falls back to the primary key when
     * representingColumn() itself is empty - which is why 'id' is named above
     * for the models that have no other - and never because the column happens
     * to be null on this row. Falling back on the value put an id in every grid
     * cell where Yii 1 shows nothing.
     */
    public function __toString()
    {
        $value = $this->hasAttribute('first_name') ? $this->first_name : null;

        return $value === null ? '' : (string) $value;
    }

    /**
     * The ordering Yii 1's defaultScope() put on every query for this
     * model. Null means Yii 1 applied none, and neither should this:
     * an order Yii 1 never applied is an order the user never saw.
     */
    public static function defaultOrder()
    {
        return ['id' => SORT_DESC];
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

    /**
     * The order this model's listings use.
     *
     * The grid's own sort when search() names one, otherwise whatever
     * defaultScope() applies. Both the admin grid and the index listing
     * read this, so the two cannot drift apart.
     */
    public static function listingOrder()
    {
        return self::defaultOrder();
    }

    /** Views ask the model whether the current role may reach a route. */
    public function checkPermission($url)
    {
        return \app\components\Access::check($url);
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
     * GxActiveRecord::getRelationLabel(). The generated attributeLabels()
     * above already resolves a relation or foreign key to the related
     * model's label, so this is the attribute label.
     */
    public function getRelationLabel($name, $n = null)
    {
        return $this->getAttributeLabel($name);
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

    public static function getTypeOptions($id = null)
    {
		$list = ["New","Opened"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public static function getOrderStatusOptions($id = null)
    {
		$list = [self::ORDERSTATUS_PENDING=>"Pending",
				self::ORDERSTATUS_PACKED=>"Packed",
				self::ORDERSTATUS_SHIPPED=>"Shipped",
				self::ORDERSTATUS_COMPLETED=>"Completed",
				  self::ORDERSTATUS_CANCELLED=>"Cancelled"
				
				];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public static function getStatusOptions($id = null)
    {
		$list = ["Pending"=>"Pending","Processing"=>"Processing","Cancelled"=>"Cancelled","Completed"=>"Completed"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order No.',
            'item_count' => 'Item Count',
            'grand_total' => 'Order Amount',
            'first_name' => 'Customer Name',
            'last_name' => 'Last Name',
            'street' => 'Address',
            'city' => 'City',
            'telephone' => 'Telephone',
            'zip_code' => 'Zip Code',
            'country' => 'Country',
            'delivery_slot' => 'Delivery Slot',
            'ship_name' => 'Assign To',
            'type_id' => 'Type',
            'status' => 'Status',
            'order_status' => 'Order Status',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'create_user_id' => 'Create User',
            'updated_by' => 'Updated By',
            'delivery_boy' => 'Delivery Boy',
            'delivery_telephone' => 'Delivery Boy No.',
            'payment_method' => 'Payment Method',
            'delivery_method' => 'Delivery Method',
        ];
    }

    /**
     * Yii 1's CActiveRecord fills a new record with the column defaults
     * declared by the table; Yii 2 leaves them null until asked. Without
     * this a create form shows an empty box where Yii 1 shows 0.00, and
     * an insert writes NULL where Yii 1 writes the default.
     */
    public function init()
    {
        parent::init();

        // Not in the search scenario. Yii 1 loaded the defaults and then
        // the admin action called unsetAttributes() to clear them; a
        // search model that keeps them filters the grid by every column
        // that has a default, which showed 4 rows where Yii 1 shows 11.
        if ($this->isNewRecord && $this->scenario !== 'search') {
            $this->loadDefaultValues();
        }
    }

    /**
     * Port of the base model's beforeValidate(): stamps the row with who
     * created or changed it and when. Yii 1 ran this on every save, so a
     * row written by the port has to carry the same stamps.
     */
    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }
        if ($this->isNewRecord) {
            if ($this->hasAttribute('create_time') && !isset($this->create_time)) {
                $this->create_time = date('Y-m-d H:i:s');
            }
            if ($this->hasAttribute('create_user_id') && !isset($this->create_user_id)) {
                $this->create_user_id = Yii::$app->user->id;
            }
        } elseif ($this->hasAttribute('updated_by') && !isset($this->updated_by)) {
            $this->updated_by = Yii::$app->user->id;
        }

        return true;
    }

    /**
     * Backs the admin grid.
     *
     * The comparison rules are Yii 1's, and there is deliberately no
     * validate() call: the generated search() compares whatever is set and
     * never validates, and a required rule with no `on` clause would
     * otherwise reject every filtered request and return the full list.
     */
    public function search($params = [])
    {
        $this->load($params, $this->formName());

		$query = self::find();
        $query->orderBy(['id' => SORT_DESC]);

		Criteria::compare($query, 'id', $this->id);
		Criteria::compare($query, 'order_id', $this->order_id);
		Criteria::compare($query, 'order_date', $this->order_date);
		if(($this->start_date != '' && $this->start_date != null) && ($this->end_date != '' && $this->end_date != null)){
			$query->andWhere(['between', 'date(order_date)', $this->start_date, $this->end_date]);
		}
		Criteria::compare($query, 'delivery_boy', $this->delivery_boy);
		Criteria::compare($query, 'delivery_telephone', $this->delivery_telephone);
		Criteria::compare($query, 'payment_method', $this->payment_method);
		Criteria::compare($query, 'delivery_method', $this->delivery_method);
		Criteria::compare($query, 'item_count', $this->item_count);
		Criteria::compare($query, 'grand_total', $this->grand_total);
		Criteria::compare($query, 'first_name', $this->first_name, true);
		Criteria::compare($query, 'last_name', $this->last_name, true);
		Criteria::compare($query, 'street', $this->street, true);
		Criteria::compare($query, 'city', $this->city, true);
		Criteria::compare($query, 'telephone', $this->telephone, true);
		Criteria::compare($query, 'zip_code', $this->zip_code, true);
		Criteria::compare($query, 'country', $this->country, true);
		Criteria::compare($query, 'delivery_slot', $this->delivery_slot, true);
		Criteria::compare($query, 'ship_name', $this->ship_name, true);
		Criteria::compare($query, 'type_id', $this->type_id);
		Criteria::compare($query, 'status', $this->status, true);
		Criteria::compare($query, 'create_time', $this->create_time, true);
		Criteria::compare($query, 'update_time', $this->update_time, true);
		Criteria::compare($query, 'create_user_id', $this->create_user_id);
		Criteria::compare($query, 'updated_by', $this->updated_by);

		return new ActiveDataProvider([
		    'query' => $query,
		    'sort' => ['defaultOrder' => []],
		    'pagination' => ['pageSize' => 50],
		]);
    }

    /**
     * GxActiveRecord::isAllowed(): whether this row belongs to the
     * operator who is signed in.
     *
     * False for a model with no create_user_id, which is what Yii 1
     * answers. bill/delete asks it before deleting, and died on a
     * method the port did not have.
     */
    public function isAllowed()
    {
        if (!$this->hasAttribute('create_user_id')) {
            return false;
        }

        return $this->create_user_id == Yii::$app->user->id;
    }
}
