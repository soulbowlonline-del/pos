<?php
namespace app\models;

use yii\helpers\Html;

use app\components\Criteria;

use app\components\Ui;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/** Ported from protected/models/ItemDetail.php (Yii 1). */
class ItemDetail extends ActiveRecord
{
    // Yii 1 hands out column values as strings; the option helpers
    // compare them loosely and answer wrongly for an integer 0.
    use LegacyColumnTypes;

    // Declared on the Yii 1 model and not columns: the forms post
    // to these and the actions assign them.
    public $item_print_id;
    public $item_qty;
    public $company_id;
    public $expiry_date;
    public $packing_date;

    public $purchase_price;
    public $product_code;
    public $hsn_code;
    public const STATUS_INACTIVE = 1;
    public const IS_COMPANY = 1;
    public const IS_NOT_COMPANY = 0;
    public const STATUS_ACTIVE = 0;

    public static function tableName()
    {
        return '{{%item_detail}}';
    }

    /**
     * Yii 1 declares this relation with 'order' => 'create_time DESC' and the
     * caller reads [0], so it wants the newest adjustment. create_time has
     * duplicates in this table, which leaves that undefined, so id breaks the
     * tie on both stacks.
     */
    public function getStockAdjustLogs()
    {
        return $this->hasMany(StockAdjustLog::class, ['item_detail_id' => 'id'])
            ->orderBy(['create_time' => SORT_DESC, 'id' => SORT_DESC]);
    }

    public function getItem()
    {
        return $this->hasOne(Item::class, ['id' => 'item_id']);
    }

    public function getItemStock()
    {
        // An item detail has one stock row per batch. Neither framework
        // ordered this relation, so each picked whichever row the database
        // happened to return and the batch number on an order line was
        // effectively arbitrary - in practice the highest id, i.e. the most
        // recent batch. That is now explicit on both sides.
        return $this->hasOne(ItemStock::class, ['item_detail_id' => 'id'])
            ->orderBy(['id' => SORT_DESC]);
    }

    /**
     * Stock on hand: positive balances less the absolute value of negative ones.
     *
     * Reproduced from ItemDetail::getStockQty(), including its use of bcsub at
     * scale 3 - which returns a string, and that string is what the API has
     * always emitted for stock_qty.
     */
    public function getStockQty()
    {
        $add = '0.000';
        $sub = '0.000';

        $positive = ItemStock::find()
            ->where(['item_id' => $this->item_id])
            ->andWhere(['>', 'balance_qty', 0])
            ->andWhere(['is not', 'item_detail_id', null])
            ->orderBy(['id' => SORT_ASC])
            ->all();
        foreach ($positive as $stock) {
            $add = $add + $stock->balance_qty;
        }

        $negative = ItemStock::find()
            ->where(['item_id' => $this->item_id])
            ->andWhere(['<', 'balance_qty', 0])
            ->andWhere(['is not', 'item_detail_id', null])
            ->orderBy(['id' => SORT_ASC])
            ->all();
        foreach ($negative as $stock) {
            $sub = $sub + abs($stock->balance_qty);
        }

        return bcsub((string)$add, (string)$sub, 3);
    }

    /**
     * The tax row that applies, preferring the most recent purchase sale tax
     * over the item's own when they differ. From getItemDetailTax().
     */
    public function getItemDetailTax()
    {
        $saleTaxId = PurchaseBillDetail::getLatestSaleTaxIdByItemDetailId($this->id);
        $taxId = $this->tax_id;
        if ($saleTaxId && $saleTaxId != $this->tax_id) {
            $taxId = $saleTaxId;
        }
        return Tax::findOne(['id' => $taxId]);
    }

    /** Combined tax percentage across the four components. */
    public function getItemTaxPercent()
    {
        $tax = $this->getItemDetailTax();
        if (!$tax) {
            return 0;
        }
        return $tax->tax_val1 + $tax->tax_val2 + $tax->tax_val3 + $tax->tax_val4;
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

    /** Id of the tax row that applies, or 0. From getItemTax(). */
    public function getItemTax()
    {
        $tax = $this->getItemDetailTax();
        // Yii 1 returns the id as a string when a tax row is found and the
        // integer 0 when it is not; both are reproduced, since the payload
        // exposes this value directly.
        return $tax ? (string)$tax->id : 0;
    }

    private function itemRow()
    {
        return Item::findOne($this->item_id);
    }

    public function getCategoryTitle()
    {
        $item = $this->itemRow();
        return ($item && $item->category) ? $item->category->title : '';
    }

    public function getCompanyTitle()
    {
        $item = $this->itemRow();
        return ($item && $item->company) ? $item->company->title : '';
    }

    public function getSubcategoryTitle()
    {
        $item = $this->itemRow();
        return ($item && $item->subcategory) ? $item->subcategory->title : '';
    }

    /**
     * Payload from ItemDetail::toonlineArray() - the catalogue export shape.
     *
     * The odd key names (Short_x0020_Name, Pur_x0020_Price) are XML-encoded
     * spaces from whatever consumes this feed, and are reproduced exactly.
     *
     * Note 'Tax' is only present when a tax row is found, matching the Yii 1
     * version, which sets the key inside the if.
     */
    public function toOnlineApiArray()
    {
        $item = $this->item;

        $json = [];
        $json['Code'] = isset($item) ? $item->item_code : '';
        $json['Name'] = isset($item) ? $item->title : '';
        $json['Short_x0020_Name'] = isset($item) ? $item->short_name : '';
        $json['Barcode'] = $this->bar_code;

        $tax = Tax::findOne($this->getItemTax());
        if ($tax) {
            $json['Tax'] = $tax->title;
        }

        if ($this->mrp != '0.00') {
            $json['MRP'] = isset($this->mrp) ? $this->mrp : '';
        } else {
            $json['MRP'] = isset($item) ? $item->mrp : '';
        }

        $json['PRICE'] = isset($item) ? $item->sale_price : '';
        $json['OPStock'] = $this->getStockQty();
        $json['Pur_x0020_Price'] = isset($item) ? $item->purchase_price : '';
        $json['Pur_x0020_Value'] = isset($item) ? $item->purchase_price : '';
        $json['Weight'] = isset($item) ? $item->weight : '';
        $json['Department'] = isset($item) ? $this->getCategoryTitle() : '';
        $json['Company'] = isset($item) ? $this->getCompanyTitle() : '';
        $json['Sub_x0020_Category'] = isset($item) ? $this->getSubcategoryTitle() : '';
        $json['ProdEx1'] = '';
        $json['ProdEx2'] = '';
        $json['ProdEx3'] = '';
        $json['ProdEx4'] = '';
        $json['Active'] = self::getStatusOptions($this->status);

        return $json;
    }

    public function getItemDiscount()
    {
        return $this->hasOne(ItemDiscount::class, ['item_detail_id' => 'id']);
    }

    /** Sale price, preferring the item's over the detail's mrp. */
    public function getItemDetailSaleRate()
    {
        $mrp = $this->mrp;
        if (isset($this->item)) {
            $mrp = $this->item->sale_price;
        }
        return $mrp;
    }

    /** MRP, falling back to the item's when the detail has none. */
    public function getItemDetailMrp()
    {
        $mrp = $this->mrp;
        if ($mrp == '0.00' || $mrp === null) {
            if (isset($this->item)) {
                $mrp = $this->item->mrp;
            }
        }
        return $mrp;
    }

    /** Price excluding tax, rounded to 2dp. */
    public function getBasePrice($price = null)
    {
        $tax = $this->getItemTaxPercent();
        $base = $price !== null
            ? ($price * 100) / (100 + $tax)
            : ($this->getItemDetailSaleRate() * 100) / (100 + $tax);
        return round((float)str_replace(',', '', (string)$base), 2);
    }

    /**
     * The currently-active item discount, or null.
     *
     * A discount applies only when the item is flagged is_discount, a row links
     * it to a discount, that discount's date range covers today, and the current
     * datetime falls strictly between its start and end datetimes.
     */
    private function activeDiscount()
    {
        if (!$this->item || $this->item->is_discount != 1) {
            return null;
        }
        if (empty($this->itemDiscount)) {
            return null;
        }

        $today = date('Y-m-d');
        $now = date('Y-m-d H:i');

        $discount = Discount::find()
            ->where(['id' => $this->itemDiscount->discount_id])
            ->andWhere(['<=', 'start_date', $today])
            ->andWhere(['>=', 'end_date', $today])
            ->one();
        if (empty($discount)) {
            return null;
        }

        $start = $discount->start_date . ' ' . $discount->start_time;
        $end = $discount->end_date . ' ' . $discount->end_time;
        if (strtotime($now) > strtotime($start) && strtotime($now) < strtotime($end)) {
            return $discount;
        }
        return null;
    }

    /** Discount in currency; a percentage discount is applied to the base price. */
    public function getItemDiscountAmount($price = null)
    {
        $discount = $this->activeDiscount();
        if ($discount === null) {
            return 0;
        }
        $amount = $discount->amount;
        if ($discount->type_id == Discount::TYPE_PERCENTAGE) {
            $amount = $this->getBasePrice($price) * $amount / 100;
        }
        return $amount;
    }

    public function getItemTaxAmount($price = null)
    {
        $base = (float)str_replace(',', '', (string)$this->getBasePrice($price));
        return ($base - $this->getItemDiscountAmount($price)) * $this->getItemTaxPercent() / 100;
    }

    /**
     * CGST percentage. When tax_val1 is zero but tax_val4 (IGST) is not, half
     * the IGST rate is used instead.
     */
    public function getCgstPercent()
    {
        $tax = $this->getItemDetailTax();
        if (!$tax) {
            return 0;
        }
        $val = $tax->tax_val1;
        if ($val == '0.00' && $tax->tax_val4 != '0.00') {
            $val = $tax->tax_val4 / 2;
        }
        return $val;
    }

    /**
     * SGST percentage.
     *
     * Reads tax_val1, exactly as the Yii 1 version does - the same field CGST
     * uses, where CESS reads tax_val3. That looks like a copy-paste slip (one
     * would expect tax_val2), but it is what every SGST figure this API has
     * ever returned, so correcting it here would change tax output. Flagged
     * rather than fixed.
     */
    public function getSgstPercent()
    {
        $tax = $this->getItemDetailTax();
        if (!$tax) {
            return 0;
        }
        $val = $tax->tax_val1;
        if ($val == '0.00' && $tax->tax_val4 != '0.00') {
            $val = $tax->tax_val4 / 2;
        }
        return $val;
    }

    public function getCessPercent()
    {
        $tax = $this->getItemDetailTax();
        return $tax ? $tax->tax_val3 : 0;
    }

    /**
     * IGST percentage - always 0. The Yii 1 version has `$val = $tax->tax_val4;`
     * commented out and assigns 0 in its place, so IGST is effectively disabled
     * on this payload. Reproduced as-is.
     */
    public function getIgstPercent()
    {
        return 0;
    }

    public function getCgstAmount($price = null)
    {
        $base = (float)str_replace(',', '', (string)$this->getBasePrice($price));
        return ($base - $this->getItemDiscountAmount($price)) * $this->getCgstPercent() / 100;
    }

    public function getSgstAmount($price = null)
    {
        $base = (float)str_replace(',', '', (string)$this->getBasePrice($price));
        return ($base - $this->getItemDiscountAmount($price)) * $this->getSgstPercent() / 100;
    }

    public function getCessAmount($price = null)
    {
        $base = (float)str_replace(',', '', (string)$this->getBasePrice($price));
        return ($base - $this->getItemDiscountAmount($price)) * $this->getCessPercent() / 100;
    }

    public function getIgstAmount($price = null)
    {
        $base = (float)str_replace(',', '', (string)$this->getBasePrice($price));
        return ($base - $this->getItemDiscountAmount($price)) * $this->getIgstPercent() / 100;
    }

    /**
     * Line total, capped at the sale rate.
     *
     * number_format() is kept because it is what Yii 1 returns - a string, with
     * thousands separators - and the subsequent comparison against the sale
     * rate is therefore a string/number comparison. Changing either would
     * change the emitted value.
     */
    public function getTotalAmount($price = null)
    {
        $base = (float)str_replace(',', '', (string)$this->getBasePrice($price));
        // Note: the Yii 1 version calls getItemDiscountAmount() here with no
        // argument, unlike everywhere else in the class where $price is passed.
        $discountAmt = $this->getItemDiscountAmount();
        $tax = $this->getItemTaxAmount($price);

        $total = number_format((float)(($base - $discountAmt) + $tax), 2);
        $saleRate = $price !== null ? $price : $this->getItemDetailSaleRate();

        if ($total > $saleRate) {
            $total = $saleRate;
        }
        return $total;
    }

    /**
     * Payload from ItemDetail::toArray($price), key for key.
     *
     * The discount keys are seeded with defaults and only overwritten when an
     * active discount applies, which is why they appear twice in the source.
     */
    public function toApiArray($price = null)
    {
        $item = $this->item;

        $json = [];
        $json['item_id'] = (string)$this->id;
        $json['bar_code'] = $this->bar_code;
        $json['item_name'] = isset($item) ? $item->title : '';
        $json['hsn_code'] = isset($item) ? $item->hsn_code : '';
        $json['item_desc'] = isset($item) ? $item->short_name : '';
        $json['unit_name'] = isset($item) ? Item::getMeasurementTypeOptions($item->unit) : '';
        $json['is_coupon'] = isset($item) && $item->is_coupon !== null ? (string)$item->is_coupon : '';
        $json['box'] = 0;
        $json['qty'] = 1;
        $json['stock_qty'] = $this->getStockQty();
        $json['sale_rate'] = $price !== null ? $price : $this->getItemDetailSaleRate();
        $json['base_price'] = $this->getBasePrice($price);
        $json['mrp'] = $this->getItemDetailMrp();

        $json['batch_numbers'] = '';
        $itemStock = $this->itemStock;
        if (!empty($itemStock)) {
            $json['batch_numbers'] = $itemStock->batch_number;
        }

        $json['discount_id'] = 0;
        $json['discount_val'] = 0;
        $json['discount_type'] = 1;
        $json['discount_amt'] = 0;
        $json['tax_id'] = $this->getItemTax();
        $json['tax_percent'] = $this->getItemTaxPercent();
        $json['tax_amt'] = $this->getItemTaxAmount($price);

        $discount = $this->activeDiscount();
        if ($discount !== null) {
            $json['discount_val'] = $discount->amount;
            $json['discount_type'] = $discount->type_id;
            $json['discount_id'] = $discount->id;
            $json['discount_amt'] = $this->getItemDiscountAmount();
        }

        $json['total_amount'] = $this->getTotalAmount($price);
        $json['cgst_amt'] = $this->getCgstAmount($price);
        $json['sgst_amt'] = $this->getSgstAmount($price);
        $json['cess_amount'] = $this->getCessAmount($price);
        $json['igst_amount'] = $this->getIgstAmount();
        $json['cgst_per'] = $this->getCgstPercent();
        $json['sgst_per'] = $this->getSgstPercent();
        $json['cess_per'] = $this->getCessPercent();
        $json['igst_per'] = $this->getIgstPercent();

        $json['itemdetail_id'] = (string)$this->id;
        $json['original_item_id'] = $this->item_id === null ? null : (string)$this->item_id;

        return $json;
    }

    /**
     * Stock available for sale, from ItemDetail::checkStock().
     *
     * The first active detail row for an item is treated as always in stock and
     * returns the sentinel 2 without consulting tbl_item_stock at all; every
     * other row sums its own stock balances. Negative totals clamp to 0.
     * Callers only ever test "> 0", so the 2 is a flag rather than a quantity.
     */
    public function checkStock()
    {
        $remaining = 0;

        $detail = static::find()
            ->where(['id' => $this->id, 'status' => self::STATUS_ACTIVE])
            ->one();
        if (!$detail) {
            return 0;
        }

        $first = static::find()
            ->where(['item_id' => $detail->item_id, 'status' => self::STATUS_ACTIVE])
            ->orderBy(['id' => SORT_ASC])
            ->limit(1)
            ->one();

        if ($first && $first->id == $detail->id) {
            return 2;
        }

        foreach (ItemStock::findAll(['item_detail_id' => $detail->id]) as $stock) {
            $remaining += $stock->balance_qty;
        }
        return $remaining < 0 ? 0 : $remaining;
    }

    /**
     * Payload from ItemDetail::toOnlineOrderArray($order_item) - the line of an
     * online order that has no POS order behind it yet. Not to be confused with
     * toOnlineApiArray(), which is the product feed.
     *
     * Two things worth knowing about the original:
     *   - it declares $price = null and never assigns it, so both "if ($price
     *     != null)" branches are dead and every rate comes from the model.
     *     Kept as a plain call rather than reproducing the dead branch.
     *   - total_amount is qty * sale rate, formatted; tax and GST amounts are
     *     qty * the per-unit amount, left unformatted. That asymmetry is the
     *     Yii 1 behaviour and the client reads both.
     */
    public function toOnlineOrderApiArray($orderItem)
    {
        $qty = number_format($orderItem->qty, 2, '.', '');

        $out = [];
        $out['item_id'] = (string)$this->id;
        $out['bar_code'] = $this->bar_code;
        $out['item_name'] = $this->item ? $this->item->title : '';
        $out['item_desc'] = $this->item ? $this->item->short_name : '';
        $out['unit_name'] = $this->item ? Item::getMeasurementTypeOptions($this->item->unit) : '';
        // Yii 1 reads this through \PDO::ATTR_STRINGIFY_FETCHES, so an int
        // column comes back as a string; NULL stays NULL.
        $out['is_coupon'] = $this->item
            ? ($this->item->is_coupon === null ? null : (string)$this->item->is_coupon)
            : '';
        $out['box'] = 0;
        $out['qty'] = $qty;
        $out['stock_qty'] = $this->getStockQty();
        $out['total_remain'] = $this->item ? $this->item->getTotalRemainingQuantity() : '0.000';
        $out['sale_rate'] = $this->getItemDetailSaleRate();
        $out['base_price'] = $this->getBasePrice();
        $out['mrp'] = $this->getItemDetailMrp();

        $out['batch_numbers'] = '';
        $itemStock = $this->itemStock;
        if (!empty($itemStock)) {
            $out['batch_numbers'] = $itemStock->batch_number;
        }

        $out['discount_id'] = 0;
        $out['discount_val'] = 0;
        $out['discount_type'] = 1;
        $out['discount_amt'] = 0;
        $out['tax_id'] = $this->getItemTax();
        $out['tax_percent'] = $this->getItemTaxPercent();
        $out['tax_amt'] = $qty * $this->getItemTaxAmount();

        $discount = $this->activeDiscountRow();
        if ($discount !== null) {
            $out['discount_val'] = $discount->amount;
            $out['discount_type'] = $discount->type_id;
            $out['discount_id'] = $discount->id;
            $out['discount_amt'] = $this->getItemDiscountAmount();
        }

        $out['total_amount'] = number_format($orderItem->qty * $this->getItemDetailSaleRate(), 2, '.', '');
        $out['cgst_amt'] = $qty * $this->getCgstAmount();
        $out['sgst_amt'] = $qty * $this->getSgstAmount();
        $out['cess_amount'] = $qty * $this->getCessAmount();
        $out['igst_amount'] = $qty * $this->getIgstAmount();
        $out['cgst_per'] = $this->getCgstPercent();
        $out['sgst_per'] = $this->getSgstPercent();
        $out['cess_per'] = $this->getCessPercent();
        $out['igst_per'] = $this->getIgstPercent();

        return $out;
    }

    /** activeDiscount() is private; this exposes it to the method above. */
    private function activeDiscountRow()
    {
        return $this->activeDiscount();
    }

    /**
     * Yii 1's calculateLockedStockQty(): the balance across rows already locked
     * by the caller's SELECT ... FOR UPDATE, so the figure cannot move between
     * reading it and writing the log line.
     */
    public function calculateLockedStockQty($rows)
    {
        $add = 0;
        $sub = 0;
        foreach ($rows as $row) {
            if ($row[0] > 0) {
                $add += $row[0];
            } else {
                $sub += abs($row[0]);
            }
        }
        return bcsub((string)$add, (string)$sub, 3);
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'ItemDetail' : 'ItemDetails';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'bar_code';
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
        $value = $this->hasAttribute('bar_code') ? $this->bar_code : null;

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

    public function getCreateUser()
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
    }

    public function getTax()
    {
        return $this->hasOne(Tax::class, ['id' => 'tax_id']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }

    public function getItemDiscounts()
    {
        return $this->hasMany(ItemDiscount::class, ['item_detail_id' => 'id']);
    }

    public function getItemStocks()
    {
        return $this->hasMany(ItemStock::class, ['item_detail_id' => 'id']);
    }

    public function getItemTaxes()
    {
        return $this->hasMany(ItemTax::class, ['item_detail_id' => 'id']);
    }

    public function getItemVendors()
    {
        return $this->hasMany(ItemVendor::class, ['item_detail_id' => 'id']);
    }

    public function getMrnDetails()
    {
        return $this->hasMany(MrnDetail::class, ['item_detail_id' => 'id']);
    }

    public function getMrsDetails()
    {
        return $this->hasMany(MrsDetail::class, ['item_detail_id' => 'id']);
    }

    public function getOrderHoldItems()
    {
        return $this->hasMany(OrderHoldItem::class, ['item_detail_id' => 'id']);
    }

    public function getOrderItems()
    {
        return $this->hasMany(OrderItem::class, ['item_detail_id' => 'id']);
    }

    public function getOrderRefundItems()
    {
        return $this->hasMany(OrderRefundItem::class, ['item_detail_id' => 'id']);
    }

    public function getPurchaseBillDetails()
    {
        return $this->hasMany(PurchaseBillDetail::class, ['item_detail_id' => 'id']);
    }

    public function getPurchaseOrderDetails()
    {
        return $this->hasMany(PurchaseOrderDetail::class, ['item_detail_id' => 'id']);
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
            'item_id' => 'Item',
            'company_id' => 'Company',
            'bar_code' => 'Bar Code',
            'open_stock_qty' => 'Opening Stock',
            'reorder_qty' => 'Reorder Qty',
            'status' => 'Status',
            'type_id' => 'Type',
            'mrp' => 'MRP',
            'create_time' => 'Create Time',
            'expiry_date' => 'Expiry Date',
            'packing_date' => 'Packing Date',
            'item_print_id' => 'Item',
            'item_qty' => 'Quantity',
            'tax_id' => 'Tax',
            'create_user_id' => 'User',
            'updated_by' => 'User',
            'createUser' => 'User',
            'item' => 'Item',
            'tax' => 'Tax',
            'updatedBy' => 'User',
            'itemDiscounts' => 'ItemDiscounts',
            'itemStocks' => 'ItemStocks',
            'itemTaxes' => 'ItemTaxes',
            'itemVendors' => 'ItemVendors',
            'mrnDetails' => 'MrnDetails',
            'mrsDetails' => 'MrsDetails',
            'orderHoldItems' => 'OrderHoldItems',
            'orderItems' => 'OrderItems',
            'orderRefundItems' => 'OrderRefundItems',
            'purchaseBillDetails' => 'PurchaseBillDetails',
            'purchaseOrderDetails' => 'PurchaseOrderDetails',
            'stockAdjustLogs' => 'StockAdjustLogs',
        ];
    }

    public function toonlineArray() {
            $model = $this;
            $json_entry = null;
            if ($model) {

                $batch_no =  [];
                $default_img = 'default.png';
                $json_entry = [];
                $json_entry ['Code'] = isset($model->item)?$model->item->item_code:"";
                $json_entry ['Name'] = isset($model->item)?$model->item->title:"";
                $json_entry ['Short_x0020_Name'] = isset($model->item)?$model->item->short_name:"";
                $json_entry ['Barcode'] = $model->bar_code;
                $tax_id = $model->getItemTax();
                $tax = Tax::findOne($tax_id);
                if($tax){
                $json_entry ['Tax'] = $tax->title;
                }
                if($model->mrp != '0.00'){
                    $json_entry ['MRP'] = isset($model->mrp)?$model->mrp:"";
                }else{
                $json_entry ['MRP'] = isset($model->item)?$model->item->mrp:"";
                }
                $json_entry ['PRICE'] = isset($model->item)?$model->item->sale_price:"";
                $json_entry ['OPStock'] = $model->getStockQty();
                $json_entry ['Pur_x0020_Price'] = isset($model->item)?$model->item->purchase_price:"";
                $json_entry ['Pur_x0020_Value'] = isset($model->item)?$model->item->purchase_price:"";
                $json_entry ['Weight'] = isset($model->item)?$model->item->weight:"";
                $json_entry ['Department'] = isset($model->item)?$model->getCategory():"";
                $json_entry ['Company'] = isset($model->item)?$model->getCompany():"";
                $json_entry ['Sub_x0020_Category'] = isset($model->item)?$model->getSubcategory():"";
                $json_entry ['ProdEx1'] = '';
                $json_entry ['ProdEx2'] = '';
                $json_entry ['ProdEx3'] = '';
                $json_entry ['ProdEx4'] = '';
                $json_entry ['Active'] = $model->getStatusOptions($model->status);

            }
            return $json_entry;
        }

    public function getCategory(){
            $title = '';
            $item = Item::findOne($this->item_id);
            if($item){
                if($item->category){
                    $title = $item->category->title;
                }
            }
            return $title;
        }

    public function getCompany(){
            $title = '';
            $item = Item::findOne($this->item_id);
            if($item){
                if($item->company){
                    $title = $item->company->title;
                }
            }
            return $title;
        }

    public function getSubcategory(){
            $title = '';
            $item = Item::findOne($this->item_id);
            if($item){
                if($item->subcategory){
                    $title = $item->subcategory->title;
                }
            }
            return $title;
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

    public function getItemPrintDetails() {
            $bill_detail_ids = [];
            $list = [];
            if (isset ( Yii::$app->session ['idList'] ) && (Yii::$app->session ['idList'] != '')) {
                $query = ItemDetail::find();
                $query->andWhere(['id' => Yii::$app->session ['idList']]);
                $itemdetails= $query->all();
                if ($itemdetails) {
                    foreach ( $itemdetails as $itemdetail ) {
                        $list [$itemdetail->id] = isset ( $itemdetail->item ) ? $itemdetail->bar_code.'('.$itemdetail->item.')' : "";
                    }
                }
            }
            return $list;
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

    public function toArray2() {
            $model = $this;
            $json_entry = null;
            if ($model) {

                $batch_no =  [];
                $default_img = 'default.png';
                $json_entry = [];
                $json_entry ['item_id'] = $model->id;
                $json_entry ['bar_code'] = $model->bar_code;
                $json_entry ['item_name'] = isset($model->item)?$model->item->title:"";
                $json_entry ['item_desc'] = isset($model->item)?$model->item->short_name:"";
                //  $json_entry ['unit_id'] = isset($model->item)?$model->item->unit:"";
                $json_entry ['unit_name'] = isset($model->item)?$model->item->getMeasurementTypeOptions($model->item->unit):"";
                $json_entry ['is_coupon'] = isset($model->item)?$model->item->is_coupon:"";
                $json_entry ['is_return'] = false;

                $json_entry ['qty'] = 1;
                $json_entry ['stock_qty'] = $model->getStockQty();
                $json_entry ['sale_rate'] = $model->getItemDetailSaleRate();
                $json_entry ['base_price'] = $model->getBasePrice();
                $json_entry ['mrp'] = $model->getItemDetailMrp();

                $json_entry ['batch_numbers'] = '';
                $item_stock = $model->itemStock;
                if(!empty($item_stock))
                {

                    $batch_no = $item_stock->batch_number;
                    $json_entry ['batch_numbers'] =$batch_no;
                }
                $json_entry ['discount_id'] = 0;
                $json_entry ['discount_val'] = 0;
                $json_entry ['discount_type'] = 1;
                $json_entry ['discount_amt'] = 0;
                $json_entry ['tax_id'] = $model->getItemTax();
                $json_entry ['tax_percent'] = $model->getItemTaxPercent();
                $json_entry ['tax_amt'] = $model->getItemTaxAmount();


                if($model->item)
                {
                    $check_discount = $model->item->is_discount;
                    if($check_discount == 1)
                    {
                        if(!empty($model->itemDiscount))
                        {
                            $discount_id = $model->itemDiscount->discount_id;


                            $current_date = date('Y-m-d');
                            $current_time = date('H:i');

                            $complete_date =  date('Y-m-d H:i');

                            $query = Discount::find();

                            $query->andWhere('id =' .$discount_id);

                            $query->andWhere("start_date  <= '$current_date' AND end_date >= '$current_date'");
                            //    $criteria_even->condition = "start_time  >= '$current_time' AND end_time <= '$current_time'";
                            $discount_data = $query->one();
                            if(!empty($discount_data))
                            {

                                $start_datetime = $discount_data->start_date .' ' . $discount_data->start_time;
                                $end_datetime = $discount_data->end_date .' ' . $discount_data->end_time;

                                if ((strtotime($complete_date) > strtotime($start_datetime)) && (strtotime($complete_date) < strtotime($end_datetime)))
                                {
                                    $json_entry ['discount_val'] = $discount_data->amount;
                                    $json_entry ['discount_type'] = $discount_data->type_id;
                                    $json_entry ['discount_id'] = $discount_data->id;
                                    $json_entry ['discount_amt'] = $this->getItemDiscountAmount();
                                }



                            }
                        }
                    }
                }
                $json_entry ['total_amount'] = $this->getTotalAmount();
                $json_entry ['cgst_amt'] = $model->getCgstAmount();
                $json_entry ['sgst_amt'] = $model->getSgstAmount();
                $json_entry ['cess_amount'] = $model->getCessAmount();
                $json_entry ['igst_amount'] = $model->getIgstAmount();
                /* $json_entry ['id'] = $model->id;
                    $json_entry ['bar_code'] = isset ( $model->bar_code ) ? $model->bar_code : '';
                    $json_entry ['open_stock_qty'] = isset ( $model->open_stock_qty ) ? $model->open_stock_qty : '';
                    $json_entry ['reorder_qty'] = isset ( $model->reorder_qty ) ? $model->reorder_qty : '';
                    $json_entry ['outlet'] =  isset ( $model->outlet ) ? $model->outlet->title : '';
                    $json_entry ['tax'] = isset ( $model->tax ) ? $model->tax->title : '';
                    $json_entry ['create_username'] = isset ( $model->createUser ) ? $model->createUser->full_name : '';
                    $json_entry ['create_user_id'] = isset ( $model->create_user_id ) ? $model->create_user_id : '';
                    $json_entry ['create_time'] = isset ( $model->create_time ) ? $model->create_time : '';

                    if(isset($model->item))
                    {

                    $json_entry ['item'] = $model->item->toArray();

                    }  */

            }
            return $json_entry;
        }

    public function toArray1($id,$state,$box=1) {
            if($state == 1){
            $orderitem = OrderHoldItem::findOne($id);
            }else{
                $orderitem = OrderItem::findOne($id);
            }

            $model = $this;
            $json_entry = null;
            if ($model) {

                $batch_no =  [];
                $default_img = 'default.png';
                $json_entry = [];
                $json_entry ['item_id'] = $model->id;
                $json_entry ['bar_code'] = $model->bar_code;
                $json_entry ['item_name'] = isset($model->item)?$model->item->title:"";
                $json_entry ['item_desc'] = isset($model->item)?$model->item->short_name:"";
                //  $json_entry ['unit_id'] = isset($model->item)?$model->item->unit:"";
                $json_entry ['unit_name'] = isset($model->item)?$model->item->getMeasurementTypeOptions($model->item->unit):"";
                $json_entry ['is_coupon'] = isset($model->item)?$model->item->is_coupon:"";
                if($box == 0){
                $json_entry ['box'] = 0;
                }else{
                    $json_entry ['is_return'] = 0;
                }
                if($orderitem){
                    /* $qty = $orderitem->qty;
                    if($state == 2){
                        $orderrefund = OrderRefund::findOne(array('order_id'=>$orderitem->order_id));
                        if($orderrefund){
                            $orderrefunditem = OrderRefundItem::findOne(array('item_detail_id'=>$model->id,
                                    'item_id'=>$model->item_id,'order_refund_id'=>$orderrefund->id,
                            ));
                            if($orderrefunditem){
                                $qty = $qty - $orderrefunditem->qty;
                            }
                        }
                    } */
                $json_entry ['qty'] = $orderitem->qty;
                }
                else{
                    $json_entry ['qty'] = 1;
                }
                $json_entry ['stock_qty'] = $model->getStockQty();
                /* if($orderitem){
                    $item_mrp  = isset($model->item)?$model->item->mrp:"0";
                    $orderprice = number_format($orderitem->price,2);
                    $ordertax = number_format($orderitem->tax_amount,2);
                    $sale_after = round($orderprice + $ordertax);
                    if($sale_after >$item_mrp){
                        $sale_after = $item_mrp;
                    }
                    $json_entry ['sale_rate'] = $sale_after;
                }
                else{ */
                    $json_entry ['sale_rate'] = $model->getItemDetailSaleRate();
                /* } */

                if($orderitem){
                    $json_entry ['base_price'] = $orderitem->price;
                }
                else{
                    $json_entry ['base_price'] = $model->getBasePrice();
                }

                $json_entry ['mrp'] = $model->getItemDetailMrp();

                $json_entry ['batch_numbers'] = '';
                $item_stock = $model->itemStock;
                if(!empty($item_stock))
                {

                    $batch_no = $item_stock->batch_number;
                    $json_entry ['batch_numbers'] =$batch_no;
                }
                if($orderitem){
                    $json_entry ['discount_id'] = $orderitem->discount_id;
                    $json_entry ['discount_val'] = isset($orderitem->discount)?$orderitem->discount->amount:"0";
                    $json_entry ['discount_type'] = isset($orderitem->discount)?$orderitem->discount->type_id:"1";
                    $json_entry ['discount_amt'] = $orderitem->discount_amt;
                    $json_entry ['tax_id'] = $orderitem->tax_id;
                    $json_entry ['tax_percent'] = $model->getItemTaxPercent();
                    $json_entry ['tax_amt'] = $orderitem->tax_amount;
                //    $baseprice = $model->getBasePrice() - ($model->getBasePrice()*$json_entry ['discount_val']/100);
                    $baseprice = ($orderitem->price) - ($orderitem->discount_amt);
                    $total = $baseprice + $orderitem->tax_amount;
                    $total= number_format((float)$total,2);
                    $json_entry ['total_amount'] = $total;
                    $json_entry ['cgst_amt'] = $orderitem->getCgstAmount();
                    $json_entry ['sgst_amt'] = $orderitem->getSgstAmount();
                    $json_entry ['cess_amount'] = $orderitem->getCessAmount();
                    $json_entry ['igst_amount'] = $orderitem->getIgstAmount();
                    $json_entry ['cgst_per'] = $model->getCgstPercent();
                    $json_entry ['sgst_per'] = $model->getSgstPercent();
                    $json_entry ['cess_per'] = $model->getCessPercent();
                    $json_entry ['igst_per'] = $model->getIgstPercent();
                }
                else{
                    if($model->item)
                    {
                        $check_discount = $model->item->is_discount;
                        if($check_discount == 1)
                        {
                            if(!empty($model->itemDiscount))
                            {
                                $discount_id = $model->itemDiscount->discount_id;


                                $current_date = date('Y-m-d');
                                $current_time = date('H:i');

                                $complete_date =  date('Y-m-d H:i');

                                $query = Discount::find();

                                $query->andWhere('id =' .$discount_id);

                                $query->andWhere("start_date  <= '$current_date' AND end_date >= '$current_date'");
                                //    $criteria_even->condition = "start_time  >= '$current_time' AND end_time <= '$current_time'";
                                $discount_data = $query->one();
                                if(!empty($discount_data))
                                {

                                    $start_datetime = $discount_data->start_date .' ' . $discount_data->start_time;
                                    $end_datetime = $discount_data->end_date .' ' . $discount_data->end_time;

                                    if ((strtotime($complete_date) > strtotime($start_datetime)) && (strtotime($complete_date) < strtotime($end_datetime)))
                                    {
                                        $json_entry ['discount_val'] = $discount_data->amount;
                                        $json_entry ['discount_type'] = $discount_data->type_id;
                                        $json_entry ['discount_id'] = $discount_data->id;
                                        $json_entry ['discount_amt'] = $this->getItemDiscountAmount();
                                    }



                                }
                            }
                        }
                    }
                    $json_entry ['tax_id'] = $model->getItemTax();
                    $json_entry ['tax_percent'] = $model->getItemTaxPercent();

                    $json_entry ['tax_amt'] = $model->getItemTaxAmount();

                    $json_entry ['cgst_amt'] = $model->getCgstAmount();
                    $json_entry ['sgst_amt'] = $model->getSgstAmount();
                    $json_entry ['cess_amount'] = $model->getCessAmount();
                    $json_entry ['igst_amount'] = $model->getIgstAmount();
                    $json_entry ['cgst_per'] = $model->getCgstPercent();
                    $json_entry ['sgst_per'] = $model->getSgstPercent();
                    $json_entry ['cess_per'] = $model->getCessPercent();
                    $json_entry ['igst_per'] = $model->getIgstPercent();
                }







                /* $json_entry ['id'] = $model->id;
                    $json_entry ['bar_code'] = isset ( $model->bar_code ) ? $model->bar_code : '';
                    $json_entry ['open_stock_qty'] = isset ( $model->open_stock_qty ) ? $model->open_stock_qty : '';
                    $json_entry ['reorder_qty'] = isset ( $model->reorder_qty ) ? $model->reorder_qty : '';
                    $json_entry ['outlet'] =  isset ( $model->outlet ) ? $model->outlet->title : '';
                    $json_entry ['tax'] = isset ( $model->tax ) ? $model->tax->title : '';
                    $json_entry ['create_username'] = isset ( $model->createUser ) ? $model->createUser->full_name : '';
                    $json_entry ['create_user_id'] = isset ( $model->create_user_id ) ? $model->create_user_id : '';
                    $json_entry ['create_time'] = isset ( $model->create_time ) ? $model->create_time : '';

                    if(isset($model->item))
                    {

                    $json_entry ['item'] = $model->item->toArray();

                    }  */

            }
            return $json_entry;
        }

    public function toOnlineOrderArray($order_item) {
            $price = null;
            $model = $this;
            $json_entry = null;
            if ($model) {

                $batch_no =  [];
                $default_img = 'default.png';
                $json_entry = [];
                $json_entry ['item_id'] = $model->id;
                $json_entry ['bar_code'] = $model->bar_code;
                $json_entry ['item_name'] = isset($model->item)?$model->item->title:"";
                $json_entry ['item_desc'] = isset($model->item)?$model->item->short_name:"";
                //  $json_entry ['unit_id'] = isset($model->item)?$model->item->unit:"";
                $json_entry ['unit_name'] = isset($model->item)?$model->item->getMeasurementTypeOptions($model->item->unit):"";
                $json_entry ['is_coupon'] = isset($model->item)?$model->item->is_coupon:"";
                $json_entry ['box'] = 0;
                $json_entry ['qty'] = number_format($order_item->qty,'2','.','');
                $json_entry ['stock_qty'] = $model->getStockQty();
                $json_entry ['total_remain'] = isset($model->item)?$model->item->getTotalRemainingQuantity():"0.000";

                if($price != null){
                    $json_entry ['sale_rate'] = $price;
                }else{
                    $json_entry ['sale_rate'] = $model->getItemDetailSaleRate();
                }
                if($price != null){
                    $json_entry ['base_price'] = $model->getBasePrice($price);

                }else{
                    $json_entry ['base_price'] = $model->getBasePrice();
                }

                $json_entry ['mrp'] = $model->getItemDetailMrp();

                $json_entry ['batch_numbers'] = '';
                $item_stock = $model->itemStock;
                if(!empty($item_stock))
                {

                    $batch_no = $item_stock->batch_number;
                    $json_entry ['batch_numbers'] =$batch_no;
                }
                $json_entry ['discount_id'] = 0;
                $json_entry ['discount_val'] = 0;
                $json_entry ['discount_type'] = 1;
                $json_entry ['discount_amt'] = 0;
                $json_entry ['tax_id'] = $model->getItemTax();
                $qty =  number_format($order_item->qty,'2','.','');
                $json_entry ['tax_percent'] = $model->getItemTaxPercent();
                $json_entry ['tax_amt'] = $qty * $model->getItemTaxAmount($price);


                if($model->item)
                {
                    $check_discount = $model->item->is_discount;
                    if($check_discount == 1)
                    {
                        if(!empty($model->itemDiscount))
                        {
                            $discount_id = $model->itemDiscount->discount_id;


                            $current_date = date('Y-m-d');
                            $current_time = date('H:i');

                            $complete_date =  date('Y-m-d H:i');

                            $query = Discount::find();

                            $query->andWhere('id =' .$discount_id);

                            $query->andWhere("start_date  <= '$current_date' AND end_date >= '$current_date'");
                            //    $criteria_even->condition = "start_time  >= '$current_time' AND end_time <= '$current_time'";
                            $discount_data = $query->one();
                            if(!empty($discount_data))
                            {

                                $start_datetime = $discount_data->start_date .' ' . $discount_data->start_time;
                                $end_datetime = $discount_data->end_date .' ' . $discount_data->end_time;

                                if ((strtotime($complete_date) > strtotime($start_datetime)) && (strtotime($complete_date) < strtotime($end_datetime)))
                                {
                                    $json_entry ['discount_val'] = $discount_data->amount;
                                    $json_entry ['discount_type'] = $discount_data->type_id;
                                    $json_entry ['discount_id'] = $discount_data->id;
                                    $json_entry ['discount_amt'] = $this->getItemDiscountAmount();
                                }



                            }
                        }
                    }
                }
                $total_amount = $order_item->qty * $model->getItemDetailSaleRate();
                $json_entry ['total_amount'] = number_format($total_amount,'2','.','');
                $json_entry ['cgst_amt'] = $qty * $model->getCgstAmount($price);
                $json_entry ['sgst_amt'] = $qty * $model->getSgstAmount($price);
                $json_entry ['cess_amount'] = $qty * $model->getCessAmount($price);
                $json_entry ['igst_amount'] = $qty * $model->getIgstAmount();
                $json_entry ['cgst_per'] = $model->getCgstPercent();
                $json_entry ['sgst_per'] = $model->getSgstPercent();
                $json_entry ['cess_per'] = $model->getCessPercent();
                $json_entry ['igst_per'] = $model->getIgstPercent();

                /* $json_entry ['id'] = $model->id;
                    $json_entry ['bar_code'] = isset ( $model->bar_code ) ? $model->bar_code : '';
                    $json_entry ['open_stock_qty'] = isset ( $model->open_stock_qty ) ? $model->open_stock_qty : '';
                    $json_entry ['reorder_qty'] = isset ( $model->reorder_qty ) ? $model->reorder_qty : '';
                    $json_entry ['outlet'] =  isset ( $model->outlet ) ? $model->outlet->title : '';
                    $json_entry ['tax'] = isset ( $model->tax ) ? $model->tax->title : '';
                    $json_entry ['create_username'] = isset ( $model->createUser ) ? $model->createUser->full_name : '';
                    $json_entry ['create_user_id'] = isset ( $model->create_user_id ) ? $model->create_user_id : '';
                    $json_entry ['create_time'] = isset ( $model->create_time ) ? $model->create_time : '';

                    if(isset($model->item))
                    {

                    $json_entry ['item'] = $model->item->toArray();

                    }  */

            }
            return $json_entry;
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
            [['hsn_code', 'product_code', 'purchase_price'], 'safe'],  // form-only, declared on the Yii 1 model
            [['item_print_id', 'item_qty', 'company_id', 'expiry_date', 'packing_date'], 'safe'],  // form-only, declared on the Yii 1 model
            [['bar_code', 'mrp', 'open_stock_qty', 'outlet_id', 'create_time', 'create_user_id'], 'required'],
            [['item_id', 'status', 'type_id', 'tax_id', 'create_user_id', 'updated_by'], 'integer'],
            [['bar_code'], 'string', 'max' => 255],
            [['bar_code'], 'unique'],
            [['company_bar_code', 'update_time', 'outlet_id', 'mrp', 'company_id', 'packing_date', 'expiry_date', 'company_bar_code', 'item_qty', 'item_print_id'], 'safe'],
            [['item_id', 'status', 'type_id', 'tax_id', 'updated_by'], 'default', 'value' => null],
            [['hsn_code', 'product_code', 'purchase_price', 'mrp', 'id', 'item_id', 'bar_code', 'open_stock_qty', 'reorder_qty', 'status', 'type_id', 'create_time', 'tax_id', 'create_user_id', 'updated_by'], 'safe', 'on' => 'search'],
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
            'pagination' => ['pageSize' => 10],
        ]);

        if (self::listingOrder()) {
            $query->orderBy(self::listingOrder());
        }

        $this->load($params, $this->formName());

        foreach ([['id', 'id'], ['open_stock_qty', 'open_stock_qty'], ['mrp', 'mrp'], ['reorder_qty', 'reorder_qty'], ['status', 'status'], ['type_id', 'type_id'], ['tax_id', 'tax_id'], ['create_user_id', 'create_user_id'], ['updated_by', 'updated_by']] as [$col, $attr]) {
            Criteria::compare($query, $col, $this->$attr);
        }
        foreach ([['bar_code', 'bar_code'], ['create_time', 'create_time']] as [$col, $attr]) {
            Criteria::compare($query, $col, $this->$attr, true);
        }

        return $provider;
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
     * Yii 1's adminsearch(): a listing of its own, converted as written.
     */
    public function adminsearch()
    {

		$item_ids = [];
		$query = ItemDetail::find()->alias('t');
		$item_list_ids = [];
	
		$match_purchase_price = null;
		$match_mrp = null;
		$match_item_id = null;
		$match_hsn_code = null;
		$match_product_code = null;
	
		if ($this->item_id != null) {
			Yii::warning( var_export($this->item_id, true), '$this->item_id');
				$query2 = Item::find()->alias('t');
					
				$query2->andWhere("title LIKE :title", [
				':title' => trim (  $this->item_id ) . '%'
				]);
				
					
				//$criteria2->compare ( 'title', $this->item_id );
				$getitem = $query2->one();
				if($getitem){
				$query1 = ItemDetail::find()->alias('t');
				$query1->orderBy(['id' => SORT_ASC]);
				$query1->andWhere('item_id ='.$getitem->id);
				$itemDetail = $query1->one();
				if($itemDetail){
				$query->andWhere('id !='. $itemDetail->id);
				}
				} 
			$match_item_id = $this->item_id;
			Yii::warning( var_export($match_item_id, true), '$match_item_id');
		}
		if ($this->mrp != null) {
			$match_mrp = $this->mrp;
		}
	
		if ($this->purchase_price != null) {
			$match_purchase_price = $this->purchase_price;
		}
		if ($this->hsn_code != null) {
			$match_hsn_code = $this->hsn_code;
		}
	
		if ($this->product_code != null) {
			$match_product_code = $this->product_code;
		}
		if ($this->company_id != null) {
			$match_company_id = $this->company_id;
		}else{
			$match_company_id = null;
		}
	
	
		$is_vendor = 0;
		$query->orderBy(['t.id' => SORT_DESC]);
		Criteria::compare($query, 'id', $this->id);
	
		$role = UserRole::find()->where([
				'title' => 'Vendor'
		])->orderBy(['id' => SORT_DESC])->one();
		$user = Yii::$app->user->model;
		if ($user->role_id == $role->id) {
			$is_vendor = 1;
		}
	
		if($match_item_id != null || $match_mrp !=null ||$match_hsn_code !=null || $match_product_code != null ||
				$match_purchase_price != null || $match_company_id !=null || $is_vendor == 1	)
		{
			$item_ids = $this->getItemOptionIdsInBarcode ($match_item_id ,$match_mrp,$match_hsn_code,$match_product_code,
					$match_purchase_price,$match_company_id,$is_vendor);
			$query->andWhere(['item_id' => $item_ids]);
		}
			
	
		Criteria::compare($query, 'bar_code', $this->bar_code, true);
		Criteria::compare($query, 'open_stock_qty', $this->open_stock_qty);
		//$criteria->compare ( 'mrp', $this->mrp );
		Criteria::compare($query, 'reorder_qty', $this->reorder_qty);
		Criteria::compare($query, 'status', $this->status);
		Criteria::compare($query, 'type_id', $this->type_id);
		Criteria::compare($query, 'create_time', $this->create_time, true);
		Criteria::compare($query, 'tax_id', $this->tax_id);
		Criteria::compare($query, 'create_user_id', $this->create_user_id);
		Criteria::compare($query, 'updated_by', $this->updated_by);
	
		$query->orderBy(['t.id' => SORT_DESC]);

		return new ActiveDataProvider([
		    'query' => $query,
		    'sort' => ['defaultOrder' => []],
		    'pagination' => ['pageSize' => 10],
		]);
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

    public static function getItemBarcode($valueArray) {
            $elementId = $valueArray ['itemId'] . "_bcode"; /* the div element id */
            $value = $valueArray ['barocde'];
            $type = 'code128'; /* you can set the type dynamically if you want valueArray eg - $valueArray['type'] */

            self::getBarcode ( [
                    'elementId' => $elementId,
                    'value' => $value,
                    'type' => $type
            ] );
            return \yii\helpers\Html::tag('div', '', [
                    'id' => $elementId
            ]);
        }

    public static function getBarcode($optionsArray) {
            Yii::$app->getController ()->widget ( 'application.extensions.Yii-Barcode-Generator.Barcode', $optionsArray );
        }
}
