<?php
namespace app\models;

use app\components\Ui;

use app\components\Criteria;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/** Ported from protected/models/Customer.php (Yii 1). */
class Customer extends ActiveRecord
{
    // Yii 1 hands out column values as strings; the option helpers
    // compare them loosely and answer wrongly for an integer 0.
    use LegacyColumnTypes;

    public const Is_Email_sent = 1;
    public const Is_Email_pending = 0;
    public $attach_file;
    public $message;
    public $subject;
    public $columns;
    public static function tableName()
    {
        return '{{%customer}}';
    }

    public static function findByPhone($phone)
    {
        return static::findOne(['contact_no' => $phone]);
    }

    public static function getUserByContactNo($contactNo)
    {
        return static::findOne(['contact_no' => $contactNo]);
    }

    /**
     * Payload from Customer::toArray().
     *
     * Note the side effect, carried over deliberately: reading a customer
     * creates a zeroed loyalty row if none exists. That means the list actions
     * write, which is surprising but is what the Yii 1 version does, and
     * changing it here would make the two implementations disagree.
     */
    public function toApiArray()
    {
        return [
            'id' => (string)$this->id,
            'name' => isset($this->name) ? $this->name : '',
            'contact_no' => isset($this->contact_no) ? $this->contact_no : '',
            'loyalty' => CustomerLoyalty::getOrCreate($this->id)->asArray(),
        ];
    }

    /** Payload from Customer::toArray1() - the list variant, with is_enable_wa. */
    public function toApiArray1()
    {
        return [
            'id' => (string)$this->id,
            'name' => isset($this->name) ? $this->name : '',
            'contact_no' => isset($this->contact_no) ? $this->contact_no : '',
            'is_enable_wa' => isset($this->is_enable_wa)
                ? (string)$this->is_enable_wa
                : 0,
            'loyalty' => CustomerLoyalty::getOrCreate($this->id)->asArray(),
        ];
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'Customer' : 'Customers';
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

    public static function getStatusOptions($id = null)
    {
		$list = ["Active","Inactive"];
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
            [['name', 'contact_no'], 'required'],
            [['city_id', 'state_id', 'country_id', 'credit_limit', 'payment_days', 'status', 'type_id', 'create_user_id', 'updated_by', 'is_enable_wa'], 'integer'],
            [['opening_balance'], 'number'],
            [['email'], 'email'],
            [['email'], 'unique'],
            [['name', 'email', 'fax'], 'string', 'max' => 255],
            [['create_time', 'update_time', 'columns', 'subject', 'message', 'attach_file', 'is_email', 'email_date'], 'safe'],
            [['email', 'contact_no', 'status', 'type_id', 'city_id', 'create_time', 'update_time', 'updated_by'], 'default', 'value' => null],
            [['id', 'name', 'email', 'fax', 'address', 'city_id', 'state_id', 'country_id', 'zip_code', 'opening_balance', 'credit_limit', 'payment_days', 'contact_no', 'status', 'type_id', 'create_time', 'update_time', 'create_user_id', 'updated_by', 'is_enable_wa'], 'safe', 'on' => 'search'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'fax' => 'Fax',
            'address' => 'Address',
            'city_id' => 'City',
            'state_id' => 'State',
            'country_id' => 'Country',
            'subject' => 'Subject',
            'message' => 'Message',
            'attach_file' => 'Attachment',
            'zip_code' => 'Zip Code',
            'opening_balance' => 'Opening Balance',
            'credit_limit' => 'Credit Limit',
            'payment_days' => 'Payment Days',
            'contact_no' => 'Contact No',
            'status' => 'Status',
            'type_id' => 'Type',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'create_user_id' => 'User',
            'updated_by' => 'User',
            'createUser' => 'User',
            'updatedBy' => 'User',
            'orders' => 'Orders',
            'orderHolds' => 'OrderHolds',
            'orderRefunds' => 'OrderRefunds',
            'is_enable_wa' => 'Is Enable WA',
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

        foreach (['id', 'city_id', 'state_id', 'country_id', 'zip_code', 'opening_balance', 'credit_limit', 'payment_days', 'contact_no', 'status', 'type_id', 'create_user_id', 'updated_by'] as $attr) {
            Criteria::compare($query, $attr, $this->$attr);
        }
        foreach (['name', 'email', 'fax', 'address', 'create_time', 'update_time'] as $attr) {
            Criteria::compare($query, $attr, $this->$attr, true);
        }

        return $provider;
    }

    public function toArray1() {
            $model = $this;
            $json_entry = null;
            if ($model) {
                $default_img = 'default.png';
                $json_entry = [];
                $json_entry ['id'] = $model->id;
                $json_entry ['name'] = isset ( $model->name ) ? $model->name : '';
                $json_entry ['contact_no'] = isset ( $model->contact_no ) ? $model->contact_no : '';
                $json_entry ['is_enable_wa'] = isset ( $model->is_enable_wa ) ? $model->is_enable_wa : 0;
                $json_entry ['loyalty'] = CustomerLoyalty::getOrCreateCustomerLoyalty($model->id)->asArray();



            }
            return $json_entry;
        }

    public static function getUserByEmail($name)
        {
            $user = Customer::findOne(['email'=>$name]);
            return $user;
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


                        $customer= new Customer();

                        if (isset($arrays['Name']) || isset($arrays['﻿"Name"']) || isset($arrays['���"Name"'])) {

                            if (isset($arrays['Name'])) {
                                $customer->name = $itemcat_values[$arrays['Name']];

                            } else if(isset($arrays['﻿"Name"'])) {
                                $customer->name = $itemcat_values[$arrays['﻿"Name"']];
                            }else{
                                $customer->name = $itemcat_values[$arrays['���"Name"']];
                            }
                        }

                        if (isset($arrays['Opening Balance'])) {

                            $customer->opening_balance =$itemcat_values[$arrays['Opening Balance']];
                        }

                        if (isset($arrays['Credit Limit'])) {

                            $customer->credit_limit =$itemcat_values[$arrays['Credit Limit']];
                        }
                        if (isset($arrays['Payment Days'])) {

                            $customer->payment_days =$itemcat_values[$arrays['Payment Days']];
                        }
                        if (isset($arrays['Email'])) {

                            $customer->email = $itemcat_values[$arrays['Email']];
                        }
                        if (isset($arrays['Fax'])) {

                            $customer->fax = $itemcat_values[$arrays['Fax']];
                        }
                        if (isset($arrays['Contact No'])) {

                            $customer->contact_no = $itemcat_values[$arrays['Contact No']];
                        }
                        if (isset($arrays['Address'])) {

                            $customer->address = $itemcat_values[$arrays['Address']];
                        }
                        if (isset($arrays['Zip Code'])) {

                            $customer->zip_code = $itemcat_values[$arrays['Zip Code']];
                        }
                        if (isset($arrays['City'])) {
                            $query = City::find();
                            Criteria::compare($query, 'title', $itemcat_values[$arrays['City']]);
                            $city = $query->one();
                            if($city){
                                $customer->city_id =$city->id;
                            }

                        }
                        if (isset($arrays['State'])) {
                            $query = City::find();
                            Criteria::compare($query, 'title', $itemcat_values[$arrays['State']]);
                            $state = $query->one();
                            if($state){
                                $customer->state_id =$state->id;
                            }

                        }
                        if (isset($arrays['Country'])) {
                            $query = City::find();
                            Criteria::compare($query, 'title', $itemcat_values[$arrays['Country']]);
                            $country = $query->one();
                            if($country){
                                $customer->country_id =$country->id;
                            }

                        }

                        if ($customer->save()) {


                        } else {
                            print_R($customer->getErrors());
                            exit;
                            $set = false;
                        }
                    }
                    if ($set == true) {
                        $transaction->commit();
                        return 1;
                    }
                } catch (Exception $e) {
                    $transaction->rollback();
                }
            }
            return $output;
        }

    public function getColumns($selectcolumns = []){
            if(!empty($selectcolumns)){
                $selected = $selectcolumns;
            }else{
            $selected = ['name',
                    'opening_balance',
                    'credit_limit',
                    'payment_days',
                    'email',
                    'fax',
                    'contact_no',
                    'address','State', 'City','Country','zip_code'];
            }

            if($selected){
                foreach($selected as $select){
                    if($select == 'State'){
                        $columns[] = [

                            'label' => 'State',
                            'value' => function ($data) {
                            return Vendor::getStateName ( $data->state_id );
                            }
                            ];
                    }
                    else if($select == 'City'){
                        $columns[] =[
                                    'label' => 'City',
                                    'value' => function ($data) {
                                    return Vendor::getCityName ( $data->city_id );
                                    }
                                    ];
                    }
                    else if($select == 'Country'){
                        $columns[] = [
                                            'label' => 'Country',
                                            'value' => function ($data) {
                                            return Vendor::getCountryName ( $data->country_id );
                                            }
                                            ];
                    }
                    else{
                        $columns[] = $select;
                    }
                    }
                }

        /*     $columns[] =

                    'name',
                    'opening_balance',
                    'credit_limit',
                    'payment_days',

                    'email',
                    'fax',
                    'contact_no',
                    'address',
                    array (

                            'label' => 'State',
                            'value' => function ($data) {
                            return Vendor::getStateName ( $data->state_id );
                            }
                            ),
                            array (
                                    'label' => 'City',
                                    'value' => function ($data) {
                                    return Vendor::getCityName ( $data->city_id );
                                    }
                                    ),
                                    array (
                                            'label' => 'Country',
                                            'value' => function ($data) {
                                            return Vendor::getCountryName ( $data->country_id );
                                            }
                                            ),
                                            'zip_code' */


            return $columns;
        }

    public function getCreateUser()
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
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

    public function getOrders()
    {
        return $this->hasMany(Order::class, ['customer_id' => 'id']);
    }

    public function getCustomerorders()
    {
        return $this->hasMany(Order::class, ['customer_id' => 'id']);
    }

    public function getOrderHolds()
    {
        return $this->hasMany(OrderHold::class, ['customer_id' => 'id']);
    }

    public function getOrderRefunds()
    {
        return $this->hasMany(OrderRefund::class, ['customer_id' => 'id']);
    }

    public function getCustomerLoyalty()
    {
        return $this->hasOne(CustomerLoyalty::class, ['customer_id' => 'id']);
    }
}
