<?php
namespace app\models;

use app\components\Criteria;

use app\components\Ui;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/** Ported from protected/models/Organization.php (Yii 1). */
class Organization extends ActiveRecord
{
    // Yii 1 hands out column values as strings; the option helpers
    // compare them loosely and answer wrongly for an integer 0.
    use LegacyColumnTypes;

    public const STATUS_INACTIVE = 1;
    public const STATUS_ACTIVE = 0;
    public static function tableName()
    {
        return '{{%organization}}';
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'Organization' : 'Organizations';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'title';
    }

    /** GxActiveRecord::__toString(): the representing column, or the id. */
    public function __toString()
    {
        $value = $this->hasAttribute('title') ? $this->title : null;

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

    public function getMrns()
    {
        return $this->hasMany(Mrn::class, ['organization_id' => 'id']);
    }

    public function getMrs()
    {
        return $this->hasMany(Mrs::class, ['organization_id' => 'id']);
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

    public function getOutlets()
    {
        return $this->hasMany(Outlet::class, ['organization_id' => 'id']);
    }

    public function getPurchaseBills()
    {
        return $this->hasMany(PurchaseBill::class, ['organization_id' => 'id']);
    }

    public function getPurchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, ['organization_id' => 'id']);
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
            'title' => 'Title',
            'link' => 'Link',
            'email' => 'Email',
            'contact_no' => 'Contact No',
            'secondary_contact_no' => 'Secondary Contact No',
            'address' => 'Address',
            'tax_no' => 'Tax No',
            'status' => 'Status',
            'type_id' => 'Type',
            'create_time' => 'Create Time',
            'city_id' => 'City',
            'state_id' => 'State',
            'country_id' => 'Country',
            'create_user_id' => 'User',
            'updated_by' => 'User',
            'mrns' => 'Mrns',
            'mrs' => 'Mrs',
            'city' => 'City',
            'country' => 'Country',
            'createUser' => 'User',
            'state' => 'State',
            'updatedBy' => 'User',
            'outlets' => 'Outlets',
            'purchaseBills' => 'PurchaseBills',
            'purchaseOrders' => 'PurchaseOrders',
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
            [['title', 'email', 'contact_no', 'address', 'create_time', 'city_id', 'state_id', 'country_id', 'create_user_id'], 'required'],
            [['contact_no', 'status', 'type_id', 'city_id', 'state_id', 'country_id', 'create_user_id', 'updated_by'], 'integer'],
            [['title', 'link', 'email'], 'string', 'max' => 255],
            [['secondary_contact_no', 'tax_no'], 'safe'],
            [['status', 'type_id', 'updated_by'], 'default', 'value' => null],
            [['id', 'title', 'link', 'email', 'contact_no', 'secondary_contact_no', 'address', 'tax_no', 'status', 'type_id', 'create_time', 'city_id', 'state_id', 'country_id', 'create_user_id', 'updated_by'], 'safe', 'on' => 'search'],
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

        foreach (['id', 'contact_no', 'status', 'type_id', 'city_id', 'state_id', 'country_id', 'create_user_id', 'updated_by'] as $attr) {
            Criteria::compare($query, $attr, $this->$attr);
        }
        foreach (['title', 'link', 'email', 'address', 'create_time'] as $attr) {
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
                        $organization_values = explode(',', $rows[$i]);


                        $organization = new Organization();

                        if (isset($arrays['Title']) || isset($arrays['﻿"Title"']) || isset($arrays['���"Title"'])) {

                            if (isset($arrays['Title'])) {
                                $organization->title = $organization_values[$arrays['Title']];

                            } else if(isset($arrays['﻿"Title"'])) {
                                $organization->title = $organization_values[$arrays['﻿"Title"']];
                            }else{
                                $organization->title = $organization_values[$arrays['���"Title"']];
                            }
                        }


                        if (isset($arrays['Link'])) {

                            $organization->link =$organization_values[$arrays['Link']];
                        }
                        if (isset($arrays['Email'])) {

                            $organization->email =$organization_values[$arrays['Email']];
                        }

                        if (isset($arrays['Contact No'])) {

                            $organization->contact_no =$organization_values[$arrays['Contact No']];
                        }

                        if (isset($arrays['Address'])) {

                            $organization->address =$organization_values[$arrays['Address']];
                        }
                        if (isset($arrays['City'])) {
                            $query = City::find();
                            Criteria::compare($query, 'title', $organization_values[$arrays['City']]);
                            $city = $query->one();
                            if($city){
                                $organization->city_id =$city->id;
                            }

                        }
                        if (isset($arrays['State'])) {
                            $query = City::find();
                            Criteria::compare($query, 'title', $organization_values[$arrays['State']]);
                            $state = $query->one();
                            if($state){
                                $organization->state_id =$state->id;
                            }

                        }
                        if (isset($arrays['Country'])) {
                            $query = City::find();
                            Criteria::compare($query, 'title', $organization_values[$arrays['Country']]);
                            $country = $query->one();
                            if($country){
                                $organization->country_id =$country->id;
                            }

                        }
                        if ($organization->save()) {


                        } else {
                            print_R($organization->getErrors());
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
