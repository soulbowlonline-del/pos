<?php
namespace app\models;

use app\components\Criteria;

use app\components\Ui;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/** Ported from protected/models/Emp.php (Yii 1). Only outlet_id is read here. */
class Emp extends ActiveRecord
{
    // Yii 1 hands out column values as strings; the option helpers
    // compare them loosely and answer wrongly for an integer 0.
    use LegacyColumnTypes;

    public $password;
    public $username;
    public $shift_id;
    public const GENDER_BOTH = 2;
    public const ROLE_GRN = 1;
    public const ROLE_BILLING = 0;
    public const GENDER_FEMALE = 1;
    public const GENDER_MALE = 0;
    public const STATUS_INACTIVE = 1;
    public const STATUS_ACTIVE = 0;
    public static function tableName()
    {
        return '{{%emp}}';
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'Emp' : 'Emps';
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
     * The ordering Yii 1's defaultScope() put on every query for this
     * model. Null means Yii 1 applied none, and neither should this:
     * an order Yii 1 never applied is an order the user never saw.
     */
    public static function defaultOrder()
    {
        return ['id' => SORT_DESC];
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
		$list = [
				"Active",
				"InActive" 
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

    public static function getGenderOptions($id = null)
    {
		$list = [
				self::GENDER_MALE => 'Male',
				self::GENDER_FEMALE => 'Female',
			//	self::GENDER_BOTH => 'Both' 
		];
		if ($id === null || $id === '')
			return $list;
		if (is_numeric ( $id ))
			return $list [$id];
		return $id;
    }

    public static function getRoleOptions($id = null)
    {
		$list = [
				self::ROLE_BILLING => 'Billing',
				self::ROLE_GRN => 'GRN Receiving',
				//	self::GENDER_BOTH => 'Both'
		];
		if ($id === null || $id === '')
			return $list;
			if (is_numeric ( $id ))
				return $list [$id];
				return $id;
    }

    public function getShiftOptions(){
            $list = [];
            $query = Shift::find();
            $query->orderBy(['id' => SORT_DESC]);
            $query->andWhere('status ='.Shift::STATUS_ACTIVE);
            $shifts = $query->all();
            if($shifts){
                foreach($shifts as $shift){
                    $list[$shift->id] = $shift->title;
                }
            }
            return $list;
        }

    public function getCreateUser()
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
    }

    public function getDesignation()
    {
        return $this->hasOne(Designation::class, ['id' => 'designation_id']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }

    public function getEmpShifts()
    {
        return $this->hasMany(EmpShift::class, ['emp_id' => 'id']);
    }

    public function getUsers()
    {
        return $this->hasMany(User::class, ['emp_id' => 'id']);
    }

    public function getCity()
    {
        return $this->hasOne(City::class, ['id' => 'city_id']);
    }

    public function getState()
    {
        return $this->hasOne(State::class, ['id' => 'state_id']);
    }

    public function getCountry()
    {
        return $this->hasOne(Country::class, ['id' => 'country_id']);
    }

    public function getTempcity()
    {
        return $this->hasOne(City::class, ['id' => 'temp_city_id']);
    }

    public function getTempstate()
    {
        return $this->hasOne(State::class, ['id' => 'temp_state_id']);
    }

    public function getTempcountry()
    {
        return $this->hasOne(Country::class, ['id' => 'temp_country_id']);
    }

    public function getOutlet()
    {
        return $this->hasOne(Outlet::class, ['id' => 'outlet_id']);
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
            'code' => 'Code',
            'name' => 'Name',
            'email' => 'Email',
            'contact_no' => 'Contact No',
            'gender_id' => 'Gender',
            'date_of_birth' => 'Date Of Birth',
            'role_id' => 'Role',
            'date_of_joining' => 'Date Of Joining',
            'permanent_address' => 'Permanent Address',
            'shift_id' => 'Shift',
            'city_id' => 'City',
            'state_id' => 'State',
            'country_id' => 'Country',
            'temp_city_id' => 'City',
            'temp_state_id' => 'State',
            'temp_country_id' => 'Country',
            'temp_address' => 'Temp Address',
            'status' => 'Status',
            'type_id' => 'Type',
            'create_time' => 'Create Time',
            'designation_id' => 'Designation',
            'create_user_id' => 'User',
            'updated_by' => 'User',
            'outlet_id' => 'Outlet',
            'createUser' => 'Created By',
            'updatedBy' => 'Updated By',
            'designation' => 'Designation',
            'empShifts' => 'EmpShifts',
            'users' => 'Users',
        ];
    }

    public function getRoleValues(){
            $string = '';
            $list = [];
            $role_ids = explode(',',$this->role_id);
            if(!empty($role_ids)){
                foreach($role_ids as $role_id){

                        $list[] = $this->getRoleOptions($role_id);

                }
                if(!empty($list)){
                    $string = implode(',',$list);
                }
            }
            return $string;
        }

    public function getShifts(){
            $shift_ids = [];
            $shifts = EmpShift::findAll(['emp_id'=>$this->id]);
            if($shifts){
                foreach($shifts as $shift){
                    $shift_ids[] = $shift->shift_id;
                }
            }
            return $shift_ids;
        }

    public function removeShifts(){
            EmpShift::model()->deleteAllByAttributes(['emp_id'=>$this->id]);
            return true;
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
            [['code', 'name', 'email', 'username', 'contact_no', 'role_id', 'country_id', 'city_id', 'state_id', 'gender_id', 'shift_id', 'date_of_birth', 'date_of_joining', 'permanent_address', 'create_time', 'designation_id', 'create_user_id'], 'required'],
            [['password'], 'required'],
            [['code', 'contact_no', 'gender_id', 'status', 'type_id', 'designation_id', 'create_user_id', 'updated_by'], 'integer'],
            [['name', 'email'], 'string', 'max' => 255],
            [['email'], 'email'],
            [['email'], 'unique'],
            [['outlet_id', 'temp_address', 'shift_id', 'city_id', 'role_id', 'state_id', 'country_id', 'temp_city_id', 'temp_state_id', 'temp_country_id', 'username', 'password'], 'safe'],
            [['code', 'temp_address', 'status', 'type_id', 'updated_by'], 'default', 'value' => null],
            [['id', 'code', 'name', 'email', 'contact_no', 'gender_id', 'date_of_birth', 'date_of_joining', 'permanent_address', 'temp_address', 'status', 'type_id', 'create_time', 'designation_id', 'create_user_id', 'updated_by'], 'safe', 'on' => 'search'],
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

        foreach (['id', 'code', 'contact_no', 'gender_id', 'status', 'type_id', 'designation_id', 'create_user_id', 'updated_by'] as $attr) {
            Criteria::compare($query, $attr, $this->$attr);
        }
        foreach (['name', 'email', 'date_of_birth', 'date_of_joining', 'permanent_address', 'temp_address', 'create_time'] as $attr) {
            Criteria::compare($query, $attr, $this->$attr, true);
        }

        return $provider;
    }

    public function setAllValues($rows) {

            $output = 0;
            $count = count($rows);


            if ($count > 1) {

                $o = explode(',', $rows[0]);
                $arrays = array_flip($o);
                $set = true;
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    for ($i = 1; $i < $count; $i++) {
                        $itemcat_values = explode(',', $rows[$i]);


                        $emp = new Emp();

                        if (isset($arrays['Code']) || isset($arrays['﻿"Code"']) || isset($arrays['���"Code"'])) {

                            if (isset($arrays['Code'])) {
                                $emp->code = $itemcat_values[$arrays['Code']];

                            } else if(isset($arrays['﻿"Code"'])) {
                                $emp->code = $itemcat_values[$arrays['﻿"Code"']];
                            }else{
                                $emp->code = $itemcat_values[$arrays['���"Code"']];
                            }
                        }

                        if (isset($arrays['Name'])) {

                            $emp->name =$itemcat_values[$arrays['Name']];
                        }

                        if (isset($arrays['Email'])) {

                            $emp->email =$itemcat_values[$arrays['Email']];
                        }
                        if (isset($arrays['Date Of Birth'])) {

                            $emp->date_of_birth = date('Y-m-d',strtotime($itemcat_values[$arrays['Date Of Birth']]));
                        }
                        if (isset($arrays['Date Of Joining'])) {

                            $emp->date_of_joining = date('Y-m-d',strtotime($itemcat_values[$arrays['Date Of Joining']]));
                        }
                        if (isset($arrays['Gender'])) {

                            if($itemcat_values[$arrays['Gender']] == 'Male'){
                            $emp->gender_id = 0;
                            }else if($itemcat_values[$arrays['Gender']] == 'Female'){
                                $emp->gender_id = 1;
                            }else{
                                $emp->gender_id = 2;
                            }
                        }
                        if (isset($arrays['Permanent Address'])) {

                            $emp->permanent_address = $itemcat_values[$arrays['Permanent Address']];
                        }
                        if (isset($arrays['Temp Address'])) {

                            $emp->temp_address = $itemcat_values[$arrays['Temp Address']];
                        }
                        if (isset($arrays['Contact No'])) {

                            $emp->contact_no = $itemcat_values[$arrays['Contact No']];
                        }

                        if (isset($arrays['City'])) {
                            $query = City::find();
            $query->orderBy(['id' => SORT_DESC]);
                            Criteria::compare($query, 'title', $itemcat_values[$arrays['City']]);
                            $city = $query->one();
                            if($city){
                                $emp->city_id =$city->id;
                            }

                        }
                        if (isset($arrays['State'])) {
                            $query = City::find();
            $query->orderBy(['id' => SORT_DESC]);
                            Criteria::compare($query, 'title', $itemcat_values[$arrays['State']]);
                            $state = $query->one();
                            if($state){
                                $emp->state_id =$state->id;
                            }

                        }
                        if (isset($arrays['Country'])) {
                            $query = City::find();
            $query->orderBy(['id' => SORT_DESC]);
                            Criteria::compare($query, 'title', $itemcat_values[$arrays['Country']]);
                            $country = $query->one();
                            if($country){
                                $emp->country_id =$country->id;
                            }

                        }
                        if (isset($arrays['Temp City'])) {
                            $query = City::find();
            $query->orderBy(['id' => SORT_DESC]);
                            Criteria::compare($query, 'title', $itemcat_values[$arrays['Temp City']]);
                            $city = $query->one();
                            if($city){
                                $emp->temp_city_id =$city->id;
                            }

                        }
                        if (isset($arrays['Temp State'])) {
                            $query = City::find();
            $query->orderBy(['id' => SORT_DESC]);
                            Criteria::compare($query, 'title', $itemcat_values[$arrays['Temp State']]);
                            $state = $query->one();
                            if($state){
                                $emp->temp_state_id =$state->id;
                            }

                        }
                        if (isset($arrays['Temp Country'])) {
                            $query = City::find();
            $query->orderBy(['id' => SORT_DESC]);
                            Criteria::compare($query, 'title', $itemcat_values[$arrays['Temp Country']]);
                            $country = $query->one();
                            if($country){
                                $emp->temp_country_id =$country->id;
                            }

                        }
                        if (isset($arrays['Designation'])) {
                            $query = City::find();
            $query->orderBy(['id' => SORT_DESC]);
                            Criteria::compare($query, 'title', $itemcat_values[$arrays['Designation']]);
                            $designation = $query->one();
                            if($designation){
                                $emp->designation_id =$designation->id;
                            }

                        }
                        if (isset($arrays['Shift'])) {
                            $query = City::find();
            $query->orderBy(['id' => SORT_DESC]);
                            Criteria::compare($query, 'title', $itemcat_values[$arrays['Shift']]);
                            $shift = $query->one();
                            if($shift){
                                $emp->shift_id =$shift->id;
                            }

                        }
                        if ($emp->save()) {
                            $empshift = new EmpShift();
                            if (isset($arrays['Shift'])) {
                                $query = City::find();
            $query->orderBy(['id' => SORT_DESC]);
                                Criteria::compare($query, 'title', $itemcat_values[$arrays['Shift']]);
                                $shift = $query->one();
                                if($shift){
                                    $empshift->shift_id =$shift->id;
                                }

                            }
                            $empshift->emp_id =$emp->id;
                            $empshift->save();



                        } else {
                            print_R($emp->getErrors());
                            exit;
                            $set = false;
                        }
                    }
                    if ($set == true) {
                        $transaction->commit();
                        return 1;
                    }
                } catch (\Exception $e) {
                    $transaction->rollback();
                }
            }
            return $output;
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
}
