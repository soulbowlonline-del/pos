<?php
namespace app\models;

use app\components\Ui;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/** Ported from protected/models/ItemDetail.php (Yii 1). */
class ItemDetail extends ActiveRecord
{
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
        // Yii 1 reads this through PDO::ATTR_STRINGIFY_FETCHES, so an int
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

    /** GxActiveRecord::__toString(): the representing column, or the id. */
    public function __toString()
    {
        $value = $this->hasAttribute('bar_code') ? $this->bar_code : null;

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
}
