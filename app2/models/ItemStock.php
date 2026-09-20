<?php
namespace app\models;

use app\components\Criteria;

use app\components\Ui;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/** Ported from protected/models/ItemStock.php (Yii 1). */
class ItemStock extends ActiveRecord
{
    // Yii 1 hands out column values as strings; the option helpers
    // compare them loosely and answer wrongly for an integer 0.
    use LegacyColumnTypes;

    public const TYPE_ADDED = 0;
    public const TYPE_SUBSTRACT = 1;

    public static function tableName()
    {
        return '{{%item_stock}}';
    }

    /**
     * Yii 1's isnetLessMin(): true when the stock of this item detail at this
     * outlet has fallen to or below the item's minimum.
     *
     * The Yii 1 version opens with four queries - an MrnDetail, a
     * PurchaseOrderDetail, and two over purchase bills - whose results it never
     * reads; the code that used them is commented out below the return. They
     * are left out here rather than reproduced: they have no side effects and
     * no effect on the answer, and they run on every line of every order.
     */
    public function isnetLessMin()
    {
        $item = Item::findOne($this->item_id);

        $total = 0;
        $stocks = ItemStock::find()
            ->where([
                'item_detail_id' => $this->item_detail_id,
                'item_id' => $this->item_id,
                'outlet_id' => $this->outlet_id,
            ])
            ->orderBy(['id' => SORT_ASC])
            ->all();
        foreach ($stocks as $stock) {
            $total = $total + $stock->balance_qty;
        }

        return !($total > $item->min_qty);
    }

    /**
     * Yii 1's createMrs(): raises or tops up a pending requisition for this
     * item, at the first outlet, with the item's last vendor.
     *
     * Two things worth knowing. It overwrites $this->outlet_id with the first
     * outlet before doing anything, so the requisition is always raised against
     * that outlet whatever outlet the stock row belongs to. And the tax lookup
     * reads Item::findOne($this->item_detail_id) - the *Item* table, keyed by
     * an item *detail* id - so it resolves to an unrelated item whenever those
     * ids happen to collide, and to nothing otherwise. Both reproduced.
     */
    public function createMrs()
    {
        $organization = Organization::find()->orderBy(['id' => SORT_ASC])->one();
        $outlet = Outlet::find()->orderBy(['id' => SORT_ASC])->one();
        if ($outlet) {
            $this->outlet_id = $outlet->id;
        }
        $item = Item::findOne($this->item_id);

        $vendorId = null;
        $vendor = ItemVendor::find()
            ->where('item_detail_id = :i', [':i' => $item->id])
            ->orderBy(['id' => SORT_DESC])
            ->one();
        if ($vendor) {
            $vendorId = $vendor->vendor_id;
        }

        // Item, keyed by an item *detail* id - as in Yii 1. tbl_item has no
        // tax_id column at all, so this reads a property that does not exist.
        // Yii 1 answers null for that rather than throwing, and the branch
        // below therefore finds no tax; Yii 2 would raise "Getting unknown
        // property". hasAttribute() reproduces the Yii 1 answer explicitly.
        $misreadItem = Item::findOne($this->item_detail_id);
        if ($misreadItem) {
            $taxId = $misreadItem->hasAttribute('tax_id') ? $misreadItem->tax_id : null;
            $tax = Tax::findOne($taxId);
        } else {
            $tax = Tax::findOne($this->tax_id);
            $taxId = $this->tax_id;
        }

        if ($vendorId === null) {
            return true;
        }

        $mrs = Mrs::find()
            ->where(['status' => Mrs::STATUS_PENDING, 'vendor_id' => $vendorId, 'outlet_id' => $this->outlet_id])
            ->orderBy(['id' => SORT_ASC])
            ->one();

        $reorderQty = $item->reorder_qty != '' ? $item->reorder_qty : 10;
        $maxQty = $item->max_qty != '' ? $item->max_qty : 10;
        $minQty = $item->min_qty != '' ? $item->min_qty : 10;

        $updated = true;
        if ($mrs === null) {
            $updated = false;
            $mrs = new Mrs();
        }

        $mrs->code = 'ddd';
        $mrs->mrs_date = date('Y-m-d');
        $mrs->mrs_req_date = date('Y-m-d');
        $mrs->outlet_id = $this->outlet_id;
        $mrs->vendor_id = $vendorId;
        $mrs->organization_id = $organization->id;

        if (!$mrs->save()) {
            return true;
        }

        $vendorRow = Vendor::findOne($mrs->vendor_id);
        Notification::AddNotification(
            $mrs->id,
            $updated ? 'MRS is updated' : 'A new MRS is added',
            Notification::TYPE_MRS,
            $vendorRow->create_user_id
        );

        $itemDetail = ItemDetail::findOne($this->item_detail_id);
        $mrsDetail = MrsDetail::find()
            ->where(['item_detail_id' => $this->item_detail_id, 'mrs_id' => $mrs->id])
            ->orderBy(['id' => SORT_ASC])
            ->one();
        if ($mrsDetail === null) {
            $mrsDetail = new MrsDetail();
        }

        $mrsDetail->price = $item->purchase_price;
        $mrsDetail->req_qty = $maxQty;
        $mrsDetail->approved_qty = $reorderQty;
        $mrsDetail->min_qty = $minQty;

        if ($tax) {
            $mrsDetail->cgst_per = $tax->tax_val1;
            $mrsDetail->sgst_per = $tax->tax_val2;
            $mrsDetail->cess_per = $tax->tax_val3;
            $mrsDetail->igst_per = $tax->tax_val4;
            $mrsDetail->cgst_amt = ($reorderQty * $mrsDetail->price) * ($tax->tax_val1 / 100);
            $mrsDetail->sgst_amt = ($reorderQty * $mrsDetail->price) * ($tax->tax_val2 / 100);
            $mrsDetail->cess_amt = ($reorderQty * $mrsDetail->price) * ($tax->tax_val3 / 100);
            $mrsDetail->igst_amt = ($reorderQty * $mrsDetail->price) * ($tax->tax_val4 / 100);
            $mrsDetail->tax_id = $tax->id;
        }

        $mrsDetail->item_detail_id = $this->item_detail_id;
        $mrsDetail->item_id = $this->item_id;
        $mrsDetail->outlet_id = $mrs->outlet_id;
        $mrsDetail->mrp = $itemDetail->getItemDetailMrp();
        $mrsDetail->sale_rate = $itemDetail->getItemDetailSaleRate();
        $mrsDetail->mrs_id = $mrs->id;

        if ($mrsDetail->getGstTrue($mrs->id) == true) {
            $mrsDetail->amount = ($reorderQty * $mrsDetail->price)
                + $mrsDetail->cgst_amt + $mrsDetail->sgst_amt + $mrsDetail->cess_amt;
            $calculatedGst = ($mrsDetail->price * $mrsDetail->cgst_per) / 100
                + ($mrsDetail->price * $mrsDetail->sgst_per) / 100
                + ($mrsDetail->price * $mrsDetail->cess_per) / 100;
        } else {
            $mrsDetail->amount = ($reorderQty * $mrsDetail->price) + $mrsDetail->igst_amt;
            $calculatedGst = ($mrsDetail->price * $mrsDetail->igst_per) / 100;
        }

        if ($mrsDetail->price != '0.00' && $mrsDetail->price !== null) {
            $mrsDetail->margin = ($mrsDetail->mrp - ($mrsDetail->price + $calculatedGst))
                * 100 / ($mrsDetail->price + $calculatedGst);
        }

        $mrsDetail->save();

        return true;
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'ItemStock' : 'ItemStocks';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'batch_number';
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
        $value = $this->hasAttribute('batch_number') ? $this->batch_number : null;

        return $value === null ? '' : (string) $value;
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
		$list = ["Draft","Published","Archive"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public static function getTypeOptions($id = null)
    {
		$list = ["Add","Substract"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'item_detail_id' => 'Bar Code',
            'item_id' => 'Item',
            'batch_number' => 'Batch Number',
            'purchase_qty' => 'Qty',
            'base_price' => 'Base Price',
            'mrp' => 'Mrp',
            'status' => 'Status',
            'type_id' => 'Type',
            'create_time' => 'Create Time',
            'create_user_id' => 'Create User Id',
            'tax_id' => 'Tax Id',
            'updated_by' => 'Updated By',
            'createUser' => 'User',
            'itemDetail' => 'Bar Code',
            'tax' => 'Tax',
            'updatedBy' => 'User',
        ];
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
            [['item_id', 'batch_number', 'purchase_qty', 'create_time', 'create_user_id'], 'required'],
            [['item_detail_id', 'item_id', 'status', 'type_id', 'create_user_id', 'tax_id', 'updated_by'], 'integer'],
            [['base_price', 'mrp'], 'number'],
            [['is_company_batch_no'], 'safe'],
            [['outlet_id', 'vendor_id'], 'number'],
            [['batch_number'], 'string', 'max' => 255],
            [['item_detail_id', 'base_price', 'mrp', 'status', 'type_id', 'tax_id', 'updated_by'], 'default', 'value' => null],
            [['id', 'item_detail_id', 'item_id', 'batch_number', 'purchase_qty', 'balance_qty base_price', 'mrp', 'status', 'type_id', 'create_time', 'create_user_id', 'tax_id', 'updated_by'], 'safe', 'on' => 'search'],
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

        foreach ([['id', 'id'], ['item_detail_id', 'item_detail_id'], ['item_id', 'item_id'], ['qty', 'qty'], ['base_price', 'base_price'], ['mrp', 'mrp'], ['status', 'status'], ['type_id', 'type_id'], ['create_user_id', 'create_user_id'], ['tax_id', 'tax_id'], ['updated_by', 'updated_by']] as [$col, $attr]) {
            Criteria::compare($query, $col, $this->$attr);
        }
        foreach ([['batch_number', 'batch_number'], ['create_time', 'create_time']] as [$col, $attr]) {
            Criteria::compare($query, $col, $this->$attr, true);
        }

        return $provider;
    }

    public function createB2bMrs(){
            $organization = Organization::find()->orderBy('id ASC')->one();
            $outlet =  Outlet::find()->orderBy('id ASC')->one();
            if($outlet){
            $this->outlet_id = $outlet->id;
            }
            $item = Item::findOne($this->item_id);

            $vendor_id = null;
            /* if($this->vendor_id != 0){
            $vendor_id =  $this->vendor_id;
            }else{
            if($item != null){ */
                    $query = ItemVendor::find();
                    $query->orderBy(['id' => SORT_DESC]);
                    $query->andWhere('item_detail_id ='.$item->id);
                    $vendor =  $query->one();
                    if($vendor){
                        $vendor_id = $vendor->vendor_id;
                    }
                    $tax = Tax::findOne($this->tax_id);
                    $tax_id = $this->tax_id;
                    $itemdetail = Item::findOne($this->item_detail_id);

                    if($itemdetail){
                        $tax_id = $itemdetail->tax_id;
                        $tax = Tax::findOne($tax_id);
                    }else{
                        $tax = Tax::findOne($this->tax_id);
                        $tax_id = $this->tax_id;
                    }


                /* }
            } */

            Yii::warning( var_export( $vendor_id , true), '$mrs_vendor_id');
        if($vendor_id != null){
            $mrs = Mrs::find()->where(['status'=>Mrs::STATUS_PENDING,'vendor_id'=>$vendor_id,
                    'outlet_id'=>$this->outlet_id
            ])->orderBy(['id' => SORT_ASC])->one();
            Yii::warning( var_export( $mrs , true), '$mrs_id');
            // $criteria = new CDbCriteria();
            // $criteria->addCondition('item_id ='.$this->item_id);
            // $criteria->order = 'id desc';
            // $itemstock = ItemStock::model()->find(;
            // if($itemstock){
                // $reorder_qty = 10/100*$itemstock;
            // }else{
                // $reorder_qty = $item->reorder_qty;
            // }
            /* if(($item->min_qty != '') && ($item->max_qty != '')){
                $reorder_qty = ($item->max_qty) - ($item->min_qty);
            }else{
            $reorder_qty = 10;
            } */
            if($item->reorder_qty != ''){
                //$reorder_qty = $item->getReorderQty();
                $reorder_qty = $item->reorder_qty;
            }else{
                $reorder_qty = 10;
            }
            if($item->max_qty != ''){
                $max_qty = $item->max_qty;
                //$max_qty = $item->getMaximumQty();
            }else{
                $max_qty = 10;
            }
            if($item->min_qty != ''){
                $min_qty = $item->min_qty;
                //$min_qty = $item->getMinimumQty();
            }else{
                $min_qty = 10;
            }
            $updated = true;
            if($mrs == null){
                $updated = false;
                $mrs = new Mrs();
            }


            $mrs->code = 'ddd';
            $mrs->mrs_date = date('Y-m-d');
            $mrs->mrs_req_date = date('Y-m-d');
            $mrs->outlet_id = $this->outlet_id;

            $mrs->vendor_id = $vendor_id;

            Yii::warning( var_export( $mrs->vendor_id , true), '$mrs->vendor_id');
            //$mrs->tax_id = $this->tax_id;

            $mrs->organization_id = $organization->id;
            if($mrs->save()){
                $vendor = Vendor::findOne($mrs->vendor_id);

                if($updated){
                $msg = 'MRS is updated';
                }else{
                $msg = 'A new MRS is added';
                }
                $to_id = $vendor->create_user_id;
                $type = Notification::TYPE_MRS;
                $model_id = $mrs->id;
                Notification::AddNotification($model_id,$msg,$type,$to_id);
                $itemdetail = ItemDetail::findOne( $this->item_detail_id );
                $mrsdetail = MrsDetail::find()->where(['item_detail_id'=>$this->item_detail_id,
                        'mrs_id'=>$mrs->id
                ])->orderBy(['id' => SORT_ASC])->one();
                if($mrsdetail == null){
                    $mrsdetail = new MrsDetail();
                }
                $mrsdetail->price = $item->purchase_price;
                $mrsdetail->req_qty = $max_qty;
                $mrsdetail->approved_qty = $reorder_qty;
                $mrsdetail->min_qty =$min_qty;
                if($tax){
                    $mrsdetail->cgst_per = $tax->tax_val1;
                    $mrsdetail->sgst_per = $tax->tax_val2;
                    $mrsdetail->cess_per = $tax->tax_val3;
                    $mrsdetail->igst_per = $tax->tax_val4;
                    $mrsdetail->cgst_amt = ($reorder_qty * $mrsdetail->price)*($tax->tax_val1/100);
                    $mrsdetail->sgst_amt = ($reorder_qty * $mrsdetail->price)*($tax->tax_val2/100);
                    $mrsdetail->cess_amt = ($reorder_qty * $mrsdetail->price)*($tax->tax_val3/100);
                    $mrsdetail->igst_amt = ($reorder_qty * $mrsdetail->price)*($tax->tax_val4/100);
                    $mrsdetail->tax_id = $tax->id;
                }

                $mrsdetail->item_detail_id = $this->item_detail_id;
                $mrsdetail->item_id = $this->item_id;
                $mrsdetail->outlet_id = $mrs->outlet_id;
                $mrsdetail->mrp = $itemdetail->getItemDetailMrp();
                $mrsdetail->sale_rate = $itemdetail->getItemDetailSaleRate();
                $mrsdetail->mrs_id = $mrs->id;
                if($mrsdetail->getGSTTrue($mrs->id) == true){
                $mrsdetail->amount =($reorder_qty*($mrsdetail->price))+($mrsdetail->cgst_amt)+($mrsdetail->sgst_amt)+($mrsdetail->cess_amt);
                 $price_cgst = ($mrsdetail->price  * $mrsdetail->cgst_per)/100;
                 $price_sgst = ($mrsdetail->price  * $mrsdetail->sgst_per)/100;
                 $price_cess = ($mrsdetail->price  * $mrsdetail->cess_per)/100;
                 $calgst = $price_cgst+$price_sgst +$price_cess;
                }else{
                    $mrsdetail->amount =($reorder_qty*$mrsdetail->price)+($mrsdetail->igst_amt);
                    $price_igst = ($mrsdetail->price  * $mrsdetail->igst_per)/100;
                    $calgst = $price_igst;
                }
                if($mrsdetail->price != '0.00' && $mrsdetail->price != null){
                $margin = (($mrsdetail->mrp)-($mrsdetail->price + $calgst))*100/($mrsdetail->price + $calgst);
                $mrsdetail->margin = $margin;
                }

                if($mrsdetail->save()){

                }else{
                    print_r($mrsdetail->getErrors());exit;
                }

            }else{
                print_r($mrs->getErrors());exit;
            }
        }

            return true;
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
