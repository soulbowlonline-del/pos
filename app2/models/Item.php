<?php
namespace app\models;

use app\components\Ui;

use app\components\Criteria;

use yii\data\ActiveDataProvider;

use Yii;

use app\components\LegacyActiveRecord as ActiveRecord;

/** Ported from protected/models/Item.php (Yii 1). */
class Item extends ActiveRecord
{
    // Yii 1 hands out column values as strings; the option helpers
    // compare them loosely and answer wrongly for an integer 0.
    use LegacyColumnTypes;

    // Declared on the Yii 1 model and not columns: the forms post
    // to these and the actions assign them.
    public $tax_id;
    public $remaining_quan;

    public $outlet_id;
    public $name;
    public $bar_code;
    public $company_bar_code;
    public $vendor_id;
    public $to_user_id;
    public $qty;
    public $end_date;
    public $start_date;
    public const STATUS_INACTIVE = 1;
    public const STATUS_ACTIVE = 0;

    public static function tableName()
    {
        return '{{%item}}';
    }

    public static function getMeasurementTypeOptions($id = null)
    {
		$list = [
				"PCS-PIECES",
				"Box",
				"Case",
				"KGS-KILOGRAMS",
				"ML",
				"NOS",
				"PCS",
				"PETI",
				"TIN" 
		]
		;
		if ($id === null || $id === '')
			return $list;
		if (is_numeric ( $id ))
			return $list [$id];
		return $id;
    }

    public function getCategory()
    {
        return $this->hasOne(ItemCategory::class, ['id' => 'category_id']);
    }

    public function getSubcategory()
    {
        return $this->hasOne(ItemCategory::class, ['id' => 'sub_category_id']);
    }

    public function getCompany()
    {
        return $this->hasOne(ItemCompany::class, ['id' => 'company_id']);
    }

    /**
     * Yii 1's getItemBarcodes(): despite the plural, this returns the bar code
     * of the item's first active detail row, or '' when it has none.
     */
    public function getItemBarcodes()
    {
        $detail = ItemDetail::find()
            ->where(['item_id' => $this->id, 'status' => ItemDetail::STATUS_ACTIVE])
            ->orderBy(['id' => SORT_ASC])
            ->one();

        return $detail ? $detail->bar_code : '';
    }

    /**
     * Yii 1's getTotalRemainingQuantity(): positive stock movements less the
     * absolute value of the negative ones, to three decimal places via bcsub.
     * Rows with no item_detail_id are excluded. Two queries rather than a
     * single SUM because bcsub over the PHP-side accumulation is what produces
     * the string the API returns.
     */
    public function getTotalRemainingQuantity()
    {
        $add = '0.000';
        $sub = '0.000';

        $positive = ItemStock::find()
            ->where(['item_id' => $this->id])
            ->andWhere('balance_qty > 0.000')
            ->andWhere('item_detail_id IS NOT NULL')
            ->orderBy(['id' => SORT_ASC])
            ->all();
        foreach ($positive as $stock) {
            $add = $add + $stock->balance_qty;
        }

        $negative = ItemStock::find()
            ->where(['item_id' => $this->id])
            ->andWhere('balance_qty < 0.000')
            ->andWhere('item_detail_id IS NOT NULL')
            ->orderBy(['id' => SORT_ASC])
            ->all();
        foreach ($negative as $stock) {
            $sub = $sub + abs($stock->balance_qty);
        }

        return bcsub((string)$add, (string)$sub, 3);
    }

    /**
     * Yii 1 declares this on Item with the foreign key 'item_detail_id', so it
     * matches item_vendor rows whose item_detail_id equals this *item's* id.
     * That looks like a mistake in the relation - the column holds item detail
     * ids elsewhere - but item/barcode reads it, so it is reproduced as
     * declared rather than corrected. Ordered by id; Yii 1 leaves it unordered
     * and the caller takes [0].
     */
    public function getItemVendors()
    {
        return $this->hasMany(ItemVendor::class, ['item_detail_id' => 'id'])
            ->orderBy(['id' => SORT_ASC]);
    }

    /**
     * Yii 1's getOutletTotalRemainingQuantity(): the balance of one item detail
     * at one outlet, summed in PHP rather than by the database - which matters,
     * because it starts at the integer 0 and not the string '0.000', so the
     * result is a float where getTotalRemainingQuantity() returns a bcsub
     * string.
     */
    public function getOutletTotalRemainingQuantity($itemDetailId, $outletId)
    {
        $remaining = 0;
        $stocks = ItemStock::find()
            ->where(['item_id' => $this->id, 'item_detail_id' => $itemDetailId, 'outlet_id' => $outletId])
            ->orderBy(['id' => SORT_ASC])
            ->all();
        foreach ($stocks as $stock) {
            $remaining += $stock->balance_qty;
        }
        return $remaining;
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'Item' : 'Items';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'title';
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
        $value = $this->hasAttribute('title') ? $this->title : null;

        return $value === null ? '' : (string) $value;
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
        return null;
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
				"Inactive" 
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
				"Finished",
				"Kot",
				"combo",
				'Semi finished',
				'Material' 
		];
		if ($id === null || $id === '')
			return $list;
		if (is_numeric ( $id ))
			return $list [$id];
		return $id;
    }

    public static function getTypeKeyOptions($id = null)
    {
		$list = [
				"Finished",
				"Kot",
				"combo",
				'Semi finished',
				'Material' 
		];
		
		foreach ( $list as $key => $val ) {
			if ($val == $value) {
				return $key;
			}
		}
		
		return '0';
    }

    public static function getStatusKeyOptions($id = null)
    {
		$list = [
				"Active",
				"Inactive" 
		];
		
		foreach ( $list as $key => $val ) {
			if ($val == $value) {
				return $key;
			}
		}
		
		return '0';
    }

    public static function getStockOptions($id = null)
    {
		$list = [
				"Stockable",
				"Non Stockable" 
		];
		if ($id === null || $id === '')
			return $list;
		if (is_numeric ( $id ))
			return $list [$id];
		return $id;
    }

    public static function getMovementTypeOptions($id = null)
    {
		$list = [
				"Both",
				"Sale Only",
				"Purchase Only" 
		]
		;
		if ($id === null || $id === '')
			return $list;
		if (is_numeric ( $id ))
			return $list [$id];
		return $id;
    }

    public function getCreateUser()
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
    }

    public function getSubCompany()
    {
        return $this->hasOne(ItemCompanyCategory::class, ['id' => 'sub_company_id']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }

    public function getItemDetails()
    {
        return $this->hasMany(ItemDetail::class, ['item_id' => 'id']);
    }

    public function getItemStocks()
    {
        return $this->hasMany(ItemStock::class, ['item_id' => 'id']);
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
            'short_name' => 'Short Name',
            'title' => 'Title',
            'item_code' => 'Product Code',
            'description' => 'Bill Description',
            'image_file' => 'Image File',
            'item_type' => 'Product Type',
            'hsn_code' => 'HSN Code',
            'status' => 'Status',
            'type_id' => 'Type',
            'is_tax' => 'Is Tax',
            'min_qty' => 'Minimum Quantity',
            'max_qty' => 'Maximum Quantity',
            'reorder_qty' => 'Reorder Quantity',
            'is_discount' => 'Is Discount Applicable',
            'is_coupon' => 'Is Coupon Applicable',
            'mrp' => 'MRP',
            'sale_price' => 'Sale Price',
            'weight' => 'Weight',
            'purchase_price' => 'Purchase Price',
            'whole_sale' => 'Whole Sale',
            'category_id' => 'Category',
            'sub_category_id' => 'Sub Category',
            'sub_company_id' => 'Sub Company',
            'tax_id' => 'Tax',
            'company_id' => 'Company',
            'create_time' => 'Create Time',
            'remaining_quan' => 'Remaining Quantity',
            'create_user_id' => 'User',
            'updated_by' => 'User',
            'category' => 'ItemCategory',
            'company' => 'ItemCompany',
            'createUser' => 'User',
            'subCompany' => 'ItemCompanyCategory',
            'updatedBy' => 'User',
            'itemDetails' => 'ItemDetails',
        ];
    }

    public function getOpeningQuantity(){
            $qty = 0;
            if(Yii::$app->session ['stock_start_date'] == date('Y-m-d')){
                $query = StockLog::find();
                $query->andWhere('item_id ='.$this->id);
                $query->andWhere('date(create_time) <"'.date('Y-m-d').'"');
                $query->orderBy(['id' => SORT_DESC]);
                $stock = $query->one();
                if($stock){
                    $qty = $stock->current_qty;
                }
            }else{
                $query = StockLog::find();
                $query->andWhere('item_id ='.$this->id);
                // With no start date in the session this built
                // `date(create_time) < ""`. MySQL 5.7 treated that as a warning and
                // matched nothing; MySQL 8 rejects it outright (error 1525,
                // Incorrect DATE value). Returning 0 without running the query is
                // the same answer 5.7 gave, without the error.
                if (Yii::$app->session ['stock_start_date'] === null
                        || Yii::$app->session ['stock_start_date'] === '') {
                    return $qty;
                }
                $query->andWhere('date(create_time) <"'.Yii::$app->session ['stock_start_date'].'"');
                $query->orderBy(['id' => SORT_DESC]);
                $stock = $query->one();
                if($stock){
                    $qty = $stock->current_qty;
                }

            }
            if($qty == ''){
                $qty = '0.000';
            }

            return $qty;
        }

    public function getAdjustmentQuantity(){
            $qty = 0;

                $query = StockAdjustLog::find();
                $query->andWhere('item_id ='.$this->id);
                $query->andWhere(['between', 'date', Yii::$app->session ['stock_start_date'], Yii::$app->session ['stock_end_date']]);
                $query->select('sum(adjusted) as adjusted');
                $stock = $query->one();
                if($stock){
                    $qty = $stock->adjusted;
                }
                if($qty == ''){
                    $qty = '0.000';
                }

            return $qty;
        }

    public function getAddedQuantity(){
            $qty = 0;

            $query = PurchaseBillDetail::find();
            $query->andWhere('item_id ='.$this->id);
            $query->andWhere(['between', 'date(create_time)', Yii::$app->session ['stock_start_date'], Yii::$app->session ['stock_end_date']]);
            $query->select('sum(approved_qty) as approved_qty');
            $stock = $query->one();
            if($stock){
                $qty = $stock->approved_qty;
            }
            if($qty == ''){
                $qty = '0.000';
            }

            return $qty;
        }

    public function getReturnQuantity(){
            $qty = 0;

            $query = ItemReturnItem::find();
            $query->andWhere('item_id ='.$this->id);
            $query->andWhere(['between', 'date(create_time)', Yii::$app->session ['stock_start_date'], Yii::$app->session ['stock_end_date']]);
            $query->select('sum(qty) as qty');
            $stock = $query->one();
            if($stock){
                $qty = $stock->qty;
            }
            if($qty == ''){
                $qty = '0.000';
            }

            return $qty;
        }

    public function getSoldQuantity(){
            $qty = 0;

            $query = OrderItem::find();
            $query->andWhere('item_id ='.$this->id);
            $query->andWhere(['between', 'date(create_time)', Yii::$app->session ['stock_start_date'], Yii::$app->session ['stock_end_date']]);
            $query->select('sum(qty) as qty');
            $stock = $query->one();
            if($stock){
                $qty = $stock->qty;
            }
            if($qty == ''){
                $qty = '0.000';
            }

            return $qty;
        }

    public function getRefundQuantity(){
            $qty = 0;

            $query = OrderRefundItem::find();
            $query->andWhere('item_id ='.$this->id);
            $query->andWhere(['between', 'date(create_time)', Yii::$app->session ['stock_start_date'], Yii::$app->session ['stock_end_date']]);
            $query->select('sum(qty) as qty');
            $stock = $query->one();
            if($stock){
                $qty = $stock->qty;
            }
            if($qty == ''){
                $qty = '0.000';
            }

            return $qty;
        }

    public function getExpiryQuantity(){
            $qty = 0;

            $query = ItemExpireItem::find();
            $query->andWhere('item_id ='.$this->id);
            $query->andWhere(['between', 'date(create_time)', Yii::$app->session ['stock_start_date'], Yii::$app->session ['stock_end_date']]);
            $query->select('sum(qty) as qty');
            $stock = $query->one();
            if($stock){
                $qty = $stock->qty;
            }
            if($qty == ''){
                $qty = '0.000';
            }

            return $qty;
        }

    public function getStockRemainingQuantity(){
            $qty = 0;

            $query = StockLog::find();
            $query->andWhere('item_id ='.$this->id);
            $query->andWhere('date(create_time) <="'.Yii::$app->session ['stock_end_date'].'"');
            $query->orderBy(['id' => SORT_DESC]);
            $stock = $query->one();
            Yii::warning( var_export( $stock , true), '$stock');
            if($stock){
                $qty = $stock->current_qty;
            }
            if($qty == ''){
                $qty = '0.000';
            }

            return $qty;
        }

    public function toonlineArray() {
            $model = $this;
            $json_entry = null;
            if ($model) {

                $batch_no =  [];
                $default_img = 'default.png';
                $json_entry = [];
                $json_entry ['Code'] = isset($model->item_code)?$model->item_code:"";
                $json_entry ['Name'] = isset($model->title)?$model->title:"";
                $json_entry ['Short_x0020_Name'] = isset($model->short_name)?$model->short_name:"";
                $json_entry ['Barcode'] = $model->getItemBarcodes();
                $json_entry ['Tax'] = $model->getMainItemTax();
                $json_entry ['MRP'] = isset($model->mrp)?$model->mrp:"";

                $json_entry ['PRICE'] = isset($model->sale_price)?$model->sale_price:"";
                $json_entry ['OPStock'] = $model->getTotalRemainingQuantity();
                $json_entry ['Pur_x0020_Price'] = isset($model->purchase_price)?$model->purchase_price:"";
                $json_entry ['Pur_x0020_Value'] = isset($model->purchase_price)?$model->purchase_price:"";
                $json_entry ['Weight'] = isset($model->weight)?$model->weight:"";
                $json_entry ['Department'] =isset($model->category)?$model->category->title:"";
                $json_entry ['Company'] = isset($model->company)?$model->company->title:"";
                $subcat = isset($model->subcategory)?$model->subcategory->title:"";
                if($subcat == '<ADD NEW>'){
                    $subcat = '';
                }
                $json_entry ['Sub_x0020_Category'] = $subcat;
                $json_entry ['ProdEx1'] = '';
                $json_entry ['ProdEx2'] = '';
                $json_entry ['ProdEx3'] = '';
                $json_entry ['ProdEx4'] = '';
                $json_entry ['Active'] = $model->getStatusOptions($model->status);

            }
            return $json_entry;
        }

    public function getBarcodeList(){
            $list = [];
            $query = ItemDetail::find();
            $query->orderBy(['id' => SORT_ASC]);
            $query->andWhere('item_id ='.$this->id);
            $query->andWhere('status ='.ItemDetail::STATUS_ACTIVE);
            $item_details = $query->all();
            if($item_details){
                foreach($item_details as $item_detail){
                    $list[$item_detail->id] = $item_detail->bar_code;
                }
            }

            return $list;
        }

    public function getVendorNames(){
            $list = [];
            $query = ItemDetail::find();
            $query->orderBy(['id' => SORT_DESC]);
            $query->andWhere('item_id ='.$this->id);
            $item_details = $query->all();
            if($item_details){
                foreach($item_details as $item_detail){
                    $list[$item_detail->id] = $item_detail->bar_code;
                }
            }

            return $list;
        }

    public static function getItemMainBarcodes($id){
            $str = '';
            $query = ItemDetail::find();
            $query->orderBy(['id' => SORT_ASC]);
            $query->andWhere('item_id ='.$id);
            $item_detail = $query->one();
            if($item_detail){
                $str = $item_detail->bar_code;
            }
            return $str;
        }

    public static function getItemMainMargin($id){
                $str = '';
                $query = ItemDetail::find();
                $query->orderBy(['id' => SORT_ASC]);
                $query->andWhere('item_id ='.$id);
                $item_detail = $query->one();
                $item = Item::findOne($id);
                if($item_detail && $item && $item->mrp > 0 && $item->purchase_price > 0){
                    $price = $item->purchase_price;
                    $tax =isset($item_detail->tax)? ($price * $item_detail->tax->tax_val1/100) + ($price * $item_detail->tax->tax_val2/100) + ($price * $item_detail->tax->tax_val3/100) + ($price * $item_detail->tax->tax_val4/100) : 0;
                    $str = $item->purchase_price * ($item->mrp - ($price + $tax)) * 100 / ($price + $tax) / 100;
                }
                return $str;
            }

    public static function getItemMainGSTNewMRP($id, $tax) {
                $str = '';
                $item = Item::findOne($id);
                $margin = static::getItemMainMargin($id);
                if($item->purchase_price > 0){
                    $str = ($item->purchase_price + $margin) * (100 + $tax) / 100;
                }
                return $str;
            }

    public static function getItemMainGSTNewActualTax($id, $hsn_code) {
                $str = '';
                $item = Item::findOne($id);
                $margin = static::getItemMainMargin($id);
                if($item->purchase_price > 0){
                    $tax = 0;
                    $newTax = TblItemNewTax::findOne(['hsn_code'=>$hsn_code]);
                    if ($newTax ) {
                        return $newTax->tax;
                    }
                }
                return $str;
            }

    public static function getItemMainGSTNewActualMRPOld($id, $hsn_code) {
                $str = '';
                $item = Item::findOne($id);
                $margin = static::getItemMainMargin($id);
                if($item->purchase_price > 0){
                    $tax = 0;
                    $newTax = TblItemNewTax::findOne(['hsn_code'=>$hsn_code]);
                    if ($newTax ) {
                        $tax = $newTax->tax;
                        $str = ($item->purchase_price + $margin) * (100 + $tax) / 100;
                    }
                }
                return $str;
            }

    public static function getItemMainGSTNewActualMRP($id, $tax) {
                $str = '';
                $item = Item::findOne($id);
                $margin = static::getItemMainMargin($id);
                if($item->purchase_price > 0){

                    if ($tax >= 0 ) {
                        $str = ($item->purchase_price + $margin) * (100 + $tax) / 100;
                    }
                }
                return $str;
            }

    public function getMainItemTax(){
            $str = '';

            $query = ItemDetail::find();
            $query->orderBy(['id' => SORT_ASC]);
            $query->andWhere('item_id ='.$this->id);
            $item_detail = $query->one();
            if($item_detail){
                $str = isset($item_detail->tax)?$item_detail->tax->title:"";
            }
            return $str;
        }

    public static function getStaticMainItemTax($id){
            $str = '';

            $query = ItemDetail::find();
            $query->orderBy(['id' => SORT_ASC]);
            $query->andWhere('item_id ='.$id);
            $item_detail = $query->one();
            if($item_detail){
                $str = isset($item_detail->tax)?$item_detail->tax->title:"";
            }
            return $str;
        }

    public static function getMainTotalRemainingQuantity($id)
        {

            $remaining_quantity = 0;
            $query = ItemStock::find();
            $query->andWhere('item_id ='.$id);
            $query->andWhere('item_detail_id IS NOT NULL');
            $stocks = $query->all();

            if(!empty($stocks))
            {
                foreach ($stocks as $stock)
                {
                    $remaining_quantity += $stock->balance_qty;
                }
            }

            return $remaining_quantity;
        }

    public function getTaxList(){
            $list = [];
            $query = Tax::find();
           $query->andWhere('status ='.Tax::STATUS_ACTIVE);
            $taxes = $query->all();
            if($taxes){
                foreach($taxes as $tax){
                    $list[$tax->id] = $tax->title;
                }
            }
            return $list;
        }

    public function getParentCategorys(){
            $list = [];
            $query = ItemCategory::find();
            $query->andWhere('parent_id IS  NULL');
            $query->orderBy(['title' => SORT_ASC]);
            $query->andWhere('status ='.ItemCategory::STATUS_ACTIVE);
            $cats = $query->all();
            if($cats){
                foreach($cats as $cat){
                    $list[$cat->id] = $cat->title;
                }
            }
            return $list;
        }

    public function getSubCategorys(){
            $list = [];
            $query = ItemCategory::find();
            $query->andWhere('parent_id IS  NOT NULL');
            $query->orderBy(['title' => SORT_ASC]);
            $query->andWhere('status ='.ItemCategory::STATUS_ACTIVE);
            $cats = $query->all();
            if($cats){
                foreach($cats as $cat){
                    $list[$cat->id] = $cat->title;
                }
            }
            return $list;
        }

    public function getParentCompanys(){
            $list = [];
            $query = ItemCompany::find();
            $query->andWhere('parent_id IS  NULL');
            $query->orderBy(['title' => SORT_ASC]);
            $query->andWhere('status ='.ItemCompany::STATUS_ACTIVE);
            $cats = $query->all();
            if($cats){
                foreach($cats as $cat){
                    $list[$cat->id] = $cat->title;
                }
            }
            return $list;
        }

    public static function getAllActiveVendors() {

            $outlet_arr    = [];
            $query = Vendor::find();
            $query->orderBy(['name' => SORT_ASC]);
            $query->andWhere('status =' . Vendor::STATUS_ACTIVE);
            $outlets = $query->all();
            if ($outlets != null) {
                foreach ( $outlets as $outlet ) {
                    $outlet_arr [$outlet->id] = $outlet->name;
                }
            }

            return $outlet_arr;
        }

    public static function getAllOutlets() {

            $outlet_arr    = [];
            $query = Outlet::find();
            $query->orderBy(['title' => SORT_ASC]);
            $query->andWhere('status =' . Outlet::STATUS_ACTIVE);
            $outlets = $query->all();
            if ($outlets != null) {
                foreach ( $outlets as $outlet ) {
                    $outlet_arr [$outlet->id] = $outlet->title;
                }
            }

            return $outlet_arr;
        }

    public function getSelectedVendors(){
            $vendor_ids = [];
            $vendors = ItemVendor::findAll(['item_detail_id'=>$this->id]);
            if($vendors != null)
            {
                foreach($vendors as $vendor)
                {
                    $vendor_ids[] = $vendor->vendor_id;
                }
            }

            return $vendor_ids;
        }

    public static function RemoveVendors($id){
            ItemVendor::deleteAll(['item_detail_id'=>$id]);
            return true;
        }

    public static function getHsnCodeList(){
            $list = [];
            $items = Item::find()->all();
            if($items){
                foreach($items as $item){
                    $list[$item->id] = $item->hsn_code;
                }
            }
            return $list;
        }

    public static function getActiveItems() {

            $outlet_arr    = [];
            $query = Item::find();
            $query->orderBy(['title' => SORT_ASC]);
            $query->andWhere('status =' . Item::STATUS_ACTIVE);
            $items = $query->all();
            if ($items != null) {
                foreach ( $items as $item ) {
                    $outlet_arr [$item->id] = $item->title;
                }
            }

            return $outlet_arr;
        }

    public static function getActiveBarcodeItems() {

            $outlet_arr    = [];
            $query = ItemDetail::find();
            $query->limit(1000);
            $query->andWhere('status =' . Item::STATUS_ACTIVE);
            $items = $query->all();
            if ($items != null) {
                foreach ( $items as $item ) {
                    $outlet_arr [$item->id] = $item->item->title.'('.$item->bar_code.')';
                }
            }

            return $outlet_arr;
        }

    public static function getMainLatestVendorName($id){
            $name = '';
            $query = ItemVendor::find();
            $query->orderBy(['id' => SORT_DESC]);
            $query->limit(1);
            $query->andWhere('item_detail_id =' . $id);
            $itemvendor = $query->one();
            if($itemvendor){
                $vendor = Vendor::findOne($itemvendor->vendor_id);
                if($vendor){
                    $name = $vendor->name;
                }
            }
            return $name;
        }

    public function getLatestVendorName(){
            $name = '';
            $query = ItemVendor::find();
            $query->orderBy(['id' => SORT_DESC]);
            $query->limit(1);
            $query->andWhere('item_detail_id =' . $this->id);
            $itemvendor = $query->one();
            if($itemvendor){
                $vendor = Vendor::findOne($itemvendor->vendor_id);
                if($vendor){
                    $name = $vendor->name;
                }
            }
            return $name;
        }

    public function getStockQuantity(){

        }

    public function getItemAdjustedStock(){
            $qty = '';

            $query = MrsAdjust::find();
            Criteria::compare($query, 'status', MrsAdjust::STATUS_PENDING);
            Criteria::compare($query, 'item_id', $this->id);
            $query->orderBy(['id' => SORT_DESC]);
            $mrsadjust = $query->one();
            if($mrsadjust){
                $qty = $mrsadjust->qty;
            }
            return $qty;
        }

    public function getItemAdjustedType(){
            $type = '0';

            $query = MrsAdjust::find();
            Criteria::compare($query, 'status', MrsAdjust::STATUS_PENDING);
            Criteria::compare($query, 'item_id', $this->id);
            $query->orderBy(['id' => SORT_DESC]);
            $mrsadjust = $query->one();
            if($mrsadjust){
                $type = $mrsadjust->type_id;
            }
            return $type;
        }

    public function getCssClass()
        {
            $cssClass='';

            $purchase_qty = $this->getPurchaseQty();
            $per_purchase_qty = (($this->getPurchaseQty()) - (10/100) * ($this->getPurchaseQty()));
            $sale_qty = $this->getSaleQty();
            Yii::warning( var_export( $this , true), '$mrs');
             if($sale_qty < $per_purchase_qty){
                $cssClass='mrsred';
            }
            return $cssClass;
        }

    public function daysBetweenTwoDays($date1, $date2) {
            $date1 = strtotime($date1);
            $date2 = strtotime($date2);
            $datediff = $date1 - $date2;

            return round($datediff / (60 * 60 * 24));
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

    public function getPurchaseQty(){

            $qty = '0';
            $query = PurchaseBillDetail::find()->alias('t');
            $query->andWhere('t.item_id ='.$this->id);
            $query->select('sum(t.approved_qty) as approved_qty');
            $orderitem = $query->one();
            if($orderitem){
                $qty = $orderitem->approved_qty;
            }
            if($qty == ''){
                $qty = '0';
            }
            Yii::warning( var_export( $this->id , true), '$this->item_id');
            Yii::warning( var_export( $qty , true), '$purqty');
            return $qty;
        }

    public function getSaleQty(){
            $qty = '0';
            $query = OrderItem::find()->alias('t');
            $query->andWhere('t.item_id ='.$this->id);
            $query->select('sum(t.qty) as qty');
            $orderitem = $query->one();
            if($orderitem){
                $qty = $orderitem->qty;
            }
            if($qty == ''){
                $qty = '0';
            }
            Yii::warning( var_export( $this->id , true), '$this->item_id');
            Yii::warning( var_export( $qty , true), '$saleqty');
            return $qty;
        }

    public function getItemSaleQty($start_date,$end_date){
            $qty = '0';
            $query = OrderItem::find()->alias('t');
            $query->andWhere('t.item_id ='.$this->id);
            $query->andWhere(['between', 'create_date', $start_date, $end_date]);
            $query->select('sum(t.qty) as qty');
            $orderitem = $query->one();
            if($orderitem){
                $qty = $orderitem->qty;
            }
            if($qty == ''){
                $qty = '0';
            }
            Yii::warning( var_export( $this->id , true), '$this->item_id');
            Yii::warning( var_export( $qty , true), '$saleqty');
            return $qty;
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
        } else {
            // update_time: set on update when empty, as Yii 1 did.
            if ($this->hasAttribute('update_time') && !isset($this->update_time)) {
                $this->update_time = date('Y-m-d H:i:s');
            }
            if ($this->hasAttribute('updated_by') && !isset($this->updated_by)) {
                $this->updated_by = Yii::$app->user->id;
            }
        }

        return true;
    }

    public function rules()
    {
        return [
            [['short_name', 'title', 'create_time', 'create_user_id', 'mrp', 'sale_price'], 'required'],
            [['item_type', 'status', 'type_id', 'is_tax', 'is_discount', 'category_id', 'sub_company_id', 'company_id', 'create_user_id', 'updated_by'], 'integer'],
            [['title'], 'unique'],
            [['title', 'item_code'], 'string', 'max' => 255],
            [['short_name', 'tax_id', 'name', 'qty', 'to_user_id', 'sub_category_id', 'description', 'company_bar_code', 'tax_id', 'outlet_id', 'bar_code', 'image_file', 'min_qty', 'max_qty', 'reorder_qty', 'vendor_id', 'hsn_code', 'is_coupon', 'mrp', 'sale_price', 'weight', 'purchase_price', 'whole_sale', 'is_stockable', 'movement_type', 'opening_stock', 'unit', 'start_date', 'end_date', 'remaining_quan'], 'safe'],
            [['description', 'image_file', 'item_type', 'status', 'type_id', 'is_tax', 'is_discount', 'category_id', 'sub_company_id', 'company_id', 'updated_by'], 'default', 'value' => null],
            [['id', 'title', 'name', 'item_code', 'description', 'image_file', 'item_type', 'status', 'type_id', 'is_tax', 'is_discount', 'category_id', 'sub_company_id', 'company_id', 'create_time', 'create_user_id', 'updated_by', 'update_time', 'remaining_quan'], 'safe', 'on' => 'search'],
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
            // The page size Yii 1's search() asks its provider for, which
            // is not always the framework default.
            'pagination' => ['pageSize' => 100],
        ]);

        if (self::listingOrder()) {
            $query->orderBy(self::listingOrder());
        }

        $this->load($params, $this->formName());

        // What follows was in Yii 1's BaseItem::search() and not here. The
        // generated version kept the plain compares and dropped every
        // hand-written condition, so the item master's own search box did
        // nothing: a title or a barcode filtered nothing out and the grid
        // answered with the first hundred items either way.

        // Always applied, even when the box is empty - `LIKE '%'`, which is
        // every row whose title is not null. With `name` as well, both apply.
        $title = trim((string) $this->title);
        $query->andWhere(['like', 'title', $title . '%', false]);
        if (Yii::$app->session['item_name'] !== null
                && Yii::$app->session['item_name'] !== '') {
            $this->name = Yii::$app->session['item_name'];
        }
        if ($this->name !== null && $this->name !== '') {
            $query->andWhere(['like', 'title', trim((string) $this->name)]);
        }

        // A vendor sees only the items they supply.
        $user = Yii::$app->user->model;
        if ($user !== null && $user->role_id == 6) {
            $ids = [];
            $vendor = Vendor::findOne(['create_user_id' => $user->id]);
            if ($vendor) {
                foreach (ItemVendor::findAll(['vendor_id' => $vendor->id]) as $iv) {
                    $ids[] = $iv->item_detail_id;
                }
            }
            // An empty list matches nothing, as CDbCriteria::addInCondition
            // does - it writes 0=1 rather than dropping the condition.
            $query->andWhere(['id' => $ids]);
        }

        // The barcode box names an item detail; the grid lists items.
        if ($this->bar_code !== null && $this->bar_code !== '') {
            $detail = ItemDetail::findOne(['bar_code' => $this->bar_code]);
            if ($detail) {
                Criteria::compare($query, 'id', $detail->item_id);
            }
        }

        if ($this->tax_id !== null && $this->tax_id !== '') {
            $ids = ItemDetail::find()
                ->select('item_id')
                ->where(['tax_id' => $this->tax_id])
                ->groupBy('item_id')
                ->orderBy(['id' => SORT_DESC])
                ->column();
            $query->andWhere(['id' => $ids]);
        }

        if ($this->vendor_id !== null && $this->vendor_id !== ''
                && ($user === null || $user->role_id != 6)) {
            $ids = [];
            foreach (ItemVendor::findAll(['vendor_id' => $this->vendor_id]) as $iv) {
                $ids[] = $iv->item_detail_id;
            }
            // Yii 1 only adds the condition when the vendor has rows at all.
            if ($ids) {
                $query->andWhere(['id' => $ids]);
            }
        }

        foreach ([['id', 'id'], ['item_type', 'item_type'], ['status', 'status'], ['type_id', 'type_id'], ['mrp', 'mrp'], ['is_tax', 'is_tax'], ['is_discount', 'is_discount'], ['sale_price', 'sale_price'], ['purchase_price', 'purchase_price'], ['sub_category_id', 'sub_category_id'], ['opening_stock', 'opening_stock'], ['weight', 'weight'], ['sub_category_id', 'sub_category_id'], ['category_id', 'category_id'], ['sub_company_id', 'sub_company_id'], ['company_id', 'company_id'], ['create_user_id', 'create_user_id'], ['updated_by', 'updated_by']] as [$col, $attr]) {
            Criteria::compare($query, $col, $this->$attr);
        }
        foreach ([['short_name', 'short_name'], ['item_code', 'item_code'], ['description', 'description'], ['image_file', 'image_file'], ['hsn_code', 'hsn_code'], ['create_time', 'create_time']] as [$col, $attr]) {
            Criteria::compare($query, $col, $this->$attr, true);
        }

        return $provider;
    }

    public function getAscBarCodeTotalRemainingQuantity()
        {

            $remaining_quantity = 0;
            $query = ItemDetail::find();
            $query->orderBy(['id' => SORT_ASC]);
            $query->andWhere('status ='.ItemDetail::STATUS_ACTIVE);
            $query->andWhere('item_id ='.$this->id);
            $item_detail = $query->one();
            if($item_detail){
                $remaining_quantity = '0.000';
                $add_quantity = '0.000';
                $sub_quantity = '0.000';
                // ItemStock, and every row of it. Yii 1 writes
                //   $stocks = ItemStock::model()->findAll($criteria);
                // and an older version of the generator read the class and
                // the fetch off the *first* criteria in this method, which is
                // an ItemDetail lookup - so the query asked tbl_item_detail
                // for item_detail_id, a column of tbl_item_stock, and
                // item/adjustStock answered 500. The generator gets this
                // right now; this method predates it and the merge keeps the
                // methods a model already has.
                $query = ItemStock::find();
                $query->andWhere('item_detail_id ='.$item_detail->id);
                $query->orderBy(['id' => SORT_ASC]);
                $query->andWhere("balance_qty > 0.000");
                $query->andWhere('item_detail_id IS NOT NULL');
                $stocks = $query->all();

                if(!empty($stocks))
                {
                    foreach ($stocks as $stock)
                    {
                        $add_quantity = ($add_quantity) + ($stock->balance_qty);

                    }
                }
                $query = ItemStock::find();
                $query->andWhere('item_detail_id ='.$item_detail->id);
                $query->orderBy(['id' => SORT_ASC]);
                $query->andWhere("balance_qty < 0.000");
                $query->andWhere('item_detail_id IS NOT NULL');
                $stocks = $query->all();

                if(!empty($stocks))
                {
                    foreach ($stocks as $stock)
                    {
                        $sub_quantity = ($sub_quantity) + abs($stock->balance_qty);

                    }
                }
                $remaining_quantity = bcsub($add_quantity,$sub_quantity,3);

                /* if(!empty($item_detail))
                 {

                 $remaining_quantity += $item_detail->open_stock_qty;

                 } */
            }
            /* if($remaining_quantity < 0)
                {
                $remaining_quantity =0;
                } */
            return $remaining_quantity;
        }

    public function getBarCodeTotalRemainingQuantity()
        {

            $remaining_quantity = 0;
            $query = ItemDetail::find();
            $query->orderBy(['id' => SORT_DESC]);
            $query->andWhere('status ='.ItemDetail::STATUS_ACTIVE);
            $query->andWhere('item_id ='.$this->id);
            $item_detail = $query->one();
            if($item_detail){
                $remaining_quantity = '0.000';
                $add_quantity = '0.000';
                $sub_quantity = '0.000';
                // ItemStock, and every row of it. Yii 1 writes
                //   $stocks = ItemStock::model()->findAll($criteria);
                // and an older version of the generator read the class and
                // the fetch off the *first* criteria in this method, which is
                // an ItemDetail lookup - so the query asked tbl_item_detail
                // for item_detail_id, a column of tbl_item_stock, and
                // item/adjustStock answered 500. The generator gets this
                // right now; this method predates it and the merge keeps the
                // methods a model already has.
                $query = ItemStock::find();
                $query->andWhere('item_detail_id ='.$item_detail->id);
                $query->orderBy(['id' => SORT_ASC]);
                $query->andWhere("balance_qty > 0.000");
                $query->andWhere('item_detail_id IS NOT NULL');
                $stocks = $query->all();

                if(!empty($stocks))
                {
                    foreach ($stocks as $stock)
                    {
                        $add_quantity = ($add_quantity) + ($stock->balance_qty);

                    }
                }
                $query = ItemStock::find();
                $query->andWhere('item_detail_id ='.$item_detail->id);
                $query->orderBy(['id' => SORT_ASC]);
                $query->andWhere("balance_qty < 0.000");
                $query->andWhere('item_detail_id IS NOT NULL');
                $stocks = $query->all();

                if(!empty($stocks))
                {
                    foreach ($stocks as $stock)
                    {
                        $sub_quantity = ($sub_quantity) + abs($stock->balance_qty);

                    }
                }
                $remaining_quantity = bcsub($add_quantity,$sub_quantity,3);

            /* if(!empty($item_detail))
            {

                    $remaining_quantity += $item_detail->open_stock_qty;

            } */
            }
            /* if($remaining_quantity < 0)
            {
                $remaining_quantity =0;
            } */
            return $remaining_quantity;
        }

    public static function getAllVendors($id = null) {
            $user = Yii::$app->user->model;
            $vendor_arr = [];
            $exist = [];
            if($id != null){
                $query = ItemVendor::find();
                $query->andWhere('item_detail_id =' . $id);
                $itemvendors = $query->all();
                if($itemvendors){
                    foreach($itemvendors as $itemvendor){
                        $exist[] = $itemvendor->vendor_id;
                    }
                }
            }
            $query = Vendor::find();
            if($user->role_id == 1){
            $query->andWhere(['not in', 'id', $exist]);
            }else{
                $query->andWhere('create_user_id ='.$user->id);
            }
            $query->orderBy(['name' => SORT_ASC]);
            $query->andWhere('status =' . Vendor::STATUS_ACTIVE);
            $vendors = $query->all();
            if ($vendors != null) {
                foreach ( $vendors as $vendor ) {
                    $vendor_arr [$vendor->id] = $vendor->name;
                }
            }

            return $vendor_arr;
        }

    public function setAllNewValues($rows) {

            $output = 0;
            $count = count($rows);


            if ($count > 1) {

                $o = explode(',', $rows[0]);
                $arrays = array_flip($o);
                $set = true;
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $item = null;
                    for ($i = 1; $i < $count; $i++) {
                        $itemcat_values = explode(',', $rows[$i]);
                        if (isset($arrays['Title']) || isset($arrays['﻿"Title"']) || isset($arrays['���"Title"'])) {
                            $query = Item::find();
                            if (isset($arrays['Title'])) {
                                Criteria::compare($query, 'title', $itemcat_values[$arrays['Title']]);
                        } else if(isset($arrays['﻿"Title"'])) {
                                Criteria::compare($query, 'title', $itemcat_values[$arrays['﻿"Title"']]);

                            }else{
                                Criteria::compare($query, 'title', $itemcat_values[$arrays['���"Title"']]);
                            }
                            $item = $query->one();
                        }


                       if($item == null){
                        $item = new Item();
                       }

                        if (isset($arrays['Title']) || isset($arrays['﻿"Title"']) || isset($arrays['���"Title"'])) {

                            if (isset($arrays['Title'])) {
                                $item->title = $itemcat_values[$arrays['Title']];

                            } else if(isset($arrays['﻿"Title"'])) {
                                $item->title = $itemcat_values[$arrays['﻿"Title"']];
                            }else{
                                $item->title = $itemcat_values[$arrays['���"Title"']];
                            }
                        }
                        if (isset($arrays['Short Name']) && ($itemcat_values[$arrays['Short Name']] != '')) {

                            $item->short_name =$itemcat_values[$arrays['Short Name']];
                        }else{

                        $small = substr($item->title, 0, 10);
                        $item->short_name =$small;
                        }
                        /* if (isset($arrays['Bill Description'])) {

                            $item->description =$itemcat_values[$arrays['Bill Description']];
                        } */

                        //if (isset($arrays['Product Code'])) {

                            $item->item_code = User::randomBarcode('5');
                        //}
                        if (isset($arrays['HSN Code'])) {

                            $item->hsn_code =$itemcat_values[$arrays['HSN Code']];
                        }


                            $item->item_type = 0;



                            $item->status = 0;

                        /* if (isset($arrays['Item Type'])) {

                            $item->item_type = Item:: getTypeKeyOptions($itemcat_values[$arrays['Item Type']]);
                        }
                        if (isset($arrays['Status'])) {

                            $item->status = Item:: getStatusKeyOptions($itemcat_values[$arrays['Status']]);
                        } */
                        if (isset($arrays['Item Category']) && ($arrays['Item Category']) != '') {
                            $query = Item::find();
                            Criteria::compare($query, 'title', $itemcat_values[$arrays['Item Category']]);
                            $category = $query->one();
                            if($category){
                                $item->category_id =$category->id;
                            }

                        }
                        if (isset($arrays['Item SubCategory']) && ($arrays['Item SubCategory']) != '') {
                            $query = Item::find();
                            Criteria::compare($query, 'title', $itemcat_values[$arrays['Item SubCategory']]);
                            $category = $query->one();
                            if($category){
                                $item->category_id =$category->id;
                            }

                        }
                        if (isset($arrays['Item Company']) && $arrays['Item Company']) {
                            $query = Item::find();
                            Criteria::compare($query, 'title', $itemcat_values[$arrays['Item Company']]);
                            $category = $query->one();
                            if($category){
                                $item->company_id =$category->id;
                            }

                        }
                        if (isset($arrays['Sub Category'])  && $arrays['Sub Category']) {
                            $query = Item::find();
                            Criteria::compare($query, 'title', $itemcat_values[$arrays['Sub Category']]);
                            $category = $query->one();
                            if($category){
                                $item->sub_company_id =$category->id;
                            }

                        }
                        if (isset($arrays['MRP'])) {

                            $item->mrp = $itemcat_values[$arrays['MRP']];
                        }
                        if (isset($arrays['Sale Price'])) {

                            $item->sale_price = $itemcat_values[$arrays['Sale Price']];
                        }
                        if (isset($arrays['Purchase Price'])) {

                            $item->purchase_price = $itemcat_values[$arrays['Purchase Price']];
                        }
                        if (isset($arrays['Weight'])) {

                            $item->weight = $itemcat_values[$arrays['Weight']];
                        }
                        if (isset($arrays['Whole Sale'])) {

                            $item->whole_sale = $itemcat_values[$arrays['Whole Sale']];
                        }
                        if (isset($arrays['Opening Stock'])) {

                            $item->opening_stock = $itemcat_values[$arrays['Opening Stock']];
                        }
                        if (isset($arrays['Minimum Quantity'])) {

                            $item->min_qty = $itemcat_values[$arrays['Minimum Quantity']];
                        }
                        if (isset($arrays['Maximum Quantity'])) {

                            $item->max_qty = $itemcat_values[$arrays['Maximum Quantity']];
                        }
                        if (isset($arrays['Reorder Quantity'])) {

                            $item->reorder_qty = $itemcat_values[$arrays['Reorder Quantity']];
                        }
                        $role = UserRole::find()->where(['title'=>'Admin'])->one();
                        $user = Yii::$app->user->model;
                        if($user->role_id != $role->id ){
                            $item->state_id = Item::STATUS_INACTIVE;
                        }

                        if ($item->save()) {
                            $itemdetail = new ItemDetail();
                            if (isset($arrays['Tax'])) {
                                $query = Item::find();
                                Criteria::compare($query, 'title', $itemcat_values[$arrays['Tax']]);
                                $tax = $query->one();
                                if($tax){
                                    $itemdetail->tax_id =$tax->id;
                                }

                            }
                            if (isset($arrays['Opening Stock'])) {

                                $itemdetail->open_stock_qty = $itemcat_values[$arrays['Opening Stock']];
                            }
                            if (isset($arrays['Barcode'])) {
                                $itemdetail->bar_code =$itemcat_values[$arrays['Barcode']];
                            }
                            $itemdetail->outlet_id = 5;
                            $itemdetail->item_id = $item->id;

                            if($itemdetail->save()){

                                $batch_no =  User::randomBarcode('5');

                                    $itemstock = ItemStock ::model()->findByAttributes(['batch_number'=>$batch_no,'outlet_id'=>$itemdetail->outlet_id ,
                                            'vendor_id'=>'0'
                                    ]);
                                    if($itemstock == null){
                                        $itemstock = new ItemStock;
                                    }

                                    $itemstock->balance_qty = $itemdetail->open_stock_qty;
                                    $itemstock->purchase_qty = $itemdetail->open_stock_qty;
                                    $itemstock->outlet_id = $itemdetail->outlet_id ;
                                    $itemstock->vendor_id = 0;
                                    $itemstock->mrp = $itemdetail->getItemDetailMrp();
                                    $itemstock->base_price = $itemdetail->getItemDetailSaleRate();
                                    $itemstock->batch_number = $batch_no;
                                    $itemstock->item_id = $item->id;
                                    $itemstock->item_detail_id = $itemdetail->id;
                                    if($itemstock->save()){

                                    }else{
                                        print_r($itemstock->getErrors());exit;
                                    }


                            }else{
                                print_R($itemdetail->getErrors());
                                exit;
                            }

                        } else {
                            print_R($item->getErrors());
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

    public function setAllValues($rows) {

            $output = 0;
            $count = count($rows);


            if ($count > 1) {

                $o = explode(',', $rows[0]);
                $arrays = array_flip($o);
                $set = true;
                $item = null;
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $new = false;
                    for ($i = 1; $i < $count; $i++) {
                        $itemcat_values = explode(',', $rows[$i]);
                        Yii::warning( var_export( $itemcat_values , true), 'item_values');

                        if (isset($arrays['Title']) || isset($arrays['﻿"Title"']) || isset($arrays['���"Title"'])) {
                            $query = Item::find();
                            if (isset($arrays['Title'])) {
                                $title = str_replace(";",",",$itemcat_values[$arrays['Title']]);
                                $title = str_replace("!",".",$title);
                                Criteria::compare($query, 'title', $title);
                            } else if(isset($arrays['﻿"Title"'])) {
                                $title = str_replace(";",",",$itemcat_values[$arrays['﻿"Title"']]);
                                $title = str_replace("!",".",$title);
                                Criteria::compare($query, 'title', $title);

                            }else{
                                $title = str_replace(";",",",$itemcat_values[$arrays['���"Title"']]);
                                $title = str_replace("!",".",$title);
                                Criteria::compare($query, 'title', $title);

                            }

                            $item = $query->one();
                        }
                        Yii::warning( var_export( $item , true), '$alreadyitem');

                        if($item == null){
                            $new = true;
                            $item = new Item();
                        }

                        if (isset($arrays['Title']) || isset($arrays['﻿"Title"']) || isset($arrays['���"Title"'])) {

                            if (isset($arrays['Title'])) {
                                $item->title = str_replace(";",",",$itemcat_values[$arrays['Title']]);
                                $item->title = str_replace("!",".",$item->title);
                            } else if(isset($arrays['﻿"Title"'])) {

                                $item->title = str_replace(";",",",$itemcat_values[$arrays['﻿"Title"']]);
                                $item->title = str_replace("!",".",$item->title);
                            }else{
                                $item->title =str_replace(";",",",$itemcat_values[$arrays['���"Title"']]);
                                $item->title = str_replace("!",".",$item->title);
                            }
                        }
                        $small = substr($item->title, 0, 10);
                        $item->short_name =$small;
                        if (isset($arrays['Bill Description'])) {

                            $item->description =$itemcat_values[$arrays['Bill Description']];
                        }

                        if (isset($arrays['Product Code'])) {

                            $item->item_code =$itemcat_values[$arrays['Product Code']];
                        }else{
                            $item->item_code = User::randomBarcode(5);
                        }
                        if (isset($arrays['Short Name'])) {
                            if($itemcat_values[$arrays['Short Name']] != ''){
                            $com = str_replace("!",".",$itemcat_values[$arrays['Short Name']]);
                            $item->short_name =$itemcat_values[$arrays['Short Name']];
                            }else{
                                $item->short_name = substr($item->title,10);
                            }
                        }
                        if (isset($arrays['HSN Code'])) {

                            $item->hsn_code =$itemcat_values[$arrays['HSN Code']];
                        }
                        if (isset($arrays['Item Type'])) {

                            $item->item_type = Item:: getTypeKeyOptions($itemcat_values[$arrays['Item Type']]);
                        }
                        if (isset($arrays['Status'])) {

                            $item->status = Item:: getStatusKeyOptions($itemcat_values[$arrays['Status']]);
                        }
                        if (isset($arrays['Item Category'])) {
                            $cat = str_replace(";",",",$itemcat_values[$arrays['Item Category']]);
                            $query = Item::find();
                            Criteria::compare($query, 'title', $cat);
                            $category = $query->one();
                            if($category){
                                $item->category_id =$category->id;
                            }

                        }
                        if (isset($arrays['Sub Category'])) {
                            $sbcat = str_replace(";",",",$itemcat_values[$arrays['Sub Category']]);
                            $query = Item::find();
                            Criteria::compare($query, 'title', $sbcat);
                            $category = $query->one();
                            if($category){
                                $item->sub_category_id =$category->id;
                            }

                        }
                        if (isset($arrays['Item Company'])) {
                            $com = str_replace(";",",",$itemcat_values[$arrays['Item Company']]);
                            $query = Item::find();
                            Criteria::compare($query, 'title', $com);
                            $category = $query->one();
                            if($category){
                                $item->company_id =$category->id;
                            }

                        }
                        if (isset($arrays['Item Company Category'])) {
                            $comcat = str_replace(";",",",$itemcat_values[$arrays['Item Company Category']]);
                            $query = Item::find();
                            Criteria::compare($query, 'title', $comcat);
                            $category = $query->one();
                            if($category){
                                $item->sub_company_id =$category->id;
                            }

                        }

                        if (isset($arrays['MRP'])) {

                            $item->mrp = $itemcat_values[$arrays['MRP']];
                        }else{
                            if($new == true){
                            $item->mrp = '20.00';
                            }
                        }
                        if (isset($arrays['Sale Price'])) {

                            $item->sale_price = $itemcat_values[$arrays['Sale Price']];
                        }else{
                            if($new == true){
                            $item->sale_price = '20.00';
                            }
                        }
                        if (isset($arrays['Purchase Price'])) {

                            $item->purchase_price = $itemcat_values[$arrays['Purchase Price']];
                        }
                        if (isset($arrays['Weight'])) {

                            $item->weight = $itemcat_values[$arrays['Weight']];
                        }
                        if (isset($arrays['Whole Sale'])) {

                            $item->whole_sale = $itemcat_values[$arrays['Whole Sale']];
                        }
                        if (isset($arrays['Opening Stock'])) {

                            $item->opening_stock = $itemcat_values[$arrays['Opening Stock']];
                        }
                        if (isset($arrays['Minimum Quantity'])) {
                        if( $itemcat_values[$arrays['Minimum Quantity']] != ''){
                            $item->min_qty = $itemcat_values[$arrays['Minimum Quantity']];
                        }
                        }
                        if (isset($arrays['Maximum Quantity'])) {
                            if( $itemcat_values[$arrays['Maximum Quantity']] != ''){
                            $item->max_qty = $itemcat_values[$arrays['Maximum Quantity']];
                            }
                        }
                        if (isset($arrays['Reorder Quantity'])) {
                            if( $itemcat_values[$arrays['Reorder Quantity']] != ''){
                            $item->reorder_qty = $itemcat_values[$arrays['Reorder Quantity']];
                            }
                        }
                        $role = UserRole::find()->where(['title'=>'Admin'])->one();
                        $user = Yii::$app->user->model;
                        if($user->role_id != $role->id ){
                            $item->state_id = Item::STATUS_INACTIVE;
                        }
                        Yii::warning( var_export( $item , true), '$item');
                        if ($item->save()) {
                            /* $itemDetailoldbars = ItemDetail::find()->where(array (
                                    'item_id' =>  $item->id))->all();
                            if($itemDetailoldbars){
                                foreach($itemDetailoldbars as $itemDetailoldbar){
                                    $itemDetailoldbar->delete();
                                }
                            } */
                            if (isset($arrays['Barcode']) && ($itemcat_values[$arrays['Barcode']] != '')) {
                                $query = Item::find();
                                $outlet = $query->one();
                                $itemDetailbars = ItemDetail::find()->where([
                                        'bar_code' =>  $itemcat_values[$arrays['Barcode']],
                                    //    'outlet_id' => $outlet->id,
                                    //    'item_id' => $item->id,
                                ])->all();

                                if($itemDetailbars){
                                    foreach($itemDetailbars as $itemDetailbar){
                                        $itemDetailbar->delete();
                                    }
                                }
                                $itemDetail = new ItemDetail();

                                Yii::warning( var_export( $itemDetail , true), '$itemDetail');
                                $barcodestr = $itemcat_values[$arrays['Barcode']];
                                $stringlength = strlen($barcodestr);
                                if($stringlength < 9){
                                    $itemDetail->company_bar_code = ItemDetail::IS_COMPANY;
                                    $itemDetail->bar_code = $itemcat_values[$arrays['Barcode']];
                                }else{
                                    $itemDetail->company_bar_code = ItemDetail::IS_NOT_COMPANY;
                                $itemDetail->bar_code = $itemcat_values[$arrays['Barcode']];
                                }
                                $itemDetail->mrp = $item->mrp;
                                $itemDetail->open_stock_qty = $item->opening_stock;
                                if($outlet)
                                $itemDetail->outlet_id = $outlet->id;
                                $itemDetail->item_id = $item->id;
                                if (isset($arrays['Tax'])) {
                                    $query = Item::find();
                                    Criteria::compare($query, 'title', $itemcat_values[$arrays['Tax']]);
                                    $category = $query->one();
                                    if($category){
                                        $itemDetail->tax_id = $category->id;

                                    }else{
                                        $newtax = new Tax();
                                        $newtax->title = $itemcat_values[$arrays['Tax']];
                                        if($newtax->save()){
                                            $itemDetail->tax_id = $newtax->id;
                                        }
                                    }
                                }
                                if($itemDetail->save()){
                                    $batch_no =  User::randomBarcode('5');

                                    $itemstock = ItemStock ::model()->findByAttributes(['outlet_id'=>$itemDetail->outlet_id ,
                                            'vendor_id'=>'0','item_detail_id'=>$itemDetail->id ,'item_id'=>$item->id
                                    ]);
                                    if($itemstock == null){
                                        $itemstock = new ItemStock;
                                    }

                                    $itemstock->balance_qty = trim($itemDetail->open_stock_qty);
                                    $itemstock->purchase_qty = trim($itemDetail->open_stock_qty);
                                    $itemstock->outlet_id = $itemDetail->outlet_id ;
                                    $itemstock->vendor_id = 0;
                                    $itemstock->mrp = $item->mrp;
                                    $itemstock->base_price = $item->sale_price;
                                    $itemstock->batch_number = $batch_no;
                                    $itemstock->item_id = $item->id;
                                    $itemstock->item_detail_id = $itemDetail->id;
                                    if($itemstock->save()){

                                    }else{
                                        print_r($itemstock->getErrors());exit;
                                    }
                                    if ($itemDetail->tax_id != null) {

                                            $itemtax= new ItemTax();
                                            $itemtax->item_detail_id =$itemDetail->id;
                                            $itemtax->tax_id =$itemDetail->tax_id;
                                            $itemtax->save();

                                    }
                                }else{

                                        print_R($itemDetail->getErrors());
                                        exit;
                                        $set = false;

                                }

                            }
                        } else {
                            echo '<pre>';
                            print_R($item);
                            print_R($item->getErrors());
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

    public function setAllVendorValues($rows) {

            $output = 0;
            $count = count($rows);


            if ($count > 1) {

                $o = explode(',', $rows[0]);
                $arrays = array_flip($o);
                $set = true;
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    for ($i = 1; $i < $count; $i++) {
                        $item_values = explode(',', $rows[$i]);


                        $itemvendor = new ItemVendor();

                        if (isset($arrays['Title']) || isset($arrays['﻿"Title"']) || isset($arrays['���"Title"'])) {

                            if (isset($arrays['Title'])) {
                                $query = Item::find();
                                Criteria::compare($query, 'title', $item_values[$arrays['Title']]);
                                $item = $query->one();
                                Yii::warning( var_export( $item , true), '$$$$item');
                                if($item){
                                    $itemvendor->item_detail_id = $item->id;
                                }

                            } else if(isset($arrays['﻿"Title"'])) {
                                $query = Item::find();
                                Criteria::compare($query, 'title', $item_values[$arrays['﻿"Title"']]);
                                $item = $query->one();
                                if($item){
                                    $itemvendor->item_detail_id = $item->id;
                                }
                            }else{
                                $query = Item::find();
                                Criteria::compare($query, 'title', $item_values[$arrays['���"Title"']]);
                                $item = $query->one();
                                if($item){
                                    $itemvendor->item_detail_id = $item->id;
                                }

                            }
                        }



                        if (isset($arrays['Vendor'])) {
                            $query = Item::find();
                            Criteria::compare($query, 'name', $item_values[$arrays['Vendor']]);
                            $vendor = $query->one();
                            Yii::warning( var_export( $vendor , true), '$$vendor');
                            if($vendor){
                                $itemvendor->vendor_id = $vendor->id;
                            }
                        }
                        $Alreadyitemvendor = ItemVendor::find()->where(['item_detail_id'=>$itemvendor->item_detail_id,
                                'vendor_id'=>$itemvendor->vendor_id
                        ])->one();
                        Yii::warning( var_export( $Alreadyitemvendor , true), '$Alreadyitemvendor');
                        if($Alreadyitemvendor == null && $itemvendor->vendor_id != null && $itemvendor->item_detail_id != null){
                        if ($itemvendor->save()) {


                        } else {
                            print_R($itemvendor->getErrors());
                            exit;
                            $set = false;
                        }
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

    public function setAllStockValues($rows){
            $output = 0;
            $count = count($rows);


            if ($count > 1) {

                $o = explode(',', $rows[0]);
                $arrays = array_flip($o);
                $set = true;
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    for ($i = 1; $i < $count; $i++) {
                        $item_values = explode(',', $rows[$i]);


                        $itemstock = new ItemStock();
                        $product_code = null;
                        $product_title = null;
                        if (isset($arrays['Prod Code']) || isset($arrays['﻿"Prod Code"']) || isset($arrays['���"Prod Code"'])) {

                            if (isset($arrays['Prod Code'])) {
                                $product_code = $item_values[$arrays['Prod Code']];
                            } else if(isset($arrays['﻿"Prod Code"'])) {
                                $product_code = $item_values[$arrays['﻿"Prod Code"']];
                            }else{
                                $product_code = $item_values[$arrays['���"Prod Code"']];
                            }
                        }



                        if (isset($arrays['Product'])) {
                            $product_title = $item_values[$arrays["Product"]];
                        }

                        if($product_title  != null && $product_code != null){
                        $query = Item::find();
                        Criteria::compare($query, 'title', $product_title);
                        //$criteria->compare('item_code',$product_code);
                        $getItem = $query->one();
                        Yii::warning( var_export( $getItem , true), '$getItem');
                        if($getItem){
                            $stocks = ItemStock::deleteAll(['item_id'=>$getItem->id]);
                            $barcode = $getItem->getItemBarcodes();
                            if($barcode){
                                $query = ItemDetail::find();
                                Criteria::compare($query, 'item_id', $getItem->id);
                                Criteria::compare($query, 'bar_code', $barcode);
                                $getItemDetail = $query->one();
                                Yii::warning( var_export( $getItemDetail , true), '$getItemDetail');
                                if($getItemDetail){
                                    $batch_no =  User::randomBarcode('5');
                            $itemstock = new ItemStock();
                            if (isset($arrays['Total'])) {
                            $itemstock->balance_qty =  $item_values[$arrays["Total"]];
                            }
                            if (isset($arrays['Opening'])) {
                            $itemstock->purchase_qty =  $item_values[$arrays["Opening"]];
                            }
                            $itemstock->outlet_id = $getItemDetail->outlet_id;
                            $itemstock->vendor_id = 0;

                            if (isset($arrays['MRP'])) {
                                $itemstock->mrp = $item_values[$arrays["MRP"]];
                            }else{
                                if($getItemDetail){
                                $itemstock->mrp = $getItemDetail->mrp;
                                }else{
                                    $itemstock->mrp = $getItem->mrp;
                                }
                            }
                            $itemstock->base_price = $getItem->sale_price;
                            $itemstock->batch_number = $batch_no;
                            $itemstock->item_id = $getItem->id;
                            $itemstock->item_detail_id = $getItemDetail->id;

                            if ($itemstock->save()) {


                            } else {
                                print_R($itemstock->getErrors());
                                exit;
                                $set = false;
                            }
                            }
                            }

                        }
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

    public function getMaximumQty(){
            return $this->max_qty;
            $max_qty = $this->max_qty;

            $query = MrsDetail::find();
            $query->andWhere('item_id ='.$this->id);
            $query->andWhere('status ='.MrsDetail::STATUS_DONE);
            $query->orderBy(['id' => SORT_DESC]);
            $last_mrs_detail = $query->one();
            Yii::warning( var_export( $last_mrs_detail , true), '$last_mrs_detail');

            if($last_mrs_detail){
                $mrs = Mrs::findOne($last_mrs_detail->mrs_id);
                $mrs_date = date('Y-m-d',strtotime($mrs->mrs_date));
                $curent_date = date('Y-m-d');
                $sale_qty_till_date = $this->getItemSaleQty($mrs_date,$curent_date);
                Yii::warning( var_export( $sale_qty_till_date , true), '$sale_qty_till_date');

                $lastsale_qty_till_date = 0;
                $query = MrsDetail::find();
                $query->andWhere('item_id ='.$this->id);
                $query->andWhere('status ='.MrsDetail::STATUS_DONE);
                $query->andWhere('mrs_id !='.$last_mrs_detail->mrs_id);
                $query->orderBy(['id' => SORT_DESC]);
                $seclast_mrs_detail = $query->one();
                Yii::warning( var_export( $seclast_mrs_detail , true), '$$seclast_mrs_detail');

                if($seclast_mrs_detail){
                    $lastmrs = Mrs::findOne($seclast_mrs_detail->mrs_id);
                    $lastmrs_date = date('Y-m-d',strtotime($lastmrs->mrs_date));
                    $lastsale_qty_till_date = $this->getItemSaleQty($lastmrs_date,$mrs_date);
                }
                Yii::warning( var_export( $lastsale_qty_till_date , true), '$lastsale_qty_till_date');

                if($sale_qty_till_date >$lastsale_qty_till_date && $lastsale_qty_till_date != 0){
                    $inc_sale = $sale_qty_till_date - $lastsale_qty_till_date;
                    $inc_sale_per = ($inc_sale/$lastsale_qty_till_date)*100;
                    Yii::warning( var_export( $inc_sale_per , true), '$inc_sale_per');

                    if($inc_sale_per > 0){
                        $get_inc = $max_qty *($inc_sale_per/100);
                        $max_qty = $max_qty + $get_inc;
                        $max_qty = round($max_qty);
                        Yii::warning( var_export( $get_inc , true), '$get_inc');
                        Yii::warning( var_export( $max_qty , true), '$max_qty');

                    }

                }else{
                    if($lastsale_qty_till_date > $sale_qty_till_date){
                    $dec_sale = $lastsale_qty_till_date - $sale_qty_till_date;
                    $dec_sale_per = ($dec_sale/$lastsale_qty_till_date)*100;
                    Yii::warning( var_export( $dec_sale_per , true), '$dec_sale_per');

                    if($dec_sale_per > 0){
                        $get_inc = $max_qty *($dec_sale_per/100);
                        $max_qty = $max_qty - $get_inc;
                        $max_qty = round($max_qty);
                        Yii::warning( var_export( $get_inc , true), '$get_inc');
                        Yii::warning( var_export( $max_qty , true), '$max_qty');

                    }
                }
                }
            }
            if($max_qty == 0){
                $max_qty = $this->max_qty;
            }
            return $max_qty;
        }

    public function getMinimumQty(){
            return $this->min_qty;
            $max_qty = $this->min_qty;

            $query = MrsDetail::find();
            $query->andWhere('item_id ='.$this->id);
            $query->andWhere('status ='.MrsDetail::STATUS_DONE);
            $query->orderBy(['id' => SORT_DESC]);
            $last_mrs_detail = $query->one();
            Yii::warning( var_export( $last_mrs_detail , true), '$last_mrs_detail');

            if($last_mrs_detail){
                $mrs = Mrs::findOne($last_mrs_detail->mrs_id);
                $mrs_date = date('Y-m-d',strtotime($mrs->mrs_date));
                $curent_date = date('Y-m-d');
                $sale_qty_till_date = $this->getItemSaleQty($mrs_date,$curent_date);
                Yii::warning( var_export( $sale_qty_till_date , true), '$sale_qty_till_date');

                $lastsale_qty_till_date = 0;
                $query = MrsDetail::find();
                $query->andWhere('item_id ='.$this->id);
                $query->andWhere('status ='.MrsDetail::STATUS_DONE);
                $query->andWhere('mrs_id !='.$last_mrs_detail->mrs_id);
                $query->orderBy(['id' => SORT_DESC]);
                $seclast_mrs_detail = $query->one();
                Yii::warning( var_export( $seclast_mrs_detail , true), '$$seclast_mrs_detail');

                if($seclast_mrs_detail){
                    $lastmrs = Mrs::findOne($seclast_mrs_detail->mrs_id);
                    $lastmrs_date = date('Y-m-d',strtotime($lastmrs->mrs_date));
                    $lastsale_qty_till_date = $this->getItemSaleQty($lastmrs_date,$mrs_date);
                }
                Yii::warning( var_export( $lastsale_qty_till_date , true), '$lastsale_qty_till_date');

                if($sale_qty_till_date >$lastsale_qty_till_date && $lastsale_qty_till_date != 0){
                    $inc_sale = $sale_qty_till_date - $lastsale_qty_till_date;
                    $inc_sale_per = ($inc_sale/$lastsale_qty_till_date)*100;
                    Yii::warning( var_export( $inc_sale_per , true), '$inc_sale_per');

                    if($inc_sale_per > 0){
                        $get_inc = $max_qty *($inc_sale_per/100);
                        $max_qty = $max_qty + $get_inc;
                        $max_qty = round($max_qty);
                        Yii::warning( var_export( $get_inc , true), '$get_inc');
                        Yii::warning( var_export( $max_qty , true), '$max_qty');

                    }

                }else{
                    if($lastsale_qty_till_date > $sale_qty_till_date){
                        $dec_sale = $lastsale_qty_till_date - $sale_qty_till_date;
                        $dec_sale_per = ($dec_sale/$lastsale_qty_till_date)*100;
                        Yii::warning( var_export( $dec_sale_per , true), '$dec_sale_per');

                        if($dec_sale_per > 0){
                            $get_inc = $max_qty *($dec_sale_per/100);
                            $max_qty = $max_qty - $get_inc;
                            $max_qty = round($max_qty);
                            Yii::warning( var_export( $get_inc , true), '$get_inc');
                            Yii::warning( var_export( $max_qty , true), '$max_qty');

                        }
                    }
                }
            }

            return $max_qty;
        }

    public function getReorderQty(){
            $max_qty = $this->reorder_qty;

            $query = MrsDetail::find();
            $query->andWhere('item_id ='.$this->id);
            $query->andWhere('status ='.MrsDetail::STATUS_DONE);
            $query->orderBy(['id' => SORT_DESC]);
            $last_mrs_detail = $query->one();
            Yii::warning( var_export( $last_mrs_detail , true), '$last_mrs_detail');

            if($last_mrs_detail){
                $mrs = Mrs::findOne($last_mrs_detail->mrs_id);
                $mrs_date = date('Y-m-d',strtotime($mrs->mrs_date));
                $curent_date = date('Y-m-d');
                $sale_qty_till_date = $this->getItemSaleQty($mrs_date,$curent_date);
                Yii::warning( var_export( $sale_qty_till_date , true), '$sale_qty_till_date');

                $lastsale_qty_till_date = 0;
                $query = MrsDetail::find();
                $query->andWhere('item_id ='.$this->id);
                $query->andWhere('status ='.MrsDetail::STATUS_DONE);
                $query->andWhere('mrs_id !='.$last_mrs_detail->mrs_id);
                $query->orderBy(['id' => SORT_DESC]);
                $seclast_mrs_detail = $query->one();
                Yii::warning( var_export( $seclast_mrs_detail , true), '$$seclast_mrs_detail');

                if($seclast_mrs_detail){
                    $lastmrs = Mrs::findOne($seclast_mrs_detail->mrs_id);
                    $lastmrs_date = date('Y-m-d',strtotime($lastmrs->mrs_date));
                    $lastsale_qty_till_date = $this->getItemSaleQty($lastmrs_date,$mrs_date);
                }
                Yii::warning( var_export( $lastsale_qty_till_date , true), '$lastsale_qty_till_date');

                if($sale_qty_till_date >$lastsale_qty_till_date && $lastsale_qty_till_date != 0){
                    $inc_sale = $sale_qty_till_date - $lastsale_qty_till_date;
                    $inc_sale_per = ($inc_sale/$lastsale_qty_till_date)*100;
                    Yii::warning( var_export( $inc_sale_per , true), '$inc_sale_per');

                    if($inc_sale_per > 0){
                        $get_inc = $max_qty *($inc_sale_per/100);
                        $max_qty = $max_qty + $get_inc;
                        $max_qty = round($max_qty);
                        Yii::warning( var_export( $get_inc , true), '$get_inc');
                        Yii::warning( var_export( $max_qty , true), '$max_qty');

                    }

                }else{
                    if($lastsale_qty_till_date > $sale_qty_till_date){
                        $dec_sale = $lastsale_qty_till_date - $sale_qty_till_date;
                        $dec_sale_per = ($dec_sale/$lastsale_qty_till_date)*100;
                        Yii::warning( var_export( $dec_sale_per , true), '$dec_sale_per');

                        if($dec_sale_per > 0){
                            $get_inc = $max_qty *($dec_sale_per/100);
                            $max_qty = $max_qty - $get_inc;
                            $max_qty = round($max_qty);
                            Yii::warning( var_export( $get_inc , true), '$get_inc');
                            Yii::warning( var_export( $max_qty , true), '$max_qty');

                        }
                    }
                }
            }
        if($max_qty == 0){
        $max_qty = $this->reorder_qty;
        }
            return $max_qty;
        }

    public function getReorderQtyNew(){
            $safetyStock = $this->min_qty;
            $rop = 0;

            $query = MrsDetail::find();
            $query->andWhere('item_id ='.$this->id);
            $query->andWhere('status ='.MrsDetail::STATUS_DONE);
            $query->orderBy(['id' => SORT_DESC]);
            $last_mrs_detail = $query->one();
            Yii::warning( var_export( $last_mrs_detail , true), '$last_mrs_detail');

            if($last_mrs_detail){
                $mrs = Mrs::findOne($last_mrs_detail->mrs_id);
                $mrs_date = date('Y-m-d',strtotime($mrs->mrs_date));
                $curent_date = date('Y-m-d');
                $sale_qty_till_date = $this->getItemSaleQty($mrs_date,$curent_date);
                Yii::warning( var_export( $sale_qty_till_date , true), '$sale_qty_till_date');

                $mrsBetweenDays = $this->daysBetweenTwoDays($curent_date, $mrs_date);
                Yii::warning( var_export( $mrsBetweenDays , true), '$mrsBetweenDays');

                $avgDailySales = round($sale_qty_till_date / $mrsBetweenDays, 2);
                Yii::warning( var_export( $avgDailySales , true), '$avgDailySales');

                $avgDeliveryTime = 0;

                $query = MrsDetail::find();
                $query->andWhere('item_id ='.$this->id);
                $query->andWhere('status ='.MrsDetail::STATUS_DONE);
                $query->orderBy(['id' => SORT_DESC]);
                $query->limit(3);
                //$criteria->addCondition('item_id =' . $this->id);
                $mrs_details = $query->all();
                $deliveryDays = 0;
                if (count($mrs_details) > 0) {
                    $endDate = date('Y-m-d');
                    foreach ($mrs_details as $key => $mrsDetail) {
                        $mrs = Mrs::findOne($mrsDetail->mrs_id);
                        $mrsCreateDate = date('Y-m-d',strtotime($mrs->mrs_date));
                        $query = StockLog::find();
                        $query->andWhere('type_id ='.StockLog::TYPE_ADDED);
                        $query->andWhere('item_id ='.$this->id);
                        $query->andWhere(['between', 'DATE(create_time)', $mrsCreateDate, $endDate]);
                        $query->orderBy(['id' => SORT_DESC]);
                        $stockLog = $query->one();
                        if ($stockLog) {
                            $stockDate = date('Y-m-d',strtotime($stockLog->create_time));
                            $stockBetweenDays = $this->daysBetweenTwoDays($mrsCreateDate, $stockDate);
                            $deliveryDays += $stockBetweenDays;
                        }
                        $endDate = $mrsCreateDate;
                    }
                    Yii::warning( var_export( $deliveryDays , true), '$deliveryDays');
                    Yii::warning( var_export( count($mrs_details) , true), 'count($mrs_details)');
                    if ($deliveryDays > 0) {
                        $avgDeliveryTime = round($deliveryDays / count($mrs_details));
                    }
                }

                Yii::warning( var_export( $avgDeliveryTime , true), '$avgDeliveryTime');

                if ($avgDailySales > 0 && $avgDeliveryTime > 0) {
                    $rop = ($avgDailySales * $avgDeliveryTime) + $safetyStock;
                    Yii::warning( var_export( $rop , true), '$rop');
                }
            }
            if($rop > 0){
                return round($rop);
            }
            return $safetyStock;
        }

    public function getAscBarCodeTotalRemainingQuantityIds($quantity_verify)
        {
            $item_detail_ids = [];
            $remaining_quantity = 0;
            $query = ItemDetail::find();
            $query->orderBy(['id' => SORT_ASC]);
            $query->andWhere('status =' . ItemDetail::STATUS_ACTIVE);
            //$criteria->addCondition('item_id =' . $this->id);
            $item_details = $query->all();

            if(!empty($item_details))
            {

                foreach($item_details as $item_detail)
                {

                  if ($item_detail) {
                $remaining_quantity = '0.000';
                $add_quantity = '0.000';
                $sub_quantity = '0.000';
                $query = ItemDetail::find();
                $query->andWhere('item_detail_id =' . $item_detail->id);
                $query->orderBy(['id' => SORT_ASC]);
                $query->andWhere("balance_qty > 0.000");
                $query->andWhere('item_detail_id IS NOT NULL');
                $stocks = $query->all();

                if (! empty($stocks)) {
                    foreach ($stocks as $stock) {

                        $add_quantity = ($add_quantity) + ($stock->balance_qty);
                    }
                }
                $query = ItemStock::find();
                $query->andWhere('item_detail_id =' . $item_detail->id);
                $query->orderBy(['id' => SORT_ASC]);
                $query->andWhere("balance_qty < 0.000");
                $query->andWhere('item_detail_id IS NOT NULL');
                $stocks = $query->all();

                if (! empty($stocks)) {
                    foreach ($stocks as $stock) {
                        $sub_quantity = ($sub_quantity) + abs($stock->balance_qty);
                    }
                }
                $remaining_quantity = bcsub($add_quantity, $sub_quantity, 3);


                if($remaining_quantity == $quantity_verify)
                {

                    $item_detail_ids [] = $item_detail->item_id;

                }

            }

                }
            }


           return $item_detail_ids;
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

    public static function getItemCompany($id) {
            $company = ItemCompany::findOne( $id );
            if ($company) {
                return $company->title;
            }
            return '';
        }

    public static function getItemCompanyCategory($id) {
            $company = ItemCompanyCategory::findOne( $id );
            if ($company) {
                return $company->title;
            }
            return '';
        }

    public static function getItemCategory($id) {
            $company = ItemCategory::findOne( $id );
            if ($company) {
                return $company->title;
            }
            return '';
        }

    /**
     * Yii 1's reportstocksearch(): a listing of its own, converted as written.
     */
    public function reportstocksearch()
    {

		$query = self::find();
		$user = Yii::$app->user->model;
		if(isset(Yii::$app->session ['item_name']) && (Yii::$app->session ['item_name'] != '')){
			$this->title = Yii::$app->session ['item_name'];
		}
// 		if ($this->name != null) {
// 			$criteria->condition = "title LIKE :title";
// 			$criteria->params = array (
// 					':title' => trim ( (string)$this->title ) . '%',
// 					':title1' => '%' . trim ( $this->name ) . '%'
// 			);
// 		} else {
			$query->andWhere("title LIKE :title ", [
					':title' => trim ( (string)$this->title ) . '%'
			]);
	
			
		/* } */
		Yii::warning( var_export($this->name, true), '$$this->name');
		if ($user->role_id == 6) {
			$itemvendor_ids = [];
			$vendor = Vendor::find()->where([
					'create_user_id' => $user->id
			])->orderBy(['id' => SORT_DESC])->one();
			if ($vendor) {
				$itemvendors = ItemVendor::find()->where([
						'vendor_id' => $vendor->id
				])->orderBy(['id' => SORT_DESC])->all();
				if ($itemvendors) {
	
					foreach ( $itemvendors as $itemvendor ) {
						$itemvendor_ids [] = $itemvendor->item_detail_id;
					}
				}
			}
			$query->andWhere(['id' => $itemvendor_ids]);
			Yii::warning( var_export($itemvendor_ids, true), '$itemvendor_ids');
		}
		Criteria::compare($query, 'id', $this->id);
	
		/*
		 * if($this->title != null){
		 * $titles = explode(' ',$this->title);
		 * foreach($titles as $title){
		 * $criteria->compare ( 'title',$title, true);
		 * }
		 * }
		 */
		/* if (Yii::$app->session ['item_name'] != '' and Yii::$app->session ['item_name'] != null) {
			$this->name = Yii::$app->session ['item_name'];
		} */
		Yii::warning( var_export($this->bar_code, true), '$this->bar_code ');
		if (isset ( $this->bar_code ) && ($this->bar_code != '')) {
			$item_detail = ItemDetail::find()->where([
					'bar_code' => $this->bar_code
			])->one();
			if ($item_detail) {
	
				Criteria::compare($query, 'id', $item_detail->item_id);
			}
		}
		if (isset ( $this->tax_id ) && ($this->tax_id != '')) {
			$detail_ids = [];
			$query4 = ItemDetail::find();
			$query4->andWhere('tax_id ='.$this->tax_id);
			$query4->orderBy(['id' => SORT_DESC]);
			$query4->groupBy('item_id');
			$item_details = $query4->all();
			Yii::warning( var_export($this->tax_id, true), '$this->tax_id');
	
			if ($item_details) {
				foreach ( $item_details as $item_detail ) {
	
					$detail_ids [] = $item_detail->item_id;
				}
				Yii::warning( var_export($detail_ids, true), '$detail_ids');
	
			}
			$query->andWhere(['id' => $detail_ids]);
		}
		if (isset ( $this->vendor_id ) && ($this->vendor_id != '') && ($user->role_id != 6)) {
			$itemvendor_idds = [];
			$itemvendorrs = ItemVendor::find()->where([
					'vendor_id' => $this->vendor_id
			])->orderBy(['id' => SORT_DESC])->all();
			if ($itemvendorrs) {
	
				foreach ( $itemvendorrs as $itemvendor ) {
					$itemvendor_idds [] = $itemvendor->item_detail_id;
				}
				$query->andWhere(['id' => $itemvendor_idds]);
			}
		}
		Criteria::compare($query, 'item_code', $this->item_code, true);
		Criteria::compare($query, 'description', $this->description, true);
		Criteria::compare($query, 'image_file', $this->image_file, true);
		Criteria::compare($query, 'item_type', $this->item_type);
		Criteria::compare($query, 'status', $this->status);
		Criteria::compare($query, 'type_id', $this->type_id);
		Criteria::compare($query, 'mrp', $this->mrp);
		Criteria::compare($query, 'is_tax', $this->is_tax);
		Criteria::compare($query, 'is_discount', $this->is_discount);
		Criteria::compare($query, 'sale_price', $this->sale_price);
		Criteria::compare($query, 'hsn_code', $this->hsn_code, true);
		Criteria::compare($query, 'purchase_price', $this->purchase_price);
		Criteria::compare($query, 'sub_category_id', $this->sub_category_id);
		Criteria::compare($query, 'opening_stock', $this->opening_stock);
		Criteria::compare($query, 'weight', $this->weight);
		Criteria::compare($query, 'sub_category_id', $this->sub_category_id);
		Criteria::compare($query, 'category_id', $this->category_id);
		Criteria::compare($query, 'sub_company_id', $this->sub_company_id);
		Criteria::compare($query, 'company_id', $this->company_id);
		Criteria::compare($query, 'create_time', $this->create_time, true);
		Criteria::compare($query, 'create_user_id', $this->create_user_id);
		Criteria::compare($query, 'updated_by', $this->updated_by);
		Yii::warning( var_export($query, true), '$criteria');
		$query->orderBy(['id' => SORT_DESC]);

		return new ActiveDataProvider([
		    'query' => $query,
		    'sort' => ['defaultOrder' => []],
		    'pagination' => ['pageSize' => Ui::PAGE_SIZE],
		]);
    }

    /**
     * Yii 1's adjust(): a listing of its own, converted as written.
     */
    public function adjust()
    {

		$query = self::find();
		$user = Yii::$app->user->model;
		
		if (Yii::$app->session ['item_name'] != '' and Yii::$app->session ['item_name'] != null) {
			$this->name = Yii::$app->session ['item_name'];
		}
		
		if (Yii::$app->session ['remaining_quan'] != '' and Yii::$app->session ['remaining_quan'] != null) {	
		$this->remaining_quan = Yii::$app->session ['remaining_quan'];	
		}
		
		
		if ($this->name != null) {
			$query->andWhere("title LIKE :title AND title LIKE :title1 ", [
					//':title' => trim ( (string)$this->title ) . '%',
					//':title' => "%" .trim ( (string)$this->title ) . '%',
					':title1' => '%' . trim ( $this->name ) . '%' 
			]);
			
		} else {
			// $criteria->condition = "title LIKE :title ";
			
			// $criteria->params = array (
			// 		':title' => trim ( (string)$this->title ) . '%' 
			// );
		}
		Yii::warning( var_export($this->name, true), '$$this->name');
		if (isset ( Yii::$app->session ['company_id'] ) && (Yii::$app->session ['company_id'] != '')) {
			$this->company_id = Yii::$app->session ['company_id'];
		}
		
		if (isset ( $this->vendor_id ) && ($this->vendor_id != '') && ($user->role_id != 6)) {
			$itemvendor_idds = [];
			$itemvendorrs = ItemVendor::find()->where([
					'vendor_id' => $this->vendor_id
			])->orderBy(['id' => SORT_DESC])->all();
			if ($itemvendorrs) {
		
				foreach ( $itemvendorrs as $itemvendor ) {
					$itemvendor_idds [] = $itemvendor->item_detail_id;
				}
				$query->andWhere(['id' => $itemvendor_idds]);
			}
		}
		
		if($this->remaining_quan != null){
			
			
			
			$ids =  $this->getAscBarCodeTotalRemainingQuantityIds($this->remaining_quan );
			
			$query->andWhere(['id' => $ids]);
		
			
		}else{
			
			Criteria::compare($query, 'id', $this->id);
					
		}
		
		Criteria::compare($query, 'title', $this->title, true);
		Criteria::compare($query, 'item_code', $this->item_code, true);
		Criteria::compare($query, 'description', $this->description, true);
		Criteria::compare($query, 'image_file', $this->image_file, true);
		Criteria::compare($query, 'mrp', $this->mrp);
		Criteria::compare($query, 'item_type', $this->item_type);
		Criteria::compare($query, 'status', $this->status);
		Criteria::compare($query, 'type_id', $this->type_id);
		Criteria::compare($query, 'is_tax', $this->is_tax);
		Criteria::compare($query, 'is_discount', $this->is_discount);
		Criteria::compare($query, 'sale_price', $this->sale_price);
		Criteria::compare($query, 'hsn_code', $this->hsn_code, true);
		Criteria::compare($query, 'purchase_price', $this->purchase_price);
		Criteria::compare($query, 'sub_category_id', $this->sub_category_id);
		Criteria::compare($query, 'opening_stock', $this->opening_stock);
		Criteria::compare($query, 'weight', $this->weight);
		Criteria::compare($query, 'sub_category_id', $this->sub_category_id);
		Criteria::compare($query, 'category_id', $this->category_id);
		Criteria::compare($query, 'sub_company_id', $this->sub_company_id);
		Criteria::compare($query, 'company_id', $this->company_id);
		Criteria::compare($query, 'create_time', $this->create_time, true);
		Criteria::compare($query, 'create_user_id', $this->create_user_id);
		Criteria::compare($query, 'updated_by', $this->updated_by);
		
		// Yii 1's debugging, which the port reproduced: `print_r($criteria)`
		// there, the ActiveQuery here, dumped above item/adjustStock's grid.
		// No die() after it, so the page rendered and simply carried the dump.
		// Removed in both trees in one commit; see docs/live-bugs-found.md.
		$query->orderBy(['adjustment_time' => SORT_DESC]);

		return new ActiveDataProvider([
		    'query' => $query,
		    'sort' => ['defaultOrder' => []],
		    'pagination' => ['pageSize' => 20],
		]);
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
