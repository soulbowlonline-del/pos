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

    public $columns;
    public static function tableName()
    {
        return '{{%stock_adjust_log}}';
    }

    public function rules()
    {
        return [
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

    /** GxActiveRecord::__toString(): the representing column, or the id. */
    public function __toString()
    {
        $value = $this->hasAttribute('date') ? $this->date : null;

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
        $query = self::find();
        // Yii 1 eager-loads these, by JOIN, in the same query. That is
        // part of the result and not just an optimisation: where the
        // listing has no ORDER BY, the join decides which rows the
        // first page shows.
        $query->joinWith(['itemDetail', 'item']);
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

        foreach (['t.create_user_id', 't.mrp', 't.current_stock', 't.actual_stock', 't.adjusted', 't.outlet_id'] as $attr) {
            Criteria::compare($query, $attr, $this->$attr);
        }
        foreach (['t.date', 'itemDetail.bar_code', 'item.title'] as $attr) {
            Criteria::compare($query, $attr, $this->$attr, true);
        }

        return $provider;
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
            $user = User::model()->active()->findByAttributes([ 'id'=>$this->create_user_id]);
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
}
