<?php
namespace app\models;

use app\components\Ui;

use app\components\Criteria;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/**
 * Ported from protected/models/StockAdjustLog.php (Yii 1).
 *
 * The rules matter: the Yii 1 writer saves with validation on, so a row that
 * fails `required` is silently not written and the caller reports NOK. The
 * `default` rule nulls empty values, as elsewhere in this schema.
 */
class StockAdjustLog extends ActiveRecord
{
    // Yii 1 hands out column values as strings; the option helpers
    // compare them loosely and answer wrongly for an integer 0.
    use LegacyColumnTypes;

    // Declared on the Yii 1 model and not columns: the forms post
    // to these and the actions assign them.
    public $start_date;
    public $end_date;

    public $columns;
    public static function tableName()
    {
        return '{{%stock_adjust_log}}';
    }

    public function rules()
    {
        return [
            [['columns', 'start_date', 'end_date'], 'safe'],  // form-only, declared on the Yii 1 model
            [['id', 'date', 'item_detail_id', 'item_id', 'mrp', 'current_stock', 'actual_stock', 'adjusted', 'outlet_id', 'type_id', 'status', 'create_time', 'update_time'], 'safe', 'on' => 'search'],
            [['date', 'item_detail_id', 'item_id', 'mrp', 'current_stock', 'actual_stock', 'adjusted'],
             'required'],
            [['item_detail_id', 'item_id', 'outlet_id', 'type_id', 'status'], 'integer'],
            [['mrp'], 'number'],
            [['create_time', 'update_time', 'remarks'], 'safe'],
            [['outlet_id', 'type_id', 'status', 'create_time', 'update_time'],
             'default', 'value' => null],
        ];
    }

    public function getCreateUser()
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'StockAdjustLog' : 'StockAdjustLogs';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'date';
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
        $value = $this->hasAttribute('date') ? $this->date : null;

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
        return ['id' => SORT_DESC];
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

    public static function getStatusOptions($id = null)
    {
		$list = [
				"Draft",
				"Published",
				"Archive" 
		];
		if ($id === null || $id === '')
			return $list;
		if (is_numeric ( $id ))
			return $list [$id];
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

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date' => 'Date',
            'item_detail_id' => 'Bar Code',
            'item_id' => 'Item',
            'mrp' => 'Mrp',
            'current_stock' => 'Current Stock',
            'actual_stock' => 'Actual Stock',
            'adjusted' => 'Adjusted',
            'outlet_id' => 'Outlet',
            'type_id' => 'Type',
            'status' => 'Status',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'itemDetail' => 'Bar Code',
            'item' => 'Item',
            'outlet' => 'Outlet',
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

		$query = self::find()->alias('t');
		
		$query->joinWith(['itemDetail' => function ($q) { $q->alias('itemDetail'); }, 'item' => function ($q) { $q->alias('item'); }]);
		
		if (($this->start_date != '') && ($this->end_date != '')) {
			$query->andWhere(['between', 't.date', $this->start_date, $this->end_date]);
		}
		if ((Yii::$app->session ['adjust_start_date'] != '') && (Yii::$app->session ['adjust_end_date'] != '')) {
			$query->andWhere(['between', 't.date', Yii::$app->session ['adjust_start_date'], Yii::$app->session ['adjust_end_date']]);
		}else{
			Criteria::compare($query, 't.date', $this->date, true);
		}
		Yii::warning( var_export(Yii::$app->session ['adjust_start_date'], true), 'startt_date');
		Yii::warning( var_export(Yii::$app->session ['adjust_end_date'], true), 'end_date');
		
		if ($this->create_user_id != null) {
			Criteria::compare($query, 't.create_user_id', $this->create_user_id);
		}
		Criteria::compare($query, 'itemDetail.bar_code', $this->item_detail_id, true);
		Criteria::compare($query, 'item.title', $this->item_id, true);
		Criteria::compare($query, 't.mrp', $this->mrp);
		Criteria::compare($query, 't.current_stock', $this->current_stock);
		Criteria::compare($query, 't.actual_stock', $this->actual_stock);
		Criteria::compare($query, 't.adjusted', $this->adjusted);
		Criteria::compare($query, 't.outlet_id', $this->outlet_id);
		
		return new ActiveDataProvider([
		    'query' => $query,
		    'sort' => ['defaultOrder' => []],
		    'pagination' => ['pageSize' => Ui::PAGE_SIZE],
		]);
    }

    public function getColumns($selectcolumns = []){
            if(!empty($selectcolumns)){
                $selected = $selectcolumns;
            }else{
                $selected = [
                        'date',
                    'remarks',
                        'username',
                        'item_id',
                        'item_detail_id',
                        'outlet_id',
                        'mrp',
                        'current_stock',
                        'actual_stock',
                        'adjusted',
                        'amount'

                ];

            }

            if($selected){
                foreach($selected as $select){
                    if($select == 'username'){
                        $columns[] = [
                                'label' => 'Username',
                                'value' => function ($data) {
                                return isset ( $data->createUser ) ? $data->createUser : "";
                                }
                                ];
                    }
                    else if($select == 'item_id'){
                        $columns[] = [
                                'label' => 'Item',
                                'value' => function ($data) {
                                return isset ( $data->item ) ? $data->item : "";
                                }
                                ];
                    }
                    else if($select == 'remarks'){
                        $columns[] = [
                            'label' => 'Remarks',
                            'value' => function ($data) {
                            return isset ( $data->remarks ) ? $data->remarks : "";
                            }
                            ];
                    }
                    else if($select == 'item_detail_id'){
                        $columns[] =[
                                'label' => 'Bar Code',
                                'value' => function ($data) {
                                return  isset ( $data->itemDetail ) ? $data->itemDetail : "";
                                }
                                ];
                    }
                    else if($select == 'outlet_id'){
                        $columns[] = [
                                'label' => 'Outlet',
                                'value' => function ($data) {
                                return  isset ( $data->outlet ) ? $data->outlet : "";
                                }
                                ];
                    }
                    else if($select == 'amount'){
                        $columns[] = [
                                'label' => 'Amount',
                                'value' => function ($data) {
                                return  $data->getAdjustedAmount();
                                }
                                ];
                    }
                    else{
                        $columns[] = $select;
                    }
                }
            }

            /*     $columns[] =

            array (

            'bill_no',
            'bill_date',
            array (
            'label' => 'Customer',
            'value' => function ($data) {
            return isset ( $data->customer ) ? $data->customer : "";
            }
            ),

            'total_amt',
            'discount_amt',
            'paid_amt',
            array (
            'label' => 'Mode Of Payment',
            'value' => function ($data) {
            return Order::getPaymentTypeOptions ( $data->mode_of_payment );
            }
            ),
            array (
            'label' => 'Mode Of Delivery',
            'value' => function ($data) {
            return Order::getDeliveryTypeOptions ( $data->mode_of_delivery );
            }
            ),
            array (
            'label' => 'Order Type',
            'value' => function ($data) {
            return Order::getTypeOptions ( $data->type_id );
            }
            ),


            array (
            'label' => 'Outlet',
            'value' => function ($data) {
            return isset ( $data->outlet ) ? $data->outlet : "";
            }
            )
            )*/


            return $columns;
        }

    public function getAdjustedAmount(){
            $amount = '0.00';
            $price = isset($this->item)?$this->item->purchase_price:$this->mrp;
            $amount = ($this->adjusted) * ($price);
            return $amount;
        }

    public function getUserNameById()
        {
            $user = User::find()->andWhere('state_id=' . User::STATUS_ACTIVE)->andWhere([ 'id'=>$this->create_user_id])->one();
            // print_r($user->full_name); die;
            return $user ? $user->full_name : '';
        }

    public function getItemDetail()
    {
        return $this->hasOne(ItemDetail::class, ['id' => 'item_detail_id']);
    }

    public function getItem()
    {
        return $this->hasOne(Item::class, ['id' => 'item_id']);
    }

    public function getOutlet()
    {
        return $this->hasOne(Outlet::class, ['id' => 'outlet_id']);
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
}
