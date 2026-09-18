<?php
namespace app\models;

use app\components\Ui;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/** Ported from protected/models/Vendor.php (Yii 1). */
class Vendor extends ActiveRecord
{
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

    /** GxActiveRecord::__toString(): the representing column, or the id. */
    public function __toString()
    {
        $value = $this->hasAttribute('name') ? $this->name : null;

        return (string) ($value === null || $value === '' ? $this->id : $value);
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
            'outlet_id' => 'Outlet',
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
            Yii::warning( var_export( $this->id ), '$item_category_id');
            Yii::warning( var_export( $item_ids ), '$item_ids');
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
            Yii::warning( var_export( $this ), '$mrs');
            if($sale_amt < $per_purchase_amt){
                $cssClass='mrsred';
            }
            return $cssClass;
        }
}
