<?php
namespace app\models;

use app\components\Ui;

use app\components\Criteria;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/**
 * Ported from protected/models/B2bPurchaseBillDetail.php (Yii 1), which is
 * 1,625 lines. Only what tally/b2bsales reads is here; the rest belongs with
 * whatever ports the B2B screens.
 */
class B2bPurchaseBillDetail extends ActiveRecord
{
    // Yii 1 hands out column values as strings; the option helpers
    // compare them loosely and answer wrongly for an integer 0.
    use LegacyColumnTypes;

    public const STATUS_APPROVED = 2;
    public const STATUS_RECEIVED = 1;
    public const STATUS_PENDING = 0;
    // Declared on the Yii 1 model and not columns: the forms post
    // to these and the actions assign them.
    public $expiry_date;
    public $packing_date;
    public $columns;
    public $start_date;
    public $tax_amount;
    public $end_date;
    public $min_amt;
    public $max_amt;
    public $tally_start_date;
    public $tally_end_date;
    public $bill_date;
    public $bill_no;
    public $vendor_id;
    public $bar_code;
    public $bill_amount;
    public $item_val_id;
    public $vendor;

    public static function tableName()
    {
        return '{{%b2bpurchase_bill_detail}}';
    }

    public function getPurchaseBill()
    {
        return $this->hasOne(B2bPurchaseBill::class, ['id' => 'purchase_bill_id']);
    }

    public function getItemDetail()
    {
        return $this->hasOne(ItemDetail::class, ['id' => 'item_detail_id']);
    }

    public function getCreateUser()
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
    }

    /**
     * False when the vendor and the outlet are in different states, which is
     * what decides IGST against CGST+SGST.
     *
     * $vendor->state_id is read without checking $vendor, so a bill pointing at
     * a missing vendor is a fatal - as in Yii 1.
     */
    public function getGstTrue($billId)
    {
        $gst = true;
        if ($billId) {
            $bill = B2bPurchaseBill::find()->where(['id' => $billId])->orderBy(['id' => SORT_ASC])->one();
            if ($bill) {
                $outlet = Outlet::findOne($bill->outlet_id);
                if ($outlet) {
                    $vendor = Vendor::findOne($bill->vendor_id);
                    if ($vendor->state_id != $outlet->state_id) {
                        $gst = false;
                    }
                }
            }
        }
        return $gst;
    }

    /**
     * A tax column, zeroed when it does not apply to this bill.
     *
     * CESS is the exception: it comes back whichever way the bill falls. Note
     * the zero is the string '0.00', not a number - the caller puts it straight
     * into the response.
     */
    public function getTaxPercentage($col)
    {
        $checkGst = !($col == 'igst_per' || $col == 'igst_amt');

        if ($this->getGstTrue($this->purchase_bill_id) == $checkGst) {
            return $this->$col;
        }
        if ($col == 'cess_per' || $col == 'cess_amt') {
            return $this->$col;
        }
        return '0.00';
    }

    /**
     * CGST plus SGST. The Yii 1 version also computes cess and igst and then
     * does not use them; left out rather than reproduced, since they cost two
     * queries each and cannot affect the answer.
     */
    public function getTotalGstPer()
    {
        return $this->getTaxPercentage('cgst_per') + $this->getTaxPercentage('sgst_per');
    }

    /**
     * price * approved_qty for this row.
     *
     * Yii 1 gets there by re-selecting the row it already has with a SUM over
     * a single id, which is why the value comes back as a string under the
     * alias `price`. Reproduced through the same query so the formatting
     * matches; a plain multiplication here would return a float.
     */
    public function getsaleTaxableAmount()
    {
        $row = B2bPurchaseBillDetail::find()
            ->select('sum(price*approved_qty) as price')
            ->where('id = :id', [':id' => $this->id])
            ->asArray()
            ->one();

        return $row === null ? null : $row['price'];
    }

    /** The bill's start date. No null check on the bill, as in Yii 1. */
    public function getOrderBillDate()
    {
        $bill = B2bPurchaseBill::findOne($this->purchase_bill_id);
        return $bill->start_date;
    }

    /**
     * The printed bill number: B2B<yy>-<yy>/<prefix>-<grn reference>.
     *
     * The prefix logic is inverted - it uses the outlet's prefix only when that
     * prefix is empty, and the literal 'B' otherwise - so every bill reads
     * B2B.../B-... whatever the outlet is called. Same inversion as
     * Order::getOrderBillNo(), and reproduced for the same reason.
     */
    public function getOrderBillNo()
    {
        $billPrefix = 'B';
        $bill = B2bPurchaseBill::findOne($this->purchase_bill_id);

        $month = date('m', strtotime($bill->start_date));
        if ($month > 3) {
            $year = substr(date('Y', strtotime($bill->start_date)), -2);
            $yearLast = substr((string)($year + 1), -2);
        } else {
            $year = date('Y', strtotime($bill->start_date)) - 1;
            $yearLast = date('Y', strtotime($bill->start_date));
        }

        $outlet = Outlet::findOne($this->outlet_id);
        if ($outlet) {
            $billPrefix = $outlet->bill_prefix == '' ? $outlet->bill_prefix : 'B';
        }

        return 'B2B' . $year . '-' . $yearLast . '/' . $billPrefix . '-' . $bill->grn_refrence_no;
    }

    /** The vendor's name. Neither lookup is checked, as in Yii 1. */
    public function getVendorName()
    {
        $bill = B2bPurchaseBill::findOne($this->purchase_bill_id);
        $vendor = Vendor::findOne($bill->vendor_id);
        return $vendor->name;
    }

    /** The name of the user who created the row. Not checked, as in Yii 1. */
    public function createduser()
    {
        $user = User::findOne($this->create_user_id);
        return $user->full_name;
    }

    /** The vendor's state, by name. */
    public function getStateName($id = null)
    {
        if (empty($id)) {
            $id = $this->purchaseBill->vendor->state_id;
        }
        $state = State::findOne($id);
        return $state ? $state->title : '';
    }

    /** The item's title, reached through the item detail. */
    public function getItemName()
    {
        $title = '';
        $itemDetail = ItemDetail::findOne($this->item_detail_id);
        if ($itemDetail) {
            $item = Item::findOne($itemDetail->item_id);
            if ($item) {
                $title = $item->title;
            }
        }
        return $title;
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'B2B PurchaseBillDetail' : 'B2B PurchaseBillDetails';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'remarks';
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
        $value = $this->hasAttribute('remarks') ? $this->remarks : null;

        return $value === null ? '' : (string) $value;
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

    /**
     * The order this model's listings use.
     *
     * The grid's own sort when search() names one, otherwise whatever
     * defaultScope() applies. Both the admin grid and the index listing
     * read this, so the two cannot drift apart.
     */
    public static function listingOrder()
    {
        return ['t.order' => SORT_ASC];
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

    public static function getFreeItemOptions($id = null)
    {
		$list = [
				"No",
				"Yes",
				
		];
		if ($id === null || $id === '')
			return $list;
			if (is_numeric ( $id ))
				return $list [$id];
				return $id;
    }

    public static function getStatusOptions($id = null)
    {
		$list = [
				"Pending",
				"Received",
				"Approved",
		];
		if ($id === null || $id === '')
			return $list;
		if (is_numeric ( $id ))
			return $list [$id];
		return $id;
    }

    public static function getTypeOptions($id = null)
    {
		$list = [
				"TYPE1",
				"TYPE2",
				"TYPE3" 
		];
		if ($id === null || $id === '')
			return $list;
		if (is_numeric ( $id ))
			return $list [$id];
		return $id;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'req_qty' => 'Max Qty',
            'bal_qty' => 'Bal Qty',
            'approved_qty' => 'Approved Qty',
            'mrp' => 'Mrp',
            'price' => 'Price',
            'discount' => 'Discount',
            'discount_amt' => 'Discount Amt',
            'discount1' => 'Other Discount',
            'discount_amt1' => 'Other Discount Amt',
            'tax_id' => 'Tax',
            'other_charge' => 'Other Charge',
            'amount' => 'Amount',
            'sale_rate' => 'Sale Rate',
            'tally_start_date' => 'Start Date',
            'tally_end_date' => 'End Date',
            'end_date' => 'End Date',
            'status' => 'Status',
            'type_id' => 'Type',
            'cgst_per' => 'CGST(%age)',
            'sgst_per' => 'SGST(%age)',
            'cess_per' => 'CESS(%age)',
            'cgst_amt' => 'CGST Amount',
            'sgst_amt' => 'SGST Amount',
            'cess_amt' => 'CESS Amount',
            'min_amt' => 'Minimum Total Amount',
            'max_amt' => 'Maximum Total Amount',
            'charge_amount' => 'Charge Amount',
            'extra_charges' => 'Extra Charges',
            'remarks' => 'Remarks',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'create_user_id' => 'Create User Id',
            'updated_by' => 'Updated By',
            'item_detail_id' => 'Item Detail Id',
            'purchase_bill_id' => 'Purchase Bill Id',
            'outlet_id' => 'Outlet Id',
            'vendor_id' => 'Vendor',
            'createUser' => 'User',
            'itemDetail' => 'ItemDetail',
            'item_id' => 'Item Name',
            'outlet' => 'Outlet',
            'purchaseBill' => 'B2b PurchaseBill',
            'updatedBy' => 'User',
        ];
    }

    public function getPBillVendorOptions()
        {
            $list = [];
            $query = PurchaseBill::find();
            $query->orderBy(['id' => SORT_DESC]);
            $query->andWhere('status !=' . PurchaseBill::STATUS_APPROVED);
            $mrss = $query->all();
            Yii::warning(var_export($mrss, true), '$mrss');
            if ($mrss) {
                foreach ($mrss as $mrs) {
                    $create_time = date('d-m-Y', strtotime($mrs->create_time));
                    $vendor = Vendor::findOne($mrs->vendor_id);
                    if ($vendor) {
                        // $list[$vendor->id] = $vendor->name.'('.$create_time.')';
                        $list[$vendor->id] = $vendor->name;
                    }
                }
            }
                $vendor = Vendor::find()->orderBy(['id' => SORT_DESC])->all();

                if ($vendor) {
                    foreach ($vendor as $_vendor) {
                        // $list[$vendor->id] = $vendor->name.'('.$create_time.')';
                        $list[$_vendor->id] = $_vendor->name;
                    }
                }

            asort($list);
            return $list;
        }

    public function getAllTaxOptions($id = null)
        {
            $list = [];
            $taxes = Tax::find()->where([
                'status' => Tax::STATUS_ACTIVE
            ])->all();
            if ($taxes) {
                foreach ($taxes as $tax) {
                    $list[$tax->id] = $tax->title;
                }
            }
            return $list;
            if ($id == null)
                return $list;
            if (is_numeric($id))
                return $list[$id];
            return $id;
        }

    public function getPOBillOptions($id = null)
        {
            $list = [];
            $user = Yii::$app->user->model;
            // $user = User::findOne($id);
            if ($user) {
                $role_id = $user->role_id;
                $role = UserRole::find()->where([
                    'title' => 'Vendor'
                ])->orderBy(['id' => SORT_DESC])->one();

                if ($id != null) {
                    if ($role_id == $role->id) {
                        $query = PurchaseBill::find();
            $query->orderBy(['id' => SORT_DESC]);
                        $query->andWhere('vendor_id =' . $id);
                        $query->andWhere('status !=' . PurchaseBill::STATUS_APPROVED);
                        $polist = $query->all();
                    } else {
                        $query_2 = PurchaseBill::find();
            $query_2->orderBy(['id' => SORT_DESC]);

                        $query_2->andWhere('status !=' . PurchaseBill::STATUS_APPROVED);
                        $polist = $query_2->all();
                    }
                    if ($polist) {
                        foreach ($polist as $po) {
                            $list[$po->id] = $po->id;
                        }
                    }
                }
            }
            return $list;
        }

    public function getAllPOBillOptions($id = null , $v =null)
        {
            $list = [];
            $user = Yii::$app->user->model;
            if ($user) {
                $role_id = $user->role_id;

                $role = UserRole::find()->where([
                    'title' => 'Vendor'
                ])->orderBy(['id' => SORT_DESC])->one();

                if ($id != null) {
                    if ($role_id == $role->id) {
                        $query = B2bPurchaseBill::find();
                        $query->andWhere('vendor_id =' . $id);
                        $query->andWhere('status !=' . B2bPurchaseBill::STATUS_APPROVED);
                        $polist = $query->all();
                    } else {
                        $query_2 = B2bPurchaseBill::find();
                    if(!empty($v)){
                         $query_2->andWhere('vendor_id =' . $v);
                    }
                        $query_2->andWhere('status !=' . B2bPurchaseBill::STATUS_APPROVED);
                        $polist = $query_2->all();
                    }
                    if ($polist) {
                        foreach ($polist as $po) {
                            $list[] = $po->id;
                        }
                    }
                }
            }
            return $list;
        }

    public function getVendorTAXNO()
        {
            $tax_no = '';
            if ($this->purchaseBill) {
                if ($this->purchaseBill->vendor) {
                    $tax_no = $this->purchaseBill->vendor->tax_no;
                }
            }
            return $tax_no;
        }

    public function getUnitName()
        {

             $details = Item::findOne([
                'id' => $this->item_id
                // 'tax_id' => $this->tax_id
            ]);
            $unit=$details->unit;
            if($unit=='0'){
                $unit="PCS-PIECES";
            }elseif($unit=='1'){
            $unit="Box";
            }
            elseif($unit=='2'){
            $unit="Case";
                }
            elseif($unit=='3'){
            $unit="KGS-KILOGRAMS";
            }
            elseif($unit=='4'){
            $unit="ML";
                }
            elseif($unit=='5'){
                $unit="NOS";
                }
            elseif($unit=='6'){
            $unit="PCS";
            }
            elseif($unit=='7'){
            $unit="PETI";
            }
            elseif($unit=='8'){
            $unit="TIN";
            }
            return $unit;
        }

    public function getSalesColumns($selectcolumns = [])
        {
            if (! empty($selectcolumns)) {
                $selected = $selectcolumns;
            } else {

                $selected = [
                    // 'date',
                    'pin_code',
                    'bill_no',
                    'state',
                    'state_code',
                    'place',
                   'tax_no',
                    'vendor',
                    'item_name',
                    'hsn_code',
                    'qty',
                    'unit',
                     'basic_value',
                     'taxable',
                    'gst_per',
                    'cgst_per',
                    'sgst_per',
                    'igst_per',
                    'cess_per',
                    'cgst_amt',
                    'sgst_amt',
                    'igst_amt',
                    'cess_amt',
                    'invoice_amt'
                ];
            }

            if ($selected) {
                foreach ($selected as $select) {
                    if ($select == 'date') {
                        $columns[] = [
                            'label' => 'Date',
                            'value' => function ($data) {
                                return isset($data->purchaseBill) ? $data->purchaseBill->end_date : "";
                            }
                        ];

                        } else if ($select == 'state_code') {
                        $columns[] = [
                            'label' => 'State code',
                            'value' => function ($data) {
                                return isset($data->purchaseBill) ?  substr($data->purchaseBill->vendor->tax_no, 0, 2) : "";
                            }
                        ];
                        } else if ($select == 'place') {
                        $columns[] = [
                            'label' => 'Place',
                            'value' => function ($data) {
                                return isset($data->purchaseBill) ? $data->purchaseBill->vendor->primary_address : "";
                            }
                        ];
                    } else if ($select == 'vendor') {
                        $columns[] = [
                            'label' => 'Vendor',
                            'value' => function ($data) {
                                return isset($data->purchaseBill) ? $data->purchaseBill->vendor : "";
                            }
                        ];
                        } else if ($select == 'item_name') {
                        $columns[] = [
                            'label' => 'Item Name',
                            'value' => function ($data) {
      return isset($data->purchaseBill) ? $data->getItemName() : "";

                            }
                        ];
                        } else if ($select == 'unit') {
                        $columns[] = [
                            'label' => 'Unit',
                            'value' => function ($data) {
      return $data->getUnitName();

                            }
                        ];
                    } else if ($select == 'hsn_code') {
                        $columns[] = [
                            'label' => 'HSN Code',
                            'value' => function ($data) {
                                return isset($data->hsn_code) ? $data->hsn_code : "";
                            }
                        ];
                        } else if ($select == 'state') {
                        $columns[] = [
                            'label' => 'State',
                            'value' => function ($data) {

                              return isset($data->purchaseBill) ? $data->getStateName($data->purchaseBill->vendor->state_id) : "";

                            }
                        ];
                        } else if ($select == 'qty') {
                        $columns[] = [
                            'label' => 'Qty',
                            'value' => function ($data) {
                                return isset($data->purchaseBill) ? $data->approved_qty : "";
                            }
                        ];
                    } else if ($select == 'bill_no') {
                        $columns[] = [
                            'label' => 'Bill No',
                            'value' => function ($data) {
                                return $data->getOrderBillNo();
                            }
                        ];
                    } else if ($select == 'tax_no') {
                        $columns[] = [
                            'label' => 'GST NO',
                            'value' => function ($data) {
                                return $data->getVendorTAXNO();
                            }
                        ];
                    } else if ($select == 'gst_per') {
                        $columns[] = [
                            'label' => 'TAX%',
                            'value' => function ($data) {
                                return $data->getTotalGstPer();
                            }
                        ];
                    } else if ($select == 'cgst_per') {
                        $columns[] = [
                            'label' => 'CGST%',
                            'value' => function ($data) {
                                return $data->getTaxPercentage("cgst_per");
                            }
                        ];
                    } else if ($select == 'sgst_per') {
                        $columns[] = [
                            'label' => 'SGST%',
                            'value' => function ($data) {
                                return $data->getTaxPercentage("sgst_per");
                            }
                        ];
                    } else if ($select == 'igst_per') {
                        $columns[] = [
                            'label' => 'IGST%',
                            'value' => function ($data) {
                                return $data->getTaxPercentage("igst_per");
                            }
                        ];
                    } else if ($select == 'cess_per') {
                        $columns[] = [
                            'label' => 'CESS%',
                            'value' => function ($data) {
                                return $data->getTaxPercentage("cess_per");
                            }
                        ];
                    } else if ($select == 'taxable') {
                        $columns[] = [
                            'label' => 'Taxable Amount',
                            'value' => function ($data) {
                                return $data->getsaleTaxableAmount() - ($data->discount_amt1 + $data->discount_amt);
                            }
                        ];

                     } else if ($select == 'invoice_amt') {
                        $columns[] = [
                            'label' => 'Invoice Amount',
                            'value' => function ($data) {
                                return $data->amount;
                            }
                        ];
                    } else if ($select == 'basic_value') {
                        $columns[] = [
                            'label' => 'Rate',
                            'value' => function ($data) {
                                return $data->mrp;
                            }
                        ];
                    } else if ($select == 'discount') {
                        $columns[] = [
                            'label' => 'Discount',
                            'value' => function ($data) {
                                return $data->getMainDiscount();
                            }
                        ];
                    } else if ($select == 'gst_amt') {
                        $columns[] = [
                            'label' => 'GST',
                            'value' => function ($data) {
                                return $data->getTotalGstAmt();
                            }
                        ];
                    } else if ($select == 'cgst_amt') {
                        $columns[] = [
                            'label' => 'CGST',
                            'value' => function ($data) {
                                return $data->cgst_amt;
                            }
                        ];
                    } else if ($select == 'sgst_amt') {
                        $columns[] = [
                            'label' => 'SGST',
                            'value' => function ($data) {
                                return $data->sgst_amt;
                            }
                        ];
                    } else if ($select == 'igst_amt') {
                        $columns[] = [
                            'label' => 'IGST',
                            'value' => function ($data) {
                                return $data->igst_amt;
                            }
                        ];
                    } else if ($select == 'cess_amt') {
                        $columns[] = [
                            'label' => 'CESS Amount',
                            'value' => function ($data) {
                                return $data->cess_amt;
                            }
                        ];
                    } else if ($select == 'grn_no') {
                        $columns[] = [
                            'label' => 'GRN NUMBER',
                            'value' => function ($data) {
                                return isset($data->purchaseBill) ? 'Gr-' . $data->purchaseBill->grn_refrence_no : "";
                                // return 'Gr-'.$data->purchase_bill_id;
                            }
                        ];
                    } else if ($select == 'scheme') {
                        $columns[] = [
                            'label' => 'SCHEME AND DISCOUNT',
                            'value' => function ($data) {
                                return $data->getSchemeDiscount();
                            }
                        ];
                    } else {
                        $columns[] = $select;
                    }
                }
            }

            return $columns;
        }

    public function getB2bDeptColumns($selectcolumns = [])
        {
            if (! empty($selectcolumns)) {
                $selected = $selectcolumns;
            } else {

                $selected = [


                    'bill_no',
                    'start_date',
                    'customer',
                    'taxable',
                    'gst_per',
                    'cgst_per',
                    'sgst_per',
                    'igst_per',
                    'cess_per',
                    'cgst_amt',
                    'sgst_amt',
                    'cess_amt',
                    'igst_amt',
                    'round_amt',

                ];
            }

            if ($selected) {
                foreach ($selected as $select) {
                    if ($select == 'start_date') {
                        $columns[] = [
                            'label' => 'Date',
                            'value' => function ($data) {
                        // echo"<pre>"; print_r($data->purchaseBil); die;
                                return $data->getOrderBillDate();
                            }
                        ];

                        }
                        else if ($select == 'taxable') {
                        $columns[] = [
                            'label' => 'Taxable',
                            'value' => function ($data) {
                                return $data->price-($data->discount_amt1 + $data->discount_amt) ;
                            }
                        ];
                        } else if ($select == 'customer') {
                        $columns[] = [
                            'label' => 'Customer',
                            'value' => function ($data) {
                                return isset($data->purchaseBill) ? $data->purchaseBill->vendor->name : "";
                            }
                        ];
                        }
                        else if ($select == 'place') {
                        $columns[] = [
                            'label' => 'Place',
                            'value' => function ($data) {
                                return isset($data->purchaseBill) ? $data->purchaseBill->vendor->primary_address : "";
                            }
                        ];
                    } else if ($select == 'vendor') {
                        $columns[] = [
                            'label' => 'Vendor',
                            'value' => function ($data) {
                                return isset($data->purchaseBill) ? $data->purchaseBill->vendor : "";
                            }
                        ];
                        }
                     else if ($select == 'hsn_code') {
                        $columns[] = [
                            'label' => 'HSN Code',
                            'value' => function ($data) {
                                return isset($data->hsn_code) ? $data->hsn_code : "";
                            }
                        ];
                        } else if ($select == 'state') {
                        $columns[] = [
                            'label' => 'State',
                            'value' => function ($data) {

                              return isset($data->purchaseBill) ? $data->getStateName($data->purchaseBill->vendor->state_id) : "";

                            }
                        ];
                        } else if ($select == 'qty') {
                        $columns[] = [
                            'label' => 'Qty',
                            'value' => function ($data) {
                                return isset($data->purchaseBill) ? $data->approved_qty : "";
                            }
                        ];
                    } else if ($select == 'bill_no') {
                        $columns[] = [
                            'label' => 'Bill No',
                            'value' => function ($data) {
                                return $data->getOrderBillNo();
                            }
                        ];
                    } else if ($select == 'tax_no') {
                        $columns[] = [
                            'label' => 'GST NO',
                            'value' => function ($data) {
                                return $data->getVendorTAXNO();
                            }
                        ];
                    } else if ($select == 'gst_per') {
                        $columns[] = [
                            'label' => 'TAX%',
                            'value' => function ($data) {
                                return $data->getTaxTitle();
                            }
                        ];
                    } else if ($select == 'cgst_per') {
                        $columns[] = [
                            'label' => 'CGST%',
                            'value' => function ($data) {
                                return $data->cgst_per;
                            }
                        ];
                    } else if ($select == 'sgst_per') {
                        $columns[] = [
                            'label' => 'SGST%',
                            'value' => function ($data) {
                                return $data->sgst_per;
                            }
                        ];
                    } else if ($select == 'igst_per') {
                        $columns[] = [
                            'label' => 'IGST%',
                            'value' => function ($data) {
                                return $data->igst_per;
                            }
                        ];
                    } else if ($select == 'cess_per') {
                        $columns[] = [
                            'label' => 'CESS%',
                            'value' => function ($data) {
                                return $data->cess_per;
                            }
                        ];
                    } else if ($select == 'round_amt') {
                        $columns[] = [
                            'label' => 'Amount',
                            'value' => function ($data) {
                                return $data->amount;
                            }
                        ];

                     } else if ($select == 'invoice_amt') {
                        $columns[] = [
                            'label' => 'Invoice Amount',
                            'value' => function ($data) {
                                return isset($data->purchaseBill) ? $data->purchaseBill->net_bill_amount : "";
                            }
                        ];
                    } else if ($select == 'basic_value') {
                        $columns[] = [
                            'label' => 'Rate',
                            'value' => function ($data) {
                                return $data->mrp;
                            }
                        ];
                    } else if ($select == 'discount') {
                        $columns[] = [
                            'label' => 'Discount',
                            'value' => function ($data) {
                                return $data->getMainDiscount();
                            }
                        ];
                    } else if ($select == 'gst_amt') {
                        $columns[] = [
                            'label' => 'GST',
                            'value' => function ($data) {
                                return $data->getTotalGstAmt();
                            }
                        ];
                    } else if ($select == 'cgst_amt') {
                        $columns[] = [
                            'label' => 'CGST',
                            'value' => function ($data) {
                                return $data->cgst_amt;
                            }
                        ];
                    } else if ($select == 'sgst_amt') {
                        $columns[] = [
                            'label' => 'SGST',
                            'value' => function ($data) {
                                return $data->sgst_amt;
                            }
                        ];
                    } else if ($select == 'igst_amt') {
                        $columns[] = [
                            'label' => 'IGST',
                            'value' => function ($data) {
                                return $data->getIgstAmount();
                            }
                        ];
                    } else if ($select == 'cess_amt') {
                        $columns[] = [
                            'label' => 'CESS Amount',
                            'value' => function ($data) {
                                return $data->getCessAmount();
                            }
                        ];
                    } else if ($select == 'grn_no') {
                        $columns[] = [
                            'label' => 'GRN NUMBER',
                            'value' => function ($data) {
                                return isset($data->purchaseBill) ? 'Gr-' . $data->purchaseBill->grn_refrence_no : "";
                                // return 'Gr-'.$data->purchase_bill_id;
                            }
                        ];
                    } else if ($select == 'scheme') {
                        $columns[] = [
                            'label' => 'SCHEME AND DISCOUNT',
                            'value' => function ($data) {
                                return $data->getSchemeDiscount();
                            }
                        ];
                    } else {
                        $columns[] = $select;
                    }
                }
            }

            return $columns;
        }

    public function getColumns($selectcolumns = [])
        {
            if (! empty($selectcolumns)) {
                $selected = $selectcolumns;
            } else {

                $selected = [
                    'date',
                    'vendor',
                    'hsn_code',
                    'bill_no',
                    'tax_no',
                    'gst_per',
                    'cgst_per',
                    'sgst_per',
                    'igst_per',
                    'cess_per',
                    'net_amount',
                    'basic_value',
                    'discount',
                    'gst_amt',
                    'cgst_amt',
                    'sgst_amt',
                    'igst_amt',
                    'cess_amt',
                    // 'grn_no',
                    // 'scheme'
                ];
            }

            if ($selected) {
                foreach ($selected as $select) {
                    if ($select == 'date') {
                        $columns[] = [
                            'label' => 'Date',
                            'value' => function ($data) {
                                return isset($data->purchaseBill) ? $data->purchaseBill->start_date : "";
                            }
                        ];
                    } else if ($select == 'vendor') {
                        $columns[] = [
                            'label' => 'Vendor',
                            'value' => function ($data) {
                                return isset($data->purchaseBill) ? $data->purchaseBill->vendor : "";
                            }
                        ];
                    } else if ($select == 'hsn_code') {
                        $columns[] = [
                            'label' => 'HSN Code',
                            'value' => function ($data) {
                                return isset($data->tax) ? $data->tax->hrn_code : "";
                            }
                        ];
                    } else if ($select == 'bill_no') {
                        $columns[] = [
                            'label' => 'Bill No',
                            'value' => function ($data) {
                              return isset($data->purchaseBill) ? '' . $data->purchaseBill->getOrderBillNo() . '' : "";
                            }
                        ];
                    } else if ($select == 'tax_no') {
                        $columns[] = [
                            'label' => 'GST NO',
                            'value' => function ($data) {
                                return $data->getVendorTAXNO();
                            }
                        ];
                    } else if ($select == 'gst_per') {
                        $columns[] = [
                            'label' => 'GST%',
                            'value' => function ($data) {
                                return $data->getTotalGstPer();
                            }
                        ];
                    } else if ($select == 'cgst_per') {
                        $columns[] = [
                            'label' => 'CGST%',
                            'value' => function ($data) {
                                return $data->getTaxPercentage("cgst_per");
                            }
                        ];
                    } else if ($select == 'sgst_per') {
                        $columns[] = [
                            'label' => 'SGST%',
                            'value' => function ($data) {
                                return $data->getTaxPercentage("sgst_per");
                            }
                        ];
                    } else if ($select == 'igst_per') {
                        $columns[] = [
                            'label' => 'IGST%',
                            'value' => function ($data) {
                                return $data->getTaxPercentage("igst_per");
                            }
                        ];
                    } else if ($select == 'cess_per') {
                        $columns[] = [
                            'label' => 'CESS%',
                            'value' => function ($data) {
                                return $data->getTaxPercentage("cess_per");
                            }
                        ];
                    } else if ($select == 'net_amount') {
                        $columns[] = [
                            'label' => 'Net Amount',
                            'value' => function ($data) {
                                return isset($data->purchaseBill) ? $data->purchaseBill->net_bill_amount : "";
                            }
                        ];
                    } else if ($select == 'basic_value') {
                        $columns[] = [
                            'label' => 'Basic Value',
                            'value' => function ($data) {
                                return $data->getBasicAmount();
                            }
                        ];
                    } else if ($select == 'discount') {
                        $columns[] = [
                            'label' => 'Discount',
                            'value' => function ($data) {
                                return $data->getMainDiscount();
                            }
                        ];
                    } else if ($select == 'gst_amt') {
                        $columns[] = [
                            'label' => 'GST',
                            'value' => function ($data) {
                                return $data->getTotalGstAmt();
                            }
                        ];
                    } else if ($select == 'cgst_amt') {
                        $columns[] = [
                            'label' => 'CGST',
                            'value' => function ($data) {
                                return $data->getCgstAmount();
                            }
                        ];
                    } else if ($select == 'sgst_amt') {
                        $columns[] = [
                            'label' => 'SGST',
                            'value' => function ($data) {
                                return $data->getSgstAmount();
                            }
                        ];
                    } else if ($select == 'igst_amt') {
                        $columns[] = [
                            'label' => 'IGST',
                            'value' => function ($data) {
                                return $data->getIgstAmount();
                            }
                        ];
                    } else if ($select == 'cess_amt') {
                        $columns[] = [
                            'label' => 'CESS Amount',
                            'value' => function ($data) {
                                return $data->getCessAmount();
                            }
                        ];
                    } else if ($select == 'grn_no') {
                        $columns[] = [
                            'label' => 'GRN NUMBER',
                            'value' => function ($data) {
                                return isset($data->purchaseBill) ? 'Gr-' . $data->purchaseBill->grn_refrence_no : "";
                                // return 'Gr-'.$data->purchase_bill_id;
                            }
                        ];
                    // } else if ($select == 'scheme') {
                        // $columns[] = array(
                            // 'label' => 'SCHEME AND DISCOUNT',
                            // 'value' => function ($data) {
                                // return $data->getSchemeDiscount();
                            // }
                        // );
                    } else {
                        $columns[] = $select;
                    }
                }
            }

            return $columns;
        }

    public function getTotalGstAmt()
        {
            $total = 0;
            $purchase_bill_id = $this->purchase_bill_id;
            $details = PurchaseBillDetail::find()->where([
                'purchase_bill_id' => $purchase_bill_id,
                'tax_id' => $this->tax_id
            ])->all();
            if ($details) {
                foreach ($details as $detail) {
                    $cgst = $detail->getTaxPercentage("cgst_amt");
                    $sgst = $detail->getTaxPercentage("sgst_amt");
                    $cess = $detail->getTaxPercentage("cess_amt");
                    $igst = $detail->getTaxPercentage("igst_amt");
                    $total = $total + ($cgst + $sgst);
                }
            }
            return $total;
        }

    public function getTotalTaxAmt()
        {
            $total = 0;
            $purchase_bill_id = $this->purchase_bill_id;
            $details = B2bPurchaseBillDetail::find()->where([
                'purchase_bill_id' => $purchase_bill_id
                // 'tax_id' => $this->tax_id
            ])->all();
            if ($details) {
                foreach ($details as $detail) {
                    $cgst = $detail->getTaxPercentage("cgst_amt");
                    $sgst = $detail->getTaxPercentage("sgst_amt");
                    $cess = $detail->getTaxPercentage("cess_amt");
                    $igst = $detail->getTaxPercentage("igst_amt");
                    $total = $total + ($cgst + $sgst +  $cess +$igst);
                }
            }
            return $total;
        }

    /**
     * Yii 1's getTotalNetAmt(): the bill's own lines summed.
     *
     * Not ported, and the B2B purchase bill report calls it, so that column
     * was a fatal waiting for the first person to open the page.
     */
    public function getTotalNetAmt()
    {
        $total = 0;
        foreach (B2bPurchaseBillDetail::findAll(
                     ['purchase_bill_id' => $this->purchase_bill_id]) as $detail) {
            $total = $total + $detail->amount;
        }

        return $total;
    }

    public function getTotalCgstAmt()
        {
            $total = 0;
            $purchase_bill_id = $this->purchase_bill_id;
            $details = B2bPurchaseBillDetail::find()->where([
                'purchase_bill_id' => $purchase_bill_id
                // 'tax_id' => $this->tax_id
            ])->all();
            if ($details) {
                foreach ($details as $detail) {
                    $cgst = $detail->getTaxPercentage("cgst_amt");

                    $total = $total + $cgst ;
                }
            }
            return $total;
        }

    public function getTotalSgstAmt()
        {
            $total = 0;
            $purchase_bill_id = $this->purchase_bill_id;
            $details = B2bPurchaseBillDetail::find()->where([
                'purchase_bill_id' => $purchase_bill_id
                // 'tax_id' => $this->tax_id
            ])->all();
            if ($details) {
                foreach ($details as $detail) {
                    $cgst = $detail->getTaxPercentage("sgst_amt");

                    $total = $total + $cgst ;
                }
            }
            return $total;
        }

    public function getTotalDisAmt()
        {
            $total = 0;
            $purchase_bill_id = $this->purchase_bill_id;
            $details = B2bPurchaseBillDetail::find()->where([
                'purchase_bill_id' => $purchase_bill_id
                // 'tax_id' => $this->tax_id
            ])->all();
            if ($details) {
                foreach ($details as $detail) {

                    $cess = $detail->discount_amt ;

                    $total = $total + $cess;
                }
            }
            return $total;
        }

    public function getTotalDis1Amt()
        {
            $total = 0;
            $purchase_bill_id = $this->purchase_bill_id;
            $details = B2bPurchaseBillDetail::find()->where([
                'purchase_bill_id' => $purchase_bill_id
                // 'tax_id' => $this->tax_id
            ])->all();
            if ($details) {
                foreach ($details as $detail) {

                    $cess = $detail->discount_amt1;

                    $total = $total + $cess;
                }
            }
            return $total;
        }

    public function getTotalCessAmt()
        {
            $total = 0;
            $purchase_bill_id = $this->purchase_bill_id;
            $details = B2bPurchaseBillDetail::find()->where([
                'purchase_bill_id' => $purchase_bill_id
                // 'tax_id' => $this->tax_id
            ])->all();
            if ($details) {
                foreach ($details as $detail) {

                    $cess = $detail->getTaxPercentage("cess_amt");

                    $total = $total + $cess;
                }
            }
            return $total;
        }

    public function getTotalIgstAmt()
        {
            $total = 0;
            $purchase_bill_id = $this->purchase_bill_id;
            $details = B2bPurchaseBillDetail::find()->where([
                'purchase_bill_id' => $purchase_bill_id
                // 'tax_id' => $this->tax_id
            ])->all();
            if ($details) {
                foreach ($details as $detail) {

                    $igst = $detail->getTaxPercentage("igst_amt");
                    $total = $total + $igst;
                }
            }
            return $total;
        }

    public function getDetailGstTrue()
        {
            $gst = false;

            Yii::warning(var_export($gst, true), '$$gst');

            return $gst;
        }

    public function getMainDiscount()
        {
            $amount = 0;
            $purchase_bill_id = $this->purchase_bill_id;
            $details = B2bPurchaseBillDetail::find()->where([
                'purchase_bill_id' => $purchase_bill_id,
                'tax_id' => $this->tax_id
            ])->all();
            if ($details) {
                foreach ($details as $detail) {
                    $amount = $amount + $detail->discount_amt;
                }
            }
            return '0';
        }

    public function getSchemeDiscount()
        {
            $amount = 0;
            $purchase_bill_id = $this->purchase_bill_id;
            $purchaseBill = PurchaseBill::findOne($purchase_bill_id);
            $query = B2bPurchaseBillDetail::find();
            $query->andWhere('purchase_bill_id =' . $purchase_bill_id);
            $query->orderBy(['id' => SORT_DESC]);
            $query->limit(1);
            $query->groupBy('purchase_bill_id,tax_id');
            $detail = $query->one();
            if ($detail->id == $this->id) {
                $amount = $purchaseBill->bill_other_discount;
            }
            return $amount;
        }

    public function getCgstAmount()
        {
            $amount = 0;
            $purchase_bill_id = $this->purchase_bill_id;
            $details = B2bPurchaseBillDetail::find()->where([
                'purchase_bill_id' => $purchase_bill_id,
                'tax_id' => $this->tax_id
            ])->all();
            if ($details) {
                foreach ($details as $detail) {
                    $cgst = $detail->getTaxPercentage("cgst_amt");
                    $amount = $amount + $cgst;
                }
            }
            return $amount;
        }

    public function getSgstAmount()
        {
            $amount = 0;
            $purchase_bill_id = $this->purchase_bill_id;
            $details = B2bPurchaseBillDetail::find()->where([
                'purchase_bill_id' => $purchase_bill_id,
                'tax_id' => $this->tax_id
            ])->all();
            if ($details) {
                foreach ($details as $detail) {
                    $sgst = $detail->getTaxPercentage("sgst_amt");
                    $amount = $amount + $sgst;
                }
            }
            return $amount;
        }

    public function getCessAmount()
        {
            $amount = 0;
            $purchase_bill_id = $this->purchase_bill_id;
            $details = B2bPurchaseBillDetail::find()->where([
                'purchase_bill_id' => $purchase_bill_id,
                'tax_id' => $this->tax_id
            ])->all();
            if ($details) {
                foreach ($details as $detail) {
                    $cess = $detail->getTaxPercentage("cess_amt");
                    $amount = $amount + $cess;
                }
            }
            return $amount;
        }

    public function getIgstAmount()
        {
            $amount = 0;
            $purchase_bill_id = $this->purchase_bill_id;
            $details = B2bPurchaseBillDetail::find()->where([
                'purchase_bill_id' => $purchase_bill_id,
                'tax_id' => $this->tax_id
            ])->all();
            if ($details) {
                foreach ($details as $detail) {
                    $igst = $detail->igst_amt;
                    $amount = $amount + $igst;
                }
            }
            return $amount;
        }

    public function getNetAmount()
        {
            $amount = 0;
            $purchase_bill_id = $this->purchase_bill_id;
            $details = B2bPurchaseBillDetail::find()->where([
                'purchase_bill_id' => $purchase_bill_id,
                'tax_id' => $this->tax_id
            ])->all();
            if ($details) {
                foreach ($details as $detail) {
                    $amount = $amount + $detail->amount;
                }
            }
            return $amount;
        }

    public function getItemBasicAmount()
        {
            $amount = 0;

            $purchase_bill_id = $this->purchase_bill_id;

            $query = B2bPurchaseBillDetail::find();
            $query->andWhere('purchase_bill_id =' . $purchase_bill_id);

            $detail = $query->one();

            if ($detail) {

                    $amount = $amount + ((($detail->amount)) - (($detail->getTaxPercentage("cgst_amt")) + ($detail->getTaxPercentage("sgst_amt")) + ($detail->getTaxPercentage("cess_amt")) + ($detail->getTaxPercentage("igst_amt"))));
                    if ($purchase_bill_id == '127') {
                        Yii::warning(var_export($detail->getTaxPercentage("cgst_amt"), true), '$detail->getCgstAmount()');
                        Yii::warning(var_export($detail->id, true), '$detail');
                        Yii::warning(var_export($amount, true), '$amount');
                    }

            }
            return $amount;
        }

    public function getTotalTaxableAmt()
        {
            $total = 0;
            $purchase_bill_id = $this->purchase_bill_id;
            $details = B2bPurchaseBillDetail::find()->where([
                'purchase_bill_id' => $purchase_bill_id
                // 'tax_id' => $this->tax_id
            ])->all();
            if ($details) {
                foreach ($details as $detail) {
                      // $total = $total + ((($detail->amount)) - (($detail->getTaxPercentage("cgst_amt")) + ($detail->getTaxPercentage("sgst_amt")) + ($detail->getTaxPercentage("cess_amt")) + ($detail->getTaxPercentage("igst_amt"))));

                      $total = $total + (($detail->approved_qty * $detail->price) - ($detail->discount_amt + $detail->discount_amt1));
                }
            }
            return $total;
        }

    public function getBasicAmount()
        {
            $amount = 0;
            $purchase_bill_id = $this->purchase_bill_id;
            $details = B2bPurchaseBillDetail::find()->where([
                'purchase_bill_id' => $purchase_bill_id
                // 'tax_id' => $this->tax_id
            ])->all();
            if ($details) {
                foreach ($details as $detail) {
                    // $amount = $amount + ((($detail->amount)) - (($detail->getTaxPercentage("cgst_amt")) + ($detail->getTaxPercentage("sgst_amt")) + ($detail->getTaxPercentage("cess_amt")) + ($detail->getTaxPercentage("igst_amt"))));
      $amount = $amount + (($detail->approved_qty * $detail->price) - ($detail->discount_amt + $detail->discount_amt1));

                   if ($purchase_bill_id == '127') {
                        Yii::warning(var_export($detail->getTaxPercentage("cgst_amt"), true), '$detail->getCgstAmount()');
                        Yii::warning(var_export($detail->id, true), '$detail');
                        Yii::warning(var_export($amount, true), '$amount');
                    }
                }
            }
            return $amount;
        }

    public function toArray1()
        {
            $model = $this;
            $bill = $this;
            $json_entry = null;
            if ($model) {
                $default_img = 'default.png';
                $json_entry = [];
                $json_entry['id'] = $model->id;
                $json_entry['Date'] = isset($model->purchaseBill) ? $model->purchaseBill->end_date : "";
                $json_entry['Vendor'] = isset($model->purchaseBill) ? $model->purchaseBill->vendor->name : "";
                $json_entry['HSN Code'] = isset($model->tax) ? $model->tax->hrn_code : "";
                $json_entry['Bill No'] = isset($model->purchaseBill) ? $model->purchaseBill->bill_no : "";
                $json_entry['GST NO'] = $model->getVendorTAXNO();
                $json_entry['GST Rate'] = $model->getTotalGstPer();
                $json_entry['CGST Rate'] = $model->getTaxPercentage("cgst_per");
                $json_entry['SGST Rate'] = $model->getTaxPercentage("sgst_per");

                $json_entry['CESS Rate'] = $model->getTaxPercentage("cess_per");
                $json_entry['IGST Rate'] =  $model->getTaxPercentage("igst_per");
                $json_entry['Net Amount'] = isset($model->purchaseBill) ? $model->purchaseBill->net_bill_amount : "";
                $json_entry['Basic Value'] = $model->getBasicAmount();
                $json_entry['Discount'] = $model->getMainDiscount();
                $json_entry['GST'] = $model->getTotalGstAmt();
                $json_entry['CGST'] = $model->getCgstAmount();
                $json_entry['SGST'] = $model->getSgstAmount();
                $json_entry['IGST'] = $model->getIgstAmount();
                $json_entry['CESS'] = $model->getCessAmount();
                $json_entry['GRN NUMBER'] = isset($model->purchaseBill) ? 'Gr-' . $model->purchaseBill->grn_refrence_no : "";
                $json_entry['SCHEME AND DISCOUNT'] = isset($model->purchaseBill) ? $model->getSchemeDiscount() : "";
            }
            return $json_entry;
        }

    public function gethsncode()
        {
            if ($this->hsn_code != null) {
                return $this->hsn_code;
            } else {
                return $this->item->hsn_code;
            }
        }

    public function getItemwiseColumns($selectcolumns = []) {
            if (! empty ( $selectcolumns )) {
                $selected = $selectcolumns;
            } else {
                $selected = [
                        'item_detail_id',
                        'item_id',
                        'approved_qty',
                        'mrp',
                        'amount',

                ];
            }

            if ($selected) {
                foreach ( $selected as $select ) {
                    if ($select == 'item_detail_id') {
                        $columns [] = [
                                'label' => 'Bar Code',
                                'value' => function ($data) {
                                    return isset ( $data->itemDetail ) ? $data->itemDetail->bar_code : "";
                                }
                        ];
                    }
                    if ($select == 'item_id') {
                        $columns [] = [
                                'label' => 'Item',
                                'value' => function ($data) {
                                    return $data->getItemName ();
                                }
                        ];
                    }
                    if ($select == 'price') {
                        $columns [] = [
                                'label' => 'MRP',
                                'value' => function ($data) {
                                    return $data->getItemOrderMrp ();
                                }
                        ];
                    }

                    if ($select == 'qty') {
                        $columns [] = [
                                'label' => 'Quantity',
                                'value' => function ($data) {
                                    return $data->getItemTotalQty ();
                                }
                        ];
                    } else if ($select == 'amount') {
                        $columns [] = [
                                'label' => 'Total Amount',
                                'value' => function ($data) {
                                    return $data->amount;
                                }
                        ];
                    }

                    else {
                        $columns [] = $select;
                    }
                }
            }

            return $columns;
        }

    public function getItemUnit() {
            $title = '';
            $item_detail = ItemDetail::findOne( $this->item_detail_id );
            if ($item_detail) {
                $item = Item::findOne( $item_detail->item_id );
                if ($item) {
                    $title = $item->unit;
                }
            }
            return $title;
        }

    public function getB2BOrdertotalgstAmount() {
            $tax =  $this->getB2BGroupTaxCgstAmount() + $this->getB2BGroupTaxSgstAmount()+$this->getB2BGroupTaxIgstAmount() + $this->getB2BGroupTaxCessAmount();
            return round ( $tax, 2 );

        }

    public function getB2BGroupTaxCgstAmount() {
            $amount = 0;
            $oamount = 0;
            $refund = 0;
            $taxable = $this->getTotalItemB2bTaxableAmount();
            $cgst_per = $this->cgst_per;





            $amount = $taxable * $cgst_per/100;


            return round ( $amount, 2 );

        }

    public function getB2BGroupTaxSgstAmount() {
            $taxable = $this->getTotalItemB2bTaxableAmount();
            $sgst_per = $this->sgst_per;





            $amount = $taxable * $sgst_per/100;


            return round ( $amount, 2 );




        }

    public function getB2BGroupTaxIgstAmount() {
            $taxable = $this->getTotalItemB2bTaxableAmount();
            $igst_per = $this->igst_per;





            $amount = $taxable * $igst_per/100;


            return round ( $amount, 2 );

        }

    public function getB2BGroupTaxCessAmount() {
            $taxable = $this->getTotalItemB2bTaxableAmount();
            $cess_per = $this->cess_per;





            $amount = $taxable * $cess_per/100;


            return round ( $amount, 2 );

        }

    public function getB2bPurchaseBill()
    {
        return $this->hasOne(B2bPurchaseBill::class, ['id' => 'purchase_bill_id']);
    }

    public function getItem()
    {
        return $this->hasOne(Item::class, ['id' => 'item_id']);
    }

    public function getOutlet()
    {
        return $this->hasOne(Outlet::class, ['id' => 'outlet_id']);
    }

    public function getTax()
    {
        return $this->hasOne(Tax::class, ['id' => 'tax_id']);
    }

    public function getState()
    {
        return $this->hasOne(State::class, ['id' => 'state_id']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }

    /**
     * Yii 1's reportsearch(): a listing of its own, converted as written.
     */
    public function reportsearch()
    {

		$query = self::find();
		
		$purchase_bill_ids = [];
		$query1 = B2bPurchaseBill::find();
		if($this->start_date != null){
			Criteria::compare($query1, 'start_date', $this->start_date);
		} 
		if($this->vendor_id != null){
			Criteria::compare($query1, 'vendor_id', $this->vendor_id);
		}
		
		$query1->andWhere('status ='.B2bPurchaseBill::STATUS_APPROVED);
		$purchasebills= $query1->all();
	//	Yii::warning( var_export($purchasebills, true), '$mrss');
		if($purchasebills){
			foreach($purchasebills as $purchasebill){
				$purchase_bill_ids[] = $purchasebill->id;
			}
	
		}
		
		$query->groupBy('purchase_bill_id,tax_id');
		// MySQL 5.7 sorted GROUP BY results implicitly; MySQL 8.0 does not. Order
		// explicitly by the grouped columns to preserve the previous output order.
		$query->orderBy(['purchase_bill_id' => SORT_ASC, 'tax_id' => SORT_ASC]);
		$query->andWhere(['purchase_bill_id' => $purchase_bill_ids]);
		Yii::warning( var_export(Yii::$app->session ['tally_start_date'], true), 'start_date');
		Yii::warning( var_export(Yii::$app->session ['tally_start_date'], true), 'end_date');
		if ((Yii::$app->session ['tally_start_date'] != '') && (Yii::$app->session ['tally_end_date'] != '')) {
			$query->andWhere(['between', 'date(create_time)', Yii::$app->session ['tally_start_date'], Yii::$app->session ['tally_end_date']]);
		}
		Criteria::compare($query, 'id', $this->id);
		Criteria::compare($query, 'req_qty', $this->req_qty);
		Criteria::compare($query, 'bal_qty', $this->bal_qty);
		Criteria::compare($query, 'approved_qty', $this->approved_qty);
		Criteria::compare($query, 'mrp', $this->mrp);
		Criteria::compare($query, 'price', $this->price);
		Criteria::compare($query, 'discount', $this->discount);
		Criteria::compare($query, 'discount_amt', $this->discount_amt);
		Criteria::compare($query, 'tax_id', $this->tax_id);
		Criteria::compare($query, 'other_charge', $this->other_charge);
		Criteria::compare($query, 'amount', $this->amount);
		Criteria::compare($query, 'sale_rate', $this->sale_rate);
		Criteria::compare($query, 'status', $this->status);
		Criteria::compare($query, 'type_id', $this->type_id);
		Criteria::compare($query, 'charge_amount', $this->charge_amount);
		Criteria::compare($query, 'extra_charges', $this->extra_charges);
		Criteria::compare($query, 'remarks', $this->remarks, true);
	//	$criteria->compare ( 'create_time', $this->create_time, true );
	//	$criteria->compare ( 'update_time', $this->update_time, true );
		Criteria::compare($query, 'create_user_id', $this->create_user_id);
		Criteria::compare($query, 'updated_by', $this->updated_by);
		Criteria::compare($query, 'item_detail_id', $this->item_detail_id);
		Criteria::compare($query, 'item_id', $this->item_id);
		Criteria::compare($query, 'purchase_bill_id', $this->purchase_bill_id);
		Criteria::compare($query, 'outlet_id', $this->outlet_id);
	
		$query->orderBy(['purchase_bill_id' => SORT_ASC, 'tax_id' => SORT_ASC]);

		return new ActiveDataProvider([
		    'query' => $query,
		    'totalCount' => (clone $query)->select(new \yii\db\Expression('1'))->count(),
		    'sort' => ['defaultOrder' => []],
		    'pagination' => ['pageSize' => Ui::PAGE_SIZE],
		]);
    }

    /**
     * Yii 1's purchasesearch(): a listing of its own, converted as written.
     */
    public function purchasesearch()
    {

		$query = B2bPurchaseBillDetail::find()->alias('t');
	
		
		$query->joinWith(['itemDetail' => function ($q) { $q->alias('itemDetail'); }, 'item' => function ($q) { $q->alias('item'); }]);
		
		Criteria::compare($query, 'itemDetail.bar_code', $this->item_detail_id, true);
		Criteria::compare($query, 'item.title', $this->item_id, true);
		Criteria::compare($query, 't.req_qty', $this->req_qty);
		Criteria::compare($query, 't.bal_qty', $this->bal_qty);
		Criteria::compare($query, 't.approved_qty', $this->approved_qty);
		Criteria::compare($query, 't.mrp', $this->mrp);
		Criteria::compare($query, 't.price', $this->price);
		Criteria::compare($query, 't.discount', $this->discount);
		Criteria::compare($query, 't.discount_amt', $this->discount_amt);
		Criteria::compare($query, 't.discount1', $this->discount1);
		Criteria::compare($query, 't.discount_amt1', $this->discount_amt1);
		Criteria::compare($query, 't.tax_id', $this->tax_id);
		Criteria::compare($query, 't.other_charge', $this->other_charge);
		Criteria::compare($query, 't.amount', $this->amount);
		Criteria::compare($query, 't.sale_rate', $this->sale_rate);
		Criteria::compare($query, 't.status', $this->status);
		Criteria::compare($query, 't.type_id', $this->type_id);
		Criteria::compare($query, 't.igst_per', $this->igst_per, true);
		Criteria::compare($query, 't.cgst_per', $this->cgst_per, true);
		Criteria::compare($query, 't.sgst_per', $this->sgst_per, true);
		Criteria::compare($query, 't.cess_per', $this->cess_per, true);
		Criteria::compare($query, 't.igst_amt', $this->igst_amt, true);
		Criteria::compare($query, 't.cgst_amt', $this->cgst_amt, true);
		Criteria::compare($query, 't.sgst_amt', $this->sgst_amt, true);
		Criteria::compare($query, 't.cess_amt', $this->cess_amt, true);
		Criteria::compare($query, 't.charge_amount', $this->charge_amount);
		Criteria::compare($query, 't.extra_charges', $this->extra_charges);
		Criteria::compare($query, 't.remarks', $this->remarks, true);
		Criteria::compare($query, 't.create_time', $this->create_time, true);
		Criteria::compare($query, 't.update_time', $this->update_time, true);
		Criteria::compare($query, 't.create_user_id', $this->create_user_id);
		Criteria::compare($query, 't.updated_by', $this->updated_by);
		
		Criteria::compare($query, 't.purchase_bill_id', $this->purchase_bill_id);
		Criteria::compare($query, 't.outlet_id', $this->outlet_id);
	
		$query->orderBy(['t.order' => SORT_ASC]);

		return new ActiveDataProvider([
		    'query' => $query,
		    'sort' => ['defaultOrder' => []],
		    'pagination' => ['pageSize' => 100],
		]);
    }

    /**
     * Yii 1's itemwisesearch(): a listing of its own, converted as written.
     */
    public function itemwisesearch()
    {

		$query = B2bPurchaseBillDetail::find()->alias('t');

		$query->joinWith(['itemDetail' => function ($q) { $q->alias('itemDetail'); }, 'item' => function ($q) { $q->alias('item'); }, 'b2bPurchaseBill' => function ($q) { $q->alias('b2bPurchaseBill'); }]);
		$query->select('t.*, SUM(t.approved_qty) AS approved_qty, SUM(t.amount) AS amount ');
		$query->andWhere('b2bPurchaseBill.status ='. B2bPurchaseBill::STATUS_APPROVED);
		$query->groupBy('item_detail_id');
		// MySQL 5.7 sorted GROUP BY results implicitly; MySQL 8.0 does not. Order
		// explicitly by the grouped columns to preserve the previous output order.
		$query->orderBy(['item_detail_id' => SORT_ASC]);
		if ((Yii::$app->session ['start_date'] != '') && (Yii::$app->session ['end_date'] != '')) {
			$order_ids = [];
			$query1 = B2bPurchaseBill::find()->alias('t');
			$query1->andWhere(['between', 't.start_date', Yii::$app->session ['start_date'], Yii::$app->session ['end_date']]);
			$query1->andWhere('status ='. B2bPurchaseBill::STATUS_APPROVED);
			$B2bPurchaseBill = $query1->all();
			
			
			
			// echo"<pre>"; print_r($B2bPurchaseBill); die;
			if($B2bPurchaseBill){
				foreach($B2bPurchaseBill as $order){
					$order_ids[] = $order->id;
				}
			}
			$query->andWhere(['t.purchase_bill_id' => $order_ids]);
			
		}
		
		
		
	
		if(!empty(Yii::$app->session['item_id'])){
			$query->andWhere(['item.id' => Yii::$app->session['item_id']]);
		}else{
			Criteria::compare($query, 'item.title', $this->item_id, true);
		}
		// $criteria->compare('order.customer_id', $this->customer_id);
		Criteria::compare($query, 'itemDetail.bar_code', $this->item_detail_id, true);

		// $criteria->compare('t.tax_amount', $this->tax_amount,true);
		// $criteria->compare('t.purchase_bill_id', $this->id);
		Criteria::compare($query, 't.tax_id', $this->tax_id);
	
		
		Criteria::compare($query, 't.price', $this->price, true);

		Criteria::compare($query, 't.discount_amt', $this->discount_amt, true);

		/* $orderitems = OrderItem::model()->findAll($criteria);
		if($orderitems){
		$total = 0;
			foreach($orderitems as $orderitem){
				
				$total = $total + $orderitem->getItemTotalAmount();
			}
		Yii::$app->session ['itemwise_total_amt']=number_format($total,2);
		} */
		$query->orderBy(['item_detail_id' => SORT_ASC]);

		return new ActiveDataProvider([
		    'query' => $query,
		    'totalCount' => (clone $query)->select(new \yii\db\Expression('1'))->count(),
		    'sort' => ['defaultOrder' => []],
		    'pagination' => ['pageSize' => Ui::PAGE_SIZE],
		]);
    }

    /**
     * Yii 1's b2bTaxwise(): a listing of its own, converted as written.
     */
    public function b2bTaxwise()
    {

	
		$order_ids = [];
		$query1 = B2bPurchaseBill::find()->alias('t');
		if ((Yii::$app->session ['order_b2b_start_date'] != '') && (Yii::$app->session ['order_b2b_end_date'] != '')) {
			$query1->andWhere(['between', 't.start_date', Yii::$app->session ['order_b2b_start_date'], Yii::$app->session ['order_b2b_end_date']]);
		}else{
			$query1->andWhere(['between', 't.start_date', $this->start_date, $this->end_date]);
		}
		if(Yii::$app->session ['order_mode_payment'] != ''){
		// $criteria1->addCondition ( 't.mode_of_payment ='.Yii::$app->session ['order_mode_payment']);
		}
		$orders = $query1->all();
		if($orders){
			foreach($orders as $order){
				$order_ids[] = $order->id;
			}
		}
	Yii::warning( var_export( $order_ids , true), '$order_ids_b2b');
		$query = self::find()->alias('t');
		$query->andWhere(['purchase_bill_id' => $order_ids]);
	
		$query->joinWith(['itemDetail' => function ($q) { $q->alias('itemDetail'); }, 'item' => function ($q) { $q->alias('item'); }, 'b2bPurchaseBill' => function ($q) { $q->alias('b2bPurchaseBill'); }]);
		$query->groupBy('t.tax_id');
		// MySQL 5.7 sorted GROUP BY results implicitly; MySQL 8.0 does not. Order
		// explicitly by the grouped columns to preserve the previous output order.
		$query->orderBy(['tax_id' => SORT_ASC]);
		//$criteria->group = 't.tax_id,t.order_id';
		Criteria::compare($query, 'item.title', $this->item_id, true);
	
		Criteria::compare($query, 'b2bPurchaseBill.bill_no', $this->purchase_bill_id);
		Criteria::compare($query, 'b2bPurchaseBill.start_date', $this->start_date);
		Criteria::compare($query, 'b2bPurchaseBill.vendor_id', $this->vendor_id);
		Criteria::compare($query, 'itemDetail.bar_code', $this->item_detail_id, true);
	
		Criteria::compare($query, 't.tax_id', $this->tax_id);
		Criteria::compare($query, 't.tax_amount', $this->tax_amount);
		//$criteria->compare ('tax_id', $this->tax_id );
		// $criteria->compare ('t.date(start_date)', $this->start_date );
		Criteria::compare($query, 't.approved_qty', $this->approved_qty);
		Criteria::compare($query, 't.price', $this->price);
		// $criteria->compare('t.discount_id', $this->discount_id);
		// $criteria->compare('t.discount_amt', $this->discount_amt);
		
	
		return new ActiveDataProvider([
		    'query' => $query,
		    'sort' => ['defaultOrder' => []],
		    'pagination' => ['pageSize' => Ui::PAGE_SIZE],
		]);
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

    public function rules()
    {
        return [
            [['bill_date', 'bill_no'], 'safe'],  // form-only, declared on the Yii 1 model
            [['expiry_date', 'packing_date', 'columns', 'start_date', 'tax_amount', 'end_date', 'min_amt', 'max_amt', 'tally_start_date', 'tally_end_date', 'vendor_id', 'bar_code', 'bill_amount', 'item_val_id', 'vendor'], 'safe'],  // form-only, declared on the Yii 1 model
            [['req_qty', 'item_detail_id', 'item_id', 'purchase_bill_id'], 'required'],
            [['status', 'type_id', 'create_user_id', 'updated_by', 'item_detail_id', 'purchase_bill_id', 'outlet_id'], 'integer'],
            [['mrp', 'price', 'discount', 'discount_amt', 'tax_id', 'other_charge', 'amount', 'sale_rate', 'charge_amount', 'extra_charges'], 'number'],
            [['hsn_code', 'item_val_id', 'grn_refrence_no', 'bar_code', 'tally_start_date', 'tally_end_date', 'start_date', 'remarks', 'is_free', 'order', 'margin', 'expiry_date', 'packing_date', 'vendor_id', 'create_time', 'columns', 'igst_amt', 'igst_per', 'update_time', 'discount1', 'discount_amt1', 'start_date', 'approved_qty', 'item_id', 'mrp', 'tax_id', 'other_charge', 'sale_rate', 'discount', 'discount_amt', 'other_charge', 'amount', 'price', 'cgst_per', 'sgst_per', 'cess_per', 'cgst_amt', 'sgst_amt', 'cess_amt', 'end_date', 'min_amt', 'max_amt', 'tax_amount', 'bill_amount', 'vendor'], 'safe'],
            [['bal_qty', 'status', 'type_id', 'charge_amount', 'extra_charges', 'remarks', 'create_time', 'update_time', 'updated_by', 'end_date', 'outlet_id'], 'default', 'value' => null],
            [['id', 'req_qty', 'bal_qty', 'approved_qty', 'mrp', 'price', 'discount', 'discount_amt', 'tax_id', 'other_charge', 'amount', 'sale_rate', 'status', 'type_id', 'charge_amount', 'extra_charges', 'remarks', 'create_time', 'update_time', 'create_user_id', 'updated_by', 'item_detail_id', 'purchase_bill_id', 'end_date', 'outlet_id', 'tax_amount', 'bill_amount', 'vendor'], 'safe', 'on' => 'search'],
        ];
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

		$query = B2bPurchaseBillDetail::find()->alias('t');
	
		$purchase_bill_ids = array();
		$query1 = B2bPurchaseBill::find()->alias('t');
		$user = Yii::$app->user->model;
		$role_id = $user->role_id;
		
		if($this->start_date != null){
			Criteria::compare($query1, 'start_date', $this->start_date);
		}
		
		if($this->vendor_id != null){
			Criteria::compare($query1, 'vendor_id', $this->vendor_id);
		}
		
		//$criteria1->addCondition('status ='.PurchaseBill::STATUS_UNAPPROVED);
		$query1->andWhere('status !='.B2bPurchaseBill::STATUS_APPROVED);
		$purchasebills= $query1->all();
		
		
		
		//Yii::warning( var_export($purchasebills, true), '$mrss');
		if($purchasebills){
			foreach($purchasebills as $purchasebill){
				$purchase_bill_ids[] = $purchasebill->id;
			}
		
		}
		
		$query->andWhere(['purchase_bill_id' => $purchase_bill_ids]);
		Criteria::compare($query, 't.id', $this->id);
		Criteria::compare($query, 't.req_qty', $this->req_qty);
		Criteria::compare($query, 't.bal_qty', $this->bal_qty);
		Criteria::compare($query, 't.approved_qty', $this->approved_qty);
		Criteria::compare($query, 't.mrp', $this->mrp);
		Criteria::compare($query, 't.price', $this->price);
		Criteria::compare($query, 't.discount', $this->discount);
		Criteria::compare($query, 't.discount_amt', $this->discount_amt);
		Criteria::compare($query, 't.tax_id', $this->tax_id);
		Criteria::compare($query, 't.other_charge', $this->other_charge);
		Criteria::compare($query, 't.amount', $this->amount);
		Criteria::compare($query, 't.sale_rate', $this->sale_rate);
		Criteria::compare($query, 't.status', $this->status);
		Criteria::compare($query, 't.type_id', $this->type_id);
		Criteria::compare($query, 't.charge_amount', $this->charge_amount);
		Criteria::compare($query, 't.extra_charges', $this->extra_charges);
		Criteria::compare($query, 't.remarks', $this->remarks, true);
		Criteria::compare($query, 't.create_time', $this->create_time, true);
		Criteria::compare($query, 't.update_time', $this->update_time, true);
		Criteria::compare($query, 't.create_user_id', $this->create_user_id);
		Criteria::compare($query, 't.updated_by', $this->updated_by);
		Criteria::compare($query, 't.item_detail_id', $this->item_detail_id);
		Criteria::compare($query, 't.purchase_bill_id', $this->purchase_bill_id);
		Criteria::compare($query, 't.outlet_id', $this->outlet_id);
		
		$query->orderBy(['t.order' => SORT_ASC]);

		return new ActiveDataProvider([
		    'query' => $query,
		    'sort' => ['defaultOrder' => []],
		    'pagination' => false,
		]);
    }

    public function getTotalItemB2bTaxableAmount() {
            $amount = 0;
            $oamount = 0;
            $refund = 0;
            $order_ids = [];
            $query1 = B2bPurchaseBill::find()->alias('t');
            $orders = $query1->all();
                if ($orders) {
                    foreach ( $orders as $order ) {
                        $order_ids [] = $order->id;
                    }
                }
            $query = B2bPurchaseBillDetail::find()->alias('t');
            $query->joinWith(['purchaseBill' => function ($q) { $q->alias('purchaseBill'); }]);
            $query->andWhere('tax_id =' . $this->tax_id);
            $query->andWhere(['t.purchase_bill_id' => $order_ids]);
            // $criteria->compare ( 'date(create_time)', $this->start_date );
            $query->select('sum(price*approved_qty) as price');
            $query->groupBy('purchaseBill.vendor_id,t.tax_id');
            // $criteria->addInCondition('purchase_bill_id',$order_ids);
            $order = $query->one();

            $amount = $order->price ;
            // echo"<pre>"; print_r($amount); die;
            // $refund_price = '0.00';
            // $criteria3 = new CDbCriteria ();
            // $criteria3->addCondition ( 't.tax_id =' . $this->tax_id );
            // $criteria3->compare ( 'date(orderRefund.create_time)', $this->create_date );
            // $criteria3->select = 'sum(t.price*t.qty) as qty';
            // $criteria3->with = 'orderRefund';
            // $criteria3->addInCondition('orderRefund.order_id',$order_ids);

            // $orderRefundItem = OrderRefundItem::model ()->find (;
            // if($orderRefundItem){
            // $refund_price = $orderRefundItem->qty;
            // }

            $amount = $amount ;

            return $amount;
        }

    public function getB2BGroupTaxOrderTotalAmount() {
            $amount = 0;
            $oamount = 0;
            $refund = 0;
            $order_ids = [];
            $query1 = B2bPurchaseBill::find();

                $orders = $query1->all();
                if ($orders) {
                    foreach ( $orders as $order ) {
                        $order_ids [] = $order->id;
                    }
                }

            $query = B2bPurchaseBillDetail::find();
            $query->andWhere('tax_id =' . $this->tax_id);
            // $criteria->compare ( 'date(create_time)', $this->start_date );
            $query->andWhere(['purchase_bill_id' => $order_ids]);
            $orders = $query->all();



            if($orders){
                foreach($orders as $order){
                    $oamount = $oamount + (($order->amount));
                }
            }
            //$amount = $oamount;




            // $criteria3 = new CDbCriteria ();

            // $criteria3->addCondition ( 't.tax_id =' . $this->tax_id );
            // $criteria3->with = 'orderRefund';
                // $criteria3->compare ( 'date(orderRefund.create_time)', $this->create_date );
            // $criteria3->addInCondition('orderRefund.order_id',$order_ids);
            // $orderRefundItems = OrderRefundItem::model ()->findAll(;
            // if($orderRefundItems){

                // foreach($orderRefundItems as $orderRefundItem){
                    // $refund = $refund + ((($orderRefundItem->price) * ($orderRefundItem->qty)) + ($orderRefundItem->tax_amt));

                // }
            // }
            $amount = $oamount ;


            return round ( $amount, 2 );
        }

    /**
     * Yii 1's b2bsearch(): a listing of its own, converted as written.
     */
    public function b2bsearch()
    {

		$order_ids = [];
		if(Yii::$app->session['order_item_item_id'] != ''){
			$this->item_id = Yii::$app->session['order_item_item_id'];
		}
		
		
		if(Yii::$app->session['order_item_customer_id'] != ''){
			$this->customer_id = Yii::$app->session['order_item_customer_id'];
		}
		
		
		if(Yii::$app->session['order_item_create_user_id'] != ''){
			$this->create_user_id = Yii::$app->session['order_item_create_user_id'];
		}
		
	
		Yii::warning( var_export(Yii::$app->session['order_item_item_id'], true), '$orderItems');
		$query1 = B2bPurchaseBill::find()->alias('t');
		if ((Yii::$app->session ['order_item_start_date'] != '') && (Yii::$app->session ['order_item_end_date'] != '')) {
			$query1->andWhere(['between', 't.start_date', Yii::$app->session ['order_item_start_date'], Yii::$app->session ['order_item_end_date']]);
		}
		if ((Yii::$app->session ['order_item_min_amt'] != '') && (Yii::$app->session ['order_item_max_amt'] != '')) {
			$query1->andWhere(['between', 't.total_Amt', Yii::$app->session ['order_item_min_amt'], Yii::$app->session ['order_item_max_amt']]);
		}
		// $criteria1->addCondition('t.status','1');
		$query1->andWhere('status ='.B2bPurchaseBill::STATUS_APPROVED);
		$orders = $query1->all();
		Yii::warning( var_export(Yii::$app->session['order_item_start_date'], true), '$order_item_start_date');
		Yii::warning( var_export(Yii::$app->session['order_item_end_date'], true), '$order_item_end_date');
		if($orders){
			foreach($orders as $order){
				$order_ids[] = $order->id;
			}
		}
		Yii::warning( var_export($order_ids, true), '$order_ids');
		$query = B2bPurchaseBillDetail::find()->alias('t');
		$query->andWhere(['t.purchase_bill_id' => $order_ids]);
		$query->joinWith(['itemDetail' => function ($q) { $q->alias('itemDetail'); }, 'item' => function ($q) { $q->alias('item'); }]);
		
		Criteria::compare($query, 'item.title', $this->item_id, true);
		
		// $criteria->compare('B2bPurchaseBill.id', $this->purchase_bill_id);
	
		// $criteria->compare('B2bPurchaseBill.create_user_id', $this->create_user_id);
		// $criteria->compare('b2bpurchase_bill.total_amount', $this->total_amount);
		Criteria::compare($query, 'item.mrp', $this->mrp);
		Criteria::compare($query, 'itemDetail.bar_code', $this->item_detail_id, true);
		
		Criteria::compare($query, 't.tax_id', $this->tax_id);
		Criteria::compare($query, 't.tax_amount', $this->tax_amount);
		
		// $criteria->compare('t.qty', $this->qty);
		Criteria::compare($query, 't.price', $this->price);
		// $criteria->compare('t.discount_id', $this->discount_id);
		// $criteria->compare('t.discount_amt', $this->discount_amt);
	
		

		$query->orderBy(['t.id' => SORT_DESC]);

		return new ActiveDataProvider([
		    'query' => $query,
		    'sort' => ['defaultOrder' => []],
		    'pagination' => ['pageSize' => 100],
		]);
    }

    /**
     * Yii 1's b2bTaxwisesearch(): a listing of its own, converted as written.
     */
    public function b2bTaxwisesearch()
    {

		$order_ids = [];
		if(Yii::$app->session['order_item_item_id'] != ''){
			$this->item_id = Yii::$app->session['order_item_item_id'];
		}
		
		
		if(Yii::$app->session['order_item_customer_id'] != ''){
			$this->customer_id = Yii::$app->session['order_item_customer_id'];
		}
		
		
		if(Yii::$app->session['order_item_create_user_id'] != ''){
			$this->create_user_id = Yii::$app->session['order_item_create_user_id'];
		}
		
	
		Yii::warning( var_export(Yii::$app->session['order_item_item_id'], true), '$orderItems');
		$query1 = B2bPurchaseBill::find()->alias('t');
		if ((Yii::$app->session ['order_item_start_date'] != '') && (Yii::$app->session ['order_item_end_date'] != '')) {
			$query1->andWhere(['between', 't.start_date', Yii::$app->session ['order_item_start_date'], Yii::$app->session ['order_item_end_date']]);
		}
		if ((Yii::$app->session ['order_item_min_amt'] != '') && (Yii::$app->session ['order_item_max_amt'] != '')) {
			$query1->andWhere(['between', 't.total_Amt', Yii::$app->session ['order_item_min_amt'], Yii::$app->session ['order_item_max_amt']]);
		}
		$query1->andWhere('status ='.B2bPurchaseBill::STATUS_APPROVED);
		$orders = $query1->all();
		Yii::warning( var_export(Yii::$app->session['order_item_start_date'], true), '$order_item_start_date');
		Yii::warning( var_export(Yii::$app->session['order_item_end_date'], true), '$order_item_end_date');
		if($orders){
			// 586
			foreach($orders as $order){
				$order_ids[] = $order->id;
			}
		}
		Yii::warning( var_export($order_ids, true), '$order_ids');
		$query = B2bPurchaseBillDetail::find()->alias('t');
		$query->joinWith(['itemDetail' => function ($q) { $q->alias('itemDetail'); }, 'item' => function ($q) { $q->alias('item'); }, 'b2bPurchaseBill' => function ($q) { $q->alias('b2bPurchaseBill'); }]);
		$query->andWhere(['purchase_bill_id' => $order_ids]);
		// $criteria->select ='t.*,SUM(cgst_amt) AS cgst_amt ,SUM(sgst_amt) AS sgst_amt,SUM(igst_amt) AS igst_amt,SUM(cess_amt) AS cess_amt,SUM(cgst_amt) AS cgst_amt';
		$query->select('t.*, SUM(t.cgst_amt) AS cgst_amt ,SUM(t.sgst_amt) AS sgst_amt,SUM(t.igst_amt) AS igst_amt,SUM(t.cess_amt) AS cess_amt, SUM(t.price * approved_qty) AS price, SUM(t.amount ) AS amount, SUM(t.discount_amt) AS discount_amt,SUM(t.discount_amt1) AS discount_amt1');
		$query->groupBy('t.tax_id,b2bPurchaseBill.id');
		// MySQL 5.7 sorted GROUP BY results implicitly; MySQL 8.0 does not. Order
		// explicitly by the grouped columns to preserve the previous output order.
		$query->orderBy(['t.tax_id' => SORT_ASC, 'b2bPurchaseBill.id' => SORT_ASC]);
		
		// $criteria->group = 't.tax_id,b2bPurchaseBill.vendor_id';
		Criteria::compare($query, 'item.title', $this->item_id, true);
		
		 Criteria::compare($query, 'b2bPurchaseBill.id', $this->purchase_bill_id);
	
		Criteria::compare($query, 'b2bPurchaseBill.create_user_id', $this->create_user_id);
		// $criteria->compare('purchaseBill.total_amount', $this->total_amount);
		Criteria::compare($query, 'item.mrp', $this->mrp);
		Criteria::compare($query, 'itemDetail.bar_code', $this->item_detail_id, true);
		
		Criteria::compare($query, 't.tax_id', $this->tax_id);
		Criteria::compare($query, 't.tax_amount', $this->tax_amount);
		
		// $criteria->compare('t.qty', $this->qty);
		Criteria::compare($query, 't.price', $this->price);
		// $criteria->compare('t.discount_id', $this->discount_id);
		// $criteria->compare('t.discount_amt', $this->discount_amt);
	
		

		$query->orderBy(['t.tax_id' => SORT_ASC, 'b2bPurchaseBill.id' => SORT_ASC]);

		return new ActiveDataProvider([
		    'query' => $query,
		    'totalCount' => (clone $query)->select(new \yii\db\Expression('1'))->count(),
		    'sort' => ['defaultOrder' => []],
		    'pagination' => ['pageSize' => 100],
		]);
    }

    public function TotalTax()
        {
            $bill_prefix = 'Tax';
            $total = ($this->cess_amt + $this->sgst_amt + $this->cgst_amt + $this->igst_amt);


            return $total;
        }

    public function getTaxTitle()
        {
            $bill_prefix = 'B';
            $tax = $this->tax_id;

            $bill = Tax::findOne($tax);



            return $bill->title;
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
