<?php
namespace app\models;

use app\components\Ui;

use app\components\Criteria;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/** Ported from protected/models/Item.php (Yii 1). */
class Item extends ActiveRecord
{
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

    /** GxActiveRecord::__toString(): the representing column, or the id. */
    public function __toString()
    {
        $value = $this->hasAttribute('title') ? $this->title : null;

        return (string) ($value === null || $value === '' ? $this->id : $value);
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
            Yii::warning( var_export( $stock ), '$stock');
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
            ItemVendor::model()->deleteAllByAttributes(['item_detail_id'=>$id]);
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
            Yii::warning( var_export( $this ), '$mrs');
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
}
