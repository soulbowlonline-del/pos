<?php
namespace app\models;

use app\components\Criteria;
use app\components\Gx;
use app\components\Ui;
use Yii;
use yii\data\ActiveDataProvider;
use app\components\LegacyActiveRecord as ActiveRecord;
use yii\helpers\Html;

/**
 * Ported from protected/models/ItemTax.php and its giix base class.
 */
class ItemTax extends ActiveRecord
{
    // Yii 1 hands out column values as strings; the option helpers below
    // compare them loosely and give the wrong answer for an integer 0.
    use LegacyColumnTypes;

    // Declared on the Yii 1 model and not columns: the forms post to
    // these and the actions assign them. Yii 2 throws on an unknown
    // property, so the declarations have to come across.
    public $columns;
    public $item_id;
    public $sale_rate;
    public $match_total_tax;
    public $match_cgst;
    public $match_sgst_tax;
    public $match_cess_tax;

    public static function tableName()
    {
        return '{{%item_tax}}';
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'ItemTax' : 'ItemTaxes';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'create_time';
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
        $value = $this->hasAttribute('create_time') ? $this->create_time : null;

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
        return ['t.id' => SORT_DESC];
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
            [['sale_rate', 'match_total_tax', 'match_cgst', 'match_sgst_tax', 'match_cess_tax'], 'safe'],  // form-only, declared on the Yii 1 model
            [['columns', 'item_id'], 'safe'],  // form-only, declared on the Yii 1 model
            [['item_detail_id', 'tax_id', 'create_time', 'create_user_id'], 'required'],
            [['item_detail_id', 'tax_id', 'status', 'type_id', 'create_user_id', 'updated_by'], 'integer'],
            [['columns', 'item_id'], 'safe'],
            [['status', 'type_id', 'updated_by'], 'default', 'value' => null],
            [['match_sgst_tax', 'match_cess_tax', 'match_cgst', 'match_total_tax', 'sale_rate', 'id', 'item_detail_id', 'tax_id', 'status', 'type_id', 'create_time', 'create_user_id', 'updated_by'], 'safe', 'on' => 'search'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'item_detail_id' => 'ItemDetail',
            'tax_id' => 'Tax',
            'status' => 'Status',
            'type_id' => 'Type',
            'create_time' => 'Create Time',
            'create_user_id' => 'User',
            'updated_by' => 'User',
            'createUser' => 'User',
            'itemDetail' => 'ItemDetail',
            'tax' => 'Tax',
            'updatedBy' => 'User',
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
        $query = self::find()->alias('t');
        // Yii 1 eager-loads these, by JOIN, in the same query. That is
        // part of the result and not just an optimisation: where the
        // listing has no ORDER BY, the join decides which rows the
        // first page shows.
        // itemDetail is aliased in its own right, not only through
        // itemDetail.item. A dotted relation aliases the last segment
        // alone, so itemDetail kept the table name, and the filter below
        // - itemDetail.bar_code - asked for a column MySQL had never
        // heard of. The grid answered 200 with no rows, so searching an
        // item tax by barcode silently found nothing where Yii 1 finds it.
        $query->joinWith(['itemDetail' => function ($q) { $q->alias('itemDetail'); },
                          'itemDetail.item' => function ($q) { $q->alias('item'); },
                          'tax' => function ($q) { $q->alias('tax'); }]);
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

        foreach ([['t.id', 'id'], ['item.sale_price', 'sale_rate'], ['t.tax_id', 'tax_id'], ['t.status', 'status'], ['t.type_id', 'type_id'], ['t.create_user_id', 'create_user_id'], ['t.updated_by', 'updated_by']] as [$col, $attr]) {
            Criteria::compare($query, $col, $this->$attr);
        }
        foreach ([['itemDetail.bar_code', 'item_detail_id'], ['item.title', 'item_id'], ['tax.tax_val1', 'match_cgst'], ['tax.hrn_code', 'match_total_tax'], ['tax.tax_val2', 'match_sgst_tax'], ['tax.tax_val3', 'match_cess_tax'], ['t.create_time', 'create_time']] as [$col, $attr]) {
            Criteria::compare($query, $col, $this->$attr, true);
        }

        return $provider;
    }

    public function getItemName() {
            $title = '';
            $item_detail = ItemDetail::findOne( $this->item_detail_id );
            if ($item_detail) {
                $item = Item::findOne( $item_detail->item_id );
                if ($item) {
                    $title = $item->title;
                }
            }
            return $title;
        }

    public function getColumns($selectcolumns = []) {
            if (! empty ( $selectcolumns )) {
                $selected = $selectcolumns;
            } else {
                $selected = [
                        'bar_code' ,
                        'item',
                        'sale_rate',
                        'tax' ,
                        'hrn_code',
                        'cgst_per',
                        'cgst_amt',
                        'sgst_per' ,
                        'sgst_amt',
                        //'igst_per',
                        //'igst_amt',
                        'cess_per',
                        'cess_amt',

                ];
            }

            if ($selected) {
                foreach ( $selected as $select ) {
                    if ($select == 'bar_code') {
                        $columns [] = [
                                'label' => 'Bar Code',
                                'value' => function ($data) {
                                return isset($data->itemDetail)?$data->itemDetail->bar_code:"";
                                }
                                ];
                    } else if ($select == 'item') {
                        $columns [] = [
                                'label' => 'Item',
                                'value' => function ($data) {
                                return $data->getItemName();
                                }
                                ];
                    } else if ($select == 'sale_rate') {
                        $columns [] = [
                                'label' => 'sale_rate',
                                'value' => function ($data) {
                                return $data->getSaleRate();
                                }
                                ];
                    } else if ($select == 'tax') {
                        $columns [] = [
                                'label' => 'Tax',
                                'value' => function ($data) {
                                return isset($data->tax)?$data->tax->title:"";
                                }
                                ];
                    } else if ($select == 'hrn_code') {
                        $columns [] = [
                                'label' => 'Total Tax(%age)',
                                'value' => function ($data) {
                                return isset($data->tax)?$data->tax->hrn_code:"";
                                }
                                ];
                    } else if ($select == 'cgst_per') {
                        $columns [] = [
                                'label' => 'CGST(%age)',
                                'value' => function ($data) {
                                return isset($data->tax)?$data->tax->tax_val1:"";
                                }
                                ];
                    }  else if ($select == 'cgst_amt') {
                        $columns [] = [
                                'label' => 'CGST Amount',
                                'value' => function ($data) {
                                return $data->getCgstAmt();
                                }
                                ];
                    } else if ($select == 'sgst_per') {
                        $columns [] = [
                                'label' => 'SGST(%age)',
                                'value' => function ($data) {
                                return isset($data->tax)?$data->tax->tax_val2:"";
                                }
                                ];
                    }  else if ($select == 'sgst_amt') {
                        $columns [] = [
                                'label' => 'SGST Amount',
                                'value' => function ($data) {
                                return $data->getSgstAmt();
                                }
                                ];
                    }
                    /*else if ($select == 'igst_per') {
                        $columns [] = array (
                                'label' => 'IGST(%age)',
                                'value' => function ($data) {
                                return isset($data->tax)?$data->tax->tax_val4:"";
                                }
                                );
                    }  else if ($select == 'igst_amt') {
                        $columns [] = array (
                                'label' => 'IGST Amount',
                                'value' => function ($data) {
                                return $data->getIgstAmt();
                                }
                                );
                    } */
                    else if ($select == 'cess_per') {
                        $columns [] = [
                                'label' => 'CESS(%age)',
                                'value' => function ($data) {
                                return isset($data->tax)?$data->tax->tax_val3:"";
                                }
                                ];
                    } else if ($select == 'cess_amt') {
                        $columns [] = [
                                'label' => 'CESS Amount',
                                'value' => function ($data) {
                                return $data->getCessAmt();
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

    public function getCgstAmt(){
            $discount = 0;
            $item_detail = ItemDetail::findOne($this->item_detail_id);
            if($item_detail){
                $item = Item::findOne($item_detail->item_id);
                if($item){
                    $price = $item_detail->getBasePrice();
                    $tax =     $this->tax->tax_val1;
                    $discount = $price * $tax/100;
                }
            }
            return number_format($discount,2);

        }

    public function getIgstAmt(){
            $discount = 0;
            $item_detail = ItemDetail::findOne($this->item_detail_id);
            if($item_detail){
                $item = Item::findOne($item_detail->item_id);
                if($item){
                    $price = $item_detail->getBasePrice();
                    $tax =     $this->tax->tax_val4;
                    $discount = $price * $tax/100;
                }
            }
            return number_format($discount,2);

        }

    public function getSgstAmt(){
            $discount = 0;
            $item_detail = ItemDetail::findOne($this->item_detail_id);
            if($item_detail){
                $item = Item::findOne($item_detail->item_id);
                if($item){
                    $price = $item_detail->getBasePrice();
                    $tax =     $this->tax->tax_val2;
                    $discount = $price * $tax/100;
                }
            }
            return number_format($discount,2);

        }

    public function getCessAmt(){
            $discount = 0;
            $item_detail = ItemDetail::findOne($this->item_detail_id);
            if($item_detail){
                $item = Item::findOne($item_detail->item_id);
                if($item){
                    $price = $item_detail->getBasePrice();
                    $tax =     $this->tax->tax_val3;
                    $discount = $price * $tax/100;
                }
            }
            return number_format($discount,2);

        }

    public function getSaleRate(){
            $sale_rate = 0;
            $item_detail = ItemDetail::findOne($this->item_detail_id);
            if($item_detail){
                $item = Item::findOne($item_detail->item_id);
                $sale_rate = $item->sale_price;
            }
            return $sale_rate;

        }

    public function getCreateUser()
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
    }

    public function getItemDetail()
    {
        return $this->hasOne(ItemDetail::class, ['id' => 'item_detail_id']);
    }

    public function getTax()
    {
        return $this->hasOne(Tax::class, ['id' => 'tax_id']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
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
