<?php
namespace app\models;

use app\components\Criteria;

use app\components\Ui;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/** Ported from protected/models/Vendor.php (Yii 1). */
class Vendor extends ActiveRecord
{
    // Yii 1 hands out column values as strings; the option helpers
    // compare them loosely and answer wrongly for an integer 0.
    use LegacyColumnTypes;

    // Declared on the Yii 1 model and not columns: the forms post
    // to these and the actions assign them.
    public $columns;
    public $start_date;
    public $end_date;
    public $username;
    public $email;

    public const IS_CASH = 1;
    public const ADVANCE_PAYMENT = 1;
    public const STATUS_INACTIVE = 1;
    public const STATUS_ACTIVE = 0;
    public static function tableName()
    {
        return '{{%vendor}}';
    }

    /** A vendor may hang off a parent whose name is used in reporting. */
    public function getParentvendor()
    {
        return $this->hasOne(Vendor::class, ['id' => 'parent_id']);
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'Vendor' : 'Vendors';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'name';
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
        $value = $this->hasAttribute('name') ? $this->name : null;

        return $value === null ? '' : (string) $value;
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

    public static function getStatusOptions($id = null)
    {
		$list = ["Active","InActive"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public static function getTypeOptions($id = null)
    {
		$list = ["TYPE1","TYPE2","TYPE3"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public static function getLocalVendorOptions($id = null)
    {
		$list = ["No","Yes"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public function getItemVendors()
    {
        return $this->hasMany(ItemVendor::class, ['vendor_id' => 'id']);
    }

    public function getPurchaseBills()
    {
        return $this->hasMany(PurchaseBill::class, ['vendor_id' => 'id']);
    }

    public function getPurchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, ['vendor_id' => 'id']);
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

    public function getOutlet()
    {
        return $this->hasOne(Outlet::class, ['id' => 'outlet_id']);
    }

    public function getState()
    {
        return $this->hasOne(State::class, ['id' => 'state_id']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
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
            'name' => 'Name',
            'description' => 'Remarks',
            'contact_person' => 'Contact Person',
            'person_designation' => 'Person Designation',
            'contact_no' => 'Contact No',
            'secondary_contact_no' => 'Office Contact No',
            'whatsapp_no' => 'WhatsApp No',
            'primary_address' => 'Primary Address',
            'secondary_address' => 'Secondary Address',
            'tax_no' => 'Tax No',
            'is_local_vendor' => 'Is Local Vendor',
            'is_cash' => 'Is Cash',
            'status' => 'Status',
            'type_id' => 'Type',
            'create_time' => 'Create Time',
            'city_id' => 'City',
            'state_id' => 'State',
            'parent_id' => 'Parent Vendor',
            'acc_no' => 'Account Number',
            'country_id' => 'Country',
            'outlet_id' => 'Outlet Id',
            'create_user_id' => 'User',
            'updated_by' => 'User',
            'itemVendors' => 'ItemVendors',
            'purchaseBills' => 'PurchaseBills',
            'purchaseOrders' => 'PurchaseOrders',
            'city' => 'City',
            'country' => 'Country',
            'createUser' => 'User',
            'outlet' => 'Outlet',
            'state' => 'State',
            'updatedBy' => 'User',
        ];
    }

    public static function getOutletName($outlet_id) {
            $outlet = Outlet::findOne( $outlet_id );
            if ($outlet) {
                return $outlet->title;
            }
            return '';
        }

    public static function getVendorEmail($id) {
            $vendor = Vendor::findOne( $id );
            if ($vendor) {
                $user = User::findOne( $vendor->create_user_id );
                if ($user)
                    return $user->email;
            }
            return '';
        }

    public static function getVendorUsername($id) {
            $vendor = Vendor::findOne( $id );
            if ($vendor) {
                $user = User::findOne( $vendor->create_user_id );
                if ($user)
                    return $user->username;
            }
            return '';
        }

    public static function getStateName($id) {
            $state = State::findOne( $id );
            if ($state) {

                if ($state)
                    return $state->title;
            }
            return '';
        }

    public static function getCityName($id) {
            $state = City::findOne( $id );
            if ($state) {

                if ($state)
                    return $state->title;
            }
            return '';
        }

    public static function getCountryName($id) {
            $state = Country::findOne( $id );
            if ($state) {

                if ($state)
                    return $state->title;
            }
            return '';
        }

    public static function getDesignationName($id) {
            $designation = Designation::findOne( $id );
            if ($designation) {

                if ($designation)
                    return $designation->title;
            }
            return '';
        }

    public static function getShiftName($id) {
            $empshift = EmpShift::findOne( [
                    'emp_id' => $id
            ] );
            if ($empshift) {
                $shift = Shift::findOne( [
                        'id' => $empshift->shift_id
                ] );
                if ($shift)
                    return $shift->title;
            }
            return '';
        }

    public function getVendorWiseColumns($selectcolumns = []){
            if(!empty($selectcolumns)){
                $selected = $selectcolumns;
            }else{
                $selected = [
                        'vendor' ,
                        'amount',
        'purchase_amount'

                ];

            }

            if($selected){
                foreach($selected as $select){
                    if($select == 'vendor'){
                        $columns[] = [
                                'label' => 'Vendor',
                                'value' => function ($data) {
                                return isset ( $data->name ) ? $data->name : "";
                                }
                                ];
                    }
                    else if($select == 'amount'){
                        $columns[] =[
                                'label' => 'Sale Amount',
                                'value' => function ($data) {
                                return $data->getVendorSaleTotalAmount ();
                                }
                                ];
                    }
                    else if($select == 'purchase_amount'){
                        $columns[] =[
                                'label' => 'Purchase Amount',
                                'value' => function ($data) {
                                return $data->getVendorPurchaseTotalAmount ();
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

    public function getVendorItem_ids(){
            $item_ids = [];
            $query = ItemVendor::find();
            $query->andWhere('vendor_id ='.$this->id);
            $itemvendors = $query->all();
            if($itemvendors){
                foreach($itemvendors as $itemvendor){
                    $query = ItemVendor::find();
                    $query->orderBy(['id' => SORT_DESC]);
                    $query->limit(1);
                    $query->andWhere('item_detail_id =' . $itemvendor->item_detail_id);
                    $selectvendor = $query->all();
                    if($selectvendor->vendor_id == $itemvendor->vendor_id){
                    $item_ids[] = $itemvendor->item_detail_id;
                    }
                }
            }
            Yii::warning( var_export( $this->id , true), '$item_category_id');
            Yii::warning( var_export( $item_ids , true), '$item_ids');
            return $item_ids;
        }

    public function getVendorPurchaseMargin()
        {
            $margin ='';
            $purchase_amt = $this->getVendorPurchaseTotalAmount();
            $sale_amt = $this->getVendorSaleTotalAmount();
            if($purchase_amt != 0){
            $margin = (($sale_amt - $purchase_amt)/$purchase_amt)*100;
            }
            return round($margin,2);

        }

    public function getCssClass()
        {
            $cssClass='';

            $purchase_amt = $this->getVendorPurchaseTotalAmount();
            $per_purchase_amt = (($this->getVendorPurchaseTotalAmount()) - (10/100) * ($this->getVendorPurchaseTotalAmount()));
            $sale_amt = $this->getVendorSaleTotalAmount();
            Yii::warning( var_export( $this , true), '$mrs');
            if($sale_amt < $per_purchase_amt){
                $cssClass='mrsred';
            }
            return $cssClass;
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

    public function getVendorPurchaseTotalAmount(){
            //$item_ids = $this->getVendorItem_ids();
            $amount = '0';
            $query = PurchaseBill::find();
            //$criteria1->addInCondition('item_id', $item_ids);
            if((Yii::$app->session['vendor_start_date'] != '') && (Yii::$app->session['vendor_end_date'] != '')){
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['vendor_start_date'], Yii::$app->session['vendor_end_date']]);
            }
            $query->andWhere('vendor_id ='.$this->id);
            $query->select('sum(net_bill_amount) as net_bill_amount');
            $orderitem = $query->one();
            if($orderitem){
                $amount = $orderitem->net_bill_amount;
            }
            if($amount == ''){
                $amount = '0';
            }
            return $amount;
        }

    public function getVendorPurchaseTaxAmount(){
            //$item_ids = $this->getVendorItem_ids();
            $amount = '0';
            $query = PurchaseBill::find();
            //$criteria1->addInCondition('item_id', $item_ids);
            if((Yii::$app->session['vendor_start_date'] != '') && (Yii::$app->session['vendor_end_date'] != '')){
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['vendor_start_date'], Yii::$app->session['vendor_end_date']]);
            }
            $query->andWhere('vendor_id ='.$this->id);
            $query->select('sum(tax_amount) as tax_amount');
            $orderitem = $query->one();
            if($orderitem){
                $amount = $orderitem->tax_amount;
            }
            if($amount == ''){
                $amount = '0';
            }
            return $amount;
        }

    public function getVendorDiscountAmount(){

            $amount = '0';
            $query = PurchaseBill::find();
            $query->andWhere('vendor_id ='.$this->id);
            if((Yii::$app->session['vendor_start_date'] != '') && (Yii::$app->session['vendor_end_date'] != '')){
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['vendor_start_date'], Yii::$app->session['vendor_end_date']]);
            }
            $query->select('sum(total_discount+bill_other_discount) as total_discount');
            $orderitem = $query->one();
            if($orderitem){
                $amount = $orderitem->total_discount;
            }
            if($amount == ''){
                $amount = '0';
            }
            return $amount;
        }

    public function getVendorSaleTotalAmount(){
            $item_ids = $this->getVendorItem_ids();

            $total = 0;
            $query = OrderItem::find();
            $query->andWhere(['item_id' => $item_ids]);
            if((Yii::$app->session['vendor_start_date'] != '') && (Yii::$app->session['vendor_end_date'] != '')){
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['vendor_start_date'], Yii::$app->session['vendor_end_date']]);
            }
            Yii::warning( var_export( Yii::$app->session['vendor_end_date'], true), '$start_date');
            Yii::warning( var_export( Yii::$app->session['vendor_end_date'], true), '$end_date');
            $orderitems = $query->all();
            Yii::warning( var_export( $orderitems, true), '$orderitems');
            if($orderitems){

                foreach ($orderitems as $orderitem){
                    $qty = $orderitem->qty;
                    $refund = 0;
                    $query = OrderRefund::find();
                    $query->andWhere('order_id ='.$orderitem->order_id);
                    if((Yii::$app->session['vendor_start_date'] != '') && (Yii::$app->session['vendor_end_date'] != '')){
                        $query->andWhere(['between', 'date(create_time)', Yii::$app->session['vendor_start_date'], Yii::$app->session['vendor_end_date']]);
                    }
                    $orderRefund = $query->one();
                    if($orderRefund){
                        $query = OrderRefundItem::find();
                        $query->andWhere('order_refund_id ='.$orderRefund->id);
                        $query->andWhere('item_detail_id ='.$orderitem->item_detail_id);
                        if((Yii::$app->session['vendor_start_date'] != '') && (Yii::$app->session['vendor_end_date'] != '')){
                            $query->andWhere(['between', 'date(create_time)', Yii::$app->session['vendor_start_date'], Yii::$app->session['vendor_end_date']]);
                        }
                        $query->andWhere('item_id ='.$orderitem->item_id);
                        $query->select('sum(total_amt) as total_amt');
                        $orderRefundItem = $query->one();
                        if($orderRefundItem){
                            $refund = $refund + ($orderRefundItem->total_amt);
                        }
                        /* if($orderRefundItems){
                         foreach($orderRefundItems as $orderRefundItem){
                         $refund = $refund + ($orderRefundItem->total_amt);
                         }


                            } */

                    }
                    $amt = ($orderitem->total_amt) - ($refund);
                    $total = $total + $amt;

                }
            }
            return round($total);
        }

    public function getVendorSaleTaxAmount(){
            $item_ids = $this->getVendorItem_ids();

            $total = 0;
            $query = OrderItem::find();
            $query->andWhere(['item_id' => $item_ids]);
            if((Yii::$app->session['vendor_start_date'] != '') && (Yii::$app->session['vendor_end_date'] != '')){
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['vendor_start_date'], Yii::$app->session['vendor_end_date']]);
            }
            Yii::warning( var_export( Yii::$app->session['vendor_end_date'], true), '$start_date');
            Yii::warning( var_export( Yii::$app->session['vendor_end_date'], true), '$end_date');
            $orderitems = $query->all();
            Yii::warning( var_export( $orderitems, true), '$orderitems');
            if($orderitems){

                foreach ($orderitems as $orderitem){
                    $qty = $orderitem->qty;
                    $refund = 0;
                    $query = OrderRefund::find();
                    $query->andWhere('order_id ='.$orderitem->order_id);
                    if((Yii::$app->session['vendor_start_date'] != '') && (Yii::$app->session['vendor_end_date'] != '')){
                        $query->andWhere(['between', 'date(create_time)', Yii::$app->session['vendor_start_date'], Yii::$app->session['vendor_end_date']]);
                    }
                    $orderRefund = $query->one();
                    if($orderRefund){
                        $query = OrderRefundItem::find();
                        $query->andWhere('order_refund_id ='.$orderRefund->id);
                        $query->andWhere('item_detail_id ='.$orderitem->item_detail_id);
                        if((Yii::$app->session['vendor_start_date'] != '') && (Yii::$app->session['vendor_end_date'] != '')){
                            $query->andWhere(['between', 'date(create_time)', Yii::$app->session['vendor_start_date'], Yii::$app->session['vendor_end_date']]);
                        }
                        $query->andWhere('item_id ='.$orderitem->item_id);
                        $query->select('sum(total_amt) as total_amt');
                        $orderRefundItem = $query->one();
                        if($orderRefundItem){
                            $refund = $refund + ($orderRefundItem->tax_amt);
                        }
                        /* if($orderRefundItems){
                         foreach($orderRefundItems as $orderRefundItem){
                         $refund = $refund + ($orderRefundItem->total_amt);
                         }


                         } */

                    }
                    $amt = ($orderitem->tax_amount) - ($refund);
                    $total = $total + $amt;

                }
            }
            return round($total);
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
            [['columns', 'start_date', 'end_date'], 'safe'],  // form-only, declared on the Yii 1 model
            [['username', 'email'], 'safe'],  // form-only, declared on the Yii 1 model
            [['name', 'primary_address', 'create_time', 'city_id', 'state_id', 'country_id', 'outlet_id', 'create_user_id'], 'required'],
            [['is_local_vendor', 'payment_days', 'status', 'type_id', 'city_id', 'state_id', 'country_id', 'create_user_id', 'updated_by'], 'integer'],
            [['name', 'contact_person', 'person_designation', 'tax_no'], 'string', 'max' => 255],
            [['description', 'contact_no', 'person_designation', 'secondary_address', 'opening_balance', 'payment_days', 'bank_name', 'acc_no', 'ifsc', 'is_advance_payment', 'is_cash', 'columns', 'start_date', 'end_date', 'vendor', 'amount', 'purchase_amount', 'contact_email', 'parent_id'], 'safe'],
            [['description', 'secondary_contact_no', 'secondary_address', 'tax_no', 'is_local_vendor', 'status', 'type_id', 'updated_by'], 'default', 'value' => null],
            [['id', 'name', 'description', 'contact_person', 'person_designation', 'contact_no', 'secondary_contact_no', 'primary_address', 'secondary_address', 'tax_no', 'is_local_vendor', 'status', 'type_id', 'create_time', 'city_id', 'state_id', 'country_id', 'outlet_id', 'create_user_id', 'updated_by', 'is_cash'], 'safe', 'on' => 'search'],
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
        $query = self::find();
        $provider = new ActiveDataProvider([
            'query' => $query,
            // The order goes on the query, not on the provider's sort.
            // Yii 1 sets it on the criteria, and three of these listings
            // order by a joined column - 'item.title' - which Yii 2's Sort
            // rejects as a key unless it is declared as a sortable
            // attribute. orderBy takes it as written.
            'sort' => ['defaultOrder' => []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE],
        ]);

        if (self::listingOrder()) {
            $query->orderBy(self::listingOrder());
        }

        $this->load($params, $this->formName());

        foreach ([['id', 'id'], ['contact_no', 'contact_no'], ['secondary_contact_no', 'secondary_contact_no'], ['is_local_vendor', 'is_local_vendor'], ['status', 'status'], ['type_id', 'type_id'], ['city_id', 'city_id'], ['state_id', 'state_id'], ['country_id', 'country_id'], ['outlet_id', 'outlet_id'], ['create_user_id', 'create_user_id'], ['updated_by', 'updated_by']] as [$col, $attr]) {
            Criteria::compare($query, $col, $this->$attr);
        }
        foreach ([['name', 'name'], ['description', 'description'], ['contact_person', 'contact_person'], ['person_designation', 'person_designation'], ['primary_address', 'primary_address'], ['secondary_address', 'secondary_address'], ['tax_no', 'tax_no'], ['create_time', 'create_time']] as [$col, $attr]) {
            Criteria::compare($query, $col, $this->$attr, true);
        }

        return $provider;
    }

    public function setAllValues($rows) {
            $output = 0;
            $count = count ( $rows );

            if ($count > 1) {

                $o = explode ( ',', $rows [0] );
                $arrays = array_flip ( $o );
                $set = true;
                $transaction = Yii::$app->db->beginTransaction ();
                try {
                    for($i = 1; $i < $count; $i ++) {
                        $itemcat_values = explode ( ',', $rows [$i] );

                        $usermodel = null;

                        $vendor = new Vendor ();

                        if (isset ( $arrays ['Name'] ) || isset ( $arrays ['﻿"Name"'] ) || isset ( $arrays ['���"Name"'] )) {

                            if (isset ( $arrays ['Name'] )) {
                                $vendor->name = $itemcat_values [$arrays ['Name']];
                            } else if (isset ( $arrays ['﻿"Name"'] )) {
                                $vendor->name = $itemcat_values [$arrays ['﻿"Name"']];
                            } else {
                                $vendor->name = $itemcat_values [$arrays ['���"Name"']];
                            }
                        }

                        if (isset ( $arrays ['Tax No'] )) {

                            $vendor->tax_no = $itemcat_values [$arrays ['Tax No']];
                        }

                        if (isset ( $arrays ['Is Local Vendor'] )) {
                            if ($itemcat_values [$arrays ['Is Local Vendor']] == 'Yes')
                                $vendor->is_local_vendor = 1;
                        } else {
                            $vendor->is_local_vendor = 0;
                        }

                        if (isset ( $arrays ['Contact No'] )) {
                            Yii::warning( var_export($itemcat_values [$arrays ['Contact No']], true), '$Contact No');
                            $vendor->contact_no = $itemcat_values [$arrays ['Contact No']];
                        }
                        if (isset ( $arrays ['Office Contact No'] )) {

                            $vendor->secondary_contact_no = $itemcat_values [$arrays ['Office Contact No']];
                        }
                        if (isset ( $arrays ['Contact Person'] )) {

                            $vendor->contact_person = $itemcat_values [$arrays ['Contact Person']];
                        }

                        if (isset ( $arrays ['Person Designation'] )) {

                            $vendor->person_designation = $itemcat_values [$arrays ['Person Designation']];
                        }
                        if (isset ( $arrays ['Opening Balance'] )) {

                            $vendor->opening_balance = $itemcat_values [$arrays ['Opening Balance']];
                        }
                        if (isset ( $arrays ['Payment Days'] )) {

                            $vendor->payment_days = $itemcat_values [$arrays ['Payment Days']];
                        }
                        if (isset ( $arrays ['Primary Address'] )) {
                            $address =  str_replace(";",",",$itemcat_values [$arrays ['Primary Address']]);
                            $vendor->primary_address = $address;
                        }
                        if (isset ( $arrays ['Secondary Address'] )) {
                            $address =  str_replace(";",",",$itemcat_values [$arrays ['Secondary Address']]);
                            $vendor->secondary_address = $address;
                        }
                        Yii::warning( var_export($itemcat_values, true), '$itemcat_values');
                        /*
                         * if (isset($arrays['Remarks'])) {
                         *
                         * $vendor->remarks = $itemcat_values[$arrays['Remarks']];
                         * }
                         */

                        if (isset ( $arrays ['City'] )) {
                            Yii::warning( var_export($itemcat_values [$arrays ['City']], true), '$city_id');
                            $query = City::find();
            $query->orderBy(['id' => SORT_DESC]);
                            Criteria::compare($query, 'title', $itemcat_values [$arrays ['City']]);
                            $city = $query->one();
                            if ($city) {
                                $vendor->city_id = $city->id;
                            }
                        }
                        Yii::warning( var_export($vendor->city_id, true), '$vendor->city_id');
                        if (isset ( $arrays ['State'] )) {
                            Yii::warning( var_export($itemcat_values [$arrays ['State']], true), '$State');
                            $query_2 = State::find();
            $query_2->orderBy(['id' => SORT_DESC]);
                            Criteria::compare($query_2, 'title', $itemcat_values [$arrays ['State']]);
                            $state = $query_2->one();
                            if ($state) {
                                $vendor->state_id = $state->id;
                            }
                        }
                        if (isset ( $arrays ['Country'] )) {

                            $query_3 = Country::find();
            $query_3->orderBy(['id' => SORT_DESC]);
                            Criteria::compare($query_3, 'title', $itemcat_values [$arrays ['Country']]);
                            $country = $query_3->one();
                            if ($country) {
                                $vendor->country_id = $country->id;
                            }
                        }

                        $role = UserRole::find()->where([
                                'title' => 'Vendor'
                        ])->one();
                        if (isset ( $arrays ['Email'] )) {
                            $query_4 = User::find();
            $query_4->orderBy(['id' => SORT_DESC]);
                            if (isset ( $arrays ['Email'] )) {
                                Criteria::compare($query_4, 'email', $itemcat_values [$arrays ['Email']]);
                            }
                            $usermodel = $query_4->one();
                        }
                        if ($usermodel == null) {
                            if (isset ( $arrays ['Username'] )) {
                                $query_5 = User::find();
            $query_5->orderBy(['id' => SORT_DESC]);
                                if (isset ( $arrays ['Username'] )) {
                                    Criteria::compare($query_5, 'username', $itemcat_values [$arrays ['Username']]);
                                }
                                $usermodel = $query_5->one();
                            }
                        }
                        if ($usermodel == null) {
                            $usermodel = new User ();
                        }
                        $usermodel->full_name = $vendor->name;
                        if (isset ( $arrays ['Password'] )) {

                            $usermodel->password = md5 ( $itemcat_values [$arrays ['Password']] );
                        }
                        if (isset ( $arrays ['Email'] )) {

                            $usermodel->email = $itemcat_values [$arrays ['Email']];
                        }
                        if (isset ( $arrays ['Username'] )) {

                            $usermodel->username = $itemcat_values [$arrays ['Username']];
                        }

                        $usermodel->contact_no = $vendor->contact_no;
                        $usermodel->address = $vendor->primary_address;
                        $usermodel->country = $vendor->country_id;
                        $usermodel->city = $vendor->city_id;
                        $usermodel->state = $vendor->state_id;
                        $usermodel->role_id = $role->id;
                        $usermodel->state_id = 1;
                        $usermodel->last_password_change = date ( "Y-m-d H:i:s" );

                        if ($usermodel->save ()) {

                            $vendordata = Vendor::find()->where(['create_user_id'=>$usermodel->id])->one();
                            if($vendordata != null){
                                $vendor = $vendordata;
                            }
                            if (isset ( $arrays ['Outlet'] )) {
                                $query_6 = Outlet::find();
            $query_6->orderBy(['id' => SORT_DESC]);
                                Criteria::compare($query_6, 'title', $itemcat_values [$arrays ['Outlet']]);

                                $outlet = $query_6->one();

                                if ($outlet) {
                                    $vendor->outlet_id = $outlet->id;
                                }else{
                                    $outlet = Outlet::find()->where(['status'=>Outlet::STATUS_ACTIVE])->one();
                                    if($outlet){
                                        $vendor->outlet_id = $outlet->id;
                                    }
                                }
                            }else{
                                $outlet = Outlet::find()->where(['status'=>Outlet::STATUS_ACTIVE])->one();
                                if($outlet){
                                    $vendor->outlet_id = $outlet->id;
                                }
                            }

                            $vendor->create_user_id = $usermodel->id;
                            Yii::warning( var_export($vendor, true), '$vendor');
                            if ($vendor->save ()) {
                            } else {
                                print_R ( $vendor->getErrors () );
                                exit ();
                                $set = false;
                            }
                        } else {
                            print_R ( $usermodel->getErrors () );
                            exit ();
                            $set = false;
                        }
                    }
                    if ($set == true) {
                        $transaction->commit ();
                        return 1;
                    }
                } catch ( Exception $e ) {
                    $transaction->rollback ();
                }
            }
            return $output;
        }
}
