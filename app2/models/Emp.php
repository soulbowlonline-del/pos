<?php
namespace app\models;

use app\components\Ui;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/** Ported from protected/models/Emp.php (Yii 1). Only outlet_id is read here. */
class Emp extends ActiveRecord
{
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
}
