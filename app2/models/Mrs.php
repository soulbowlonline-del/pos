<?php
namespace app\models;

use app\components\Criteria;

use app\components\Ui;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/** Ported from protected/models/Mrs.php (Yii 1) - a material requisition. */
class Mrs extends ActiveRecord
{
    // Yii 1 hands out column values as strings; the option helpers
    // compare them loosely and answer wrongly for an integer 0.
    use LegacyColumnTypes;

    // the full set from BaseMrs
    public const STATUS_PENDING = 0;
    public const STATUS_DONE = 1;
    public const STATUS_HALF_DONE = 2;
    public const STATUS_REJECT = 3;

    public static function tableName()
    {
        return '{{%mrs}}';
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'Mrs' : 'Mrs';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'id';
    }

    /** GxActiveRecord::__toString(): the representing column, or the id. */
    public function __toString()
    {
        $value = $this->hasAttribute('id') ? $this->id : null;

        return (string) ($value === null || $value === '' ? $this->id : $value);
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

    public static function getStatusOptions($id = null)
    {
		$list = ["Pending","Half Done","Done","Reject"];
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

    public function getMrsVendorOptions(){
            $list = [];
            $query = Mrs::find();
            $query->andWhere('status ='.Mrs::STATUS_PENDING);
            $mrss = $query->all();
            if($mrss){
                foreach($mrss as $mrs){
                    $vendor = Vendor::findOne($mrs->vendor_id);
                    if($vendor){
                        $list[$vendor->id] = $vendor->name;
                    }
                }
            }
            Yii::warning( var_export( $list ), '$list1');
            asort($list);
            Yii::warning( var_export( $list ), '$list2');
            return $list;
        }

    public function getMrns()
    {
        return $this->hasMany(Mrn::class, ['mrs_id' => 'id']);
    }

    public function getCreateUser()
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
    }

    public function getOrganization()
    {
        return $this->hasOne(Organization::class, ['id' => 'organization_id']);
    }

    public function getOutlet()
    {
        return $this->hasOne(Outlet::class, ['id' => 'outlet_id']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }

    public function getVendor()
    {
        return $this->hasOne(Vendor::class, ['id' => 'vendor_id']);
    }

    public function getMrsDetails()
    {
        return $this->hasMany(MrsDetail::class, ['mrs_id' => 'id']);
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
            'mrs_date' => 'Mrs Date',
            'mrs_update_date' => 'Mrs Update Date',
            'mrs_req_date' => 'Mrs Req Date',
            'status' => 'Status',
            'type_id' => 'Type',
            'remarks' => 'Remarks',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'vendor_id' => 'Vendor',
            'create_user_id' => 'User',
            'updated_by' => 'User',
            'outlet_id' => 'Outlet',
            'organization_id' => 'Organization',
            'mrns' => 'Mrns',
            'createUser' => 'User',
            'organization' => 'Organization',
            'outlet' => 'Outlet',
            'updatedBy' => 'User',
            'mrsDetails' => 'MrsDetails',
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

    public function rules()
    {
        return [
            [['code', 'mrs_date', 'mrs_req_date', 'outlet_id', 'organization_id'], 'required'],
            [['status', 'type_id', 'create_user_id', 'updated_by', 'outlet_id', 'organization_id'], 'integer'],
            [['code'], 'string', 'max' => 255],
            [['mrs_update_date', 'remarks', 'create_time', 'update_time', 'vendor_id', 'item_id'], 'safe'],
            [['mrs_update_date', 'status', 'type_id', 'remarks', 'create_time', 'update_time', 'updated_by'], 'default', 'value' => null],
            [['id', 'code', 'mrs_date', 'mrs_update_date', 'mrs_req_date', 'status', 'type_id', 'remarks', 'create_time', 'update_time', 'create_user_id', 'updated_by', 'outlet_id', 'organization_id'], 'safe', 'on' => 'search'],
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
            'sort' => ['defaultOrder' => self::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE],
        ]);

        $this->load($params, $this->formName());

        foreach (['id', 'status', 'type_id', 'create_user_id', 'updated_by', 'outlet_id', 'organization_id'] as $attr) {
            Criteria::compare($query, $attr, $this->$attr);
        }
        foreach (['code', 'mrs_date', 'mrs_update_date', 'mrs_req_date', 'remarks', 'create_time', 'update_time'] as $attr) {
            Criteria::compare($query, $attr, $this->$attr, true);
        }

        return $provider;
    }

    public function AssignMrs($id){
           $existmrsdetail = MrsDetail::findOne($id);
            $organization = Organization::model()->find();
            $item = Item::findOne($this->item_id);
            $mrs = Mrs::findOne(['status'=>Mrs::STATUS_PENDING,'vendor_id'=>$this->vendor_id,
                    'outlet_id'=>$this->outlet_id
            ]);
            if($mrs == null){
                $mrs = new Mrs();
            }

            $mrs->code = 'ddd';
            $mrs->mrs_date = date('Y-m-d');
            $mrs->mrs_req_date = date('Y-m-d');
            $mrs->outlet_id = $this->outlet_id;
            $mrs->vendor_id = $this->vendor_id;
            $mrs->organization_id = $organization->id;
            if($mrs->save()){
                $mrsdetail = MrsDetail::findOne(['item_detail_id'=>$existmrsdetail->item_detail_id,
                        'mrs_id'=>$mrs->id
                ]);

                if($mrsdetail == null){
                    $mrsdetail = new MrsDetail();
                }

                $mrsdetail->req_qty = $item->reorder_qty;
                $mrsdetail->item_detail_id = $existmrsdetail->item_detail_id;
                $mrsdetail->item_id = $existmrsdetail->item_id;
                $mrsdetail->outlet_id = $existmrsdetail->outlet_id;
                $mrsdetail->mrs_id = $mrs->id;
                if($mrsdetail->save()){

                }else{
                    print_r($mrsdetail->getErrors());exit;
                }

            }
            return true;
        }
}
