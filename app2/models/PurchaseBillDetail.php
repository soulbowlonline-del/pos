<?php
namespace app\models;

use app\components\Criteria;

use app\components\Ui;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/**
 * Minimal Yii 2 PurchaseBillDetail.
 *
 * Only the sale-tax lookup the item payload needs is ported. The Yii 1 model is
 * 670 lines and its toArray1() drives the Tally purchase reports through eleven
 * tax arithmetic helpers; those move with the purchase and billing module.
 */
class PurchaseBillDetail extends ActiveRecord
{
    // Yii 1 hands out column values as strings; the option helpers
    // compare them loosely and answer wrongly for an integer 0.
    use LegacyColumnTypes;

    // Declared on the Yii 1 model and not columns: the forms post
    // to these and the actions assign them.
    public $expiry_date;
    public $packing_date;
    public $columns;
    public $start_date;
    public $tally_start_date;
    public $tally_end_date;
    public $bill_date;
    public $bill_no;
    public $vendor_id;
    public $bar_code;
    public $item_val_id;

    public const STATUS_APPROVED = 2;
    public const STATUS_RECEIVED = 1;
    public const STATUS_PENDING = 0;
    public static function tableName()
    {
        return '{{%purchase_bill_detail}}';
    }

    /** Latest sale_tax_id recorded against an item detail, or 0. */
    public static function getLatestSaleTaxIdByItemDetailId($itemDetailId)
    {
        $detail = static::find()
            ->where(['item_detail_id' => $itemDetailId])
            ->orderBy(['id' => SORT_DESC])
            ->limit(1)
            ->one();
        return $detail ? $detail->sale_tax_id : 0;
    }

    public function getItem()
    {
        return $this->hasOne(Item::class, ['id' => 'item_id']);
    }

    public function getPurchaseBill()
    {
        return $this->hasOne(PurchaseBill::class, ['id' => 'purchase_bill_id']);
    }

    public function getItemDetail()
    {
        return $this->hasOne(ItemDetail::class, ['id' => 'item_detail_id']);
    }

    public function getTax()
    {
        return $this->hasOne(Tax::class, ['id' => 'tax_id']);
    }

    /** All lines of this bill sharing this line's tax id. */
    private function siblings()
    {
        return static::findAll([
            'purchase_bill_id' => $this->purchase_bill_id,
            'tax_id' => $this->tax_id,
        ]);
    }

    /**
     * Whether the bill is intra-state, i.e. CGST/SGST rather than IGST.
     *
     * As in ItemReturnItem, the vendor is dereferenced without a null check;
     * left as-is rather than defaulting silently to a different tax treatment.
     */
    public function getGstTrue($billId)
    {
        if (!$billId) {
            return true;
        }
        $bill = PurchaseBill::findOne(['id' => $billId]);
        if (!$bill) {
            return true;
        }
        $outlet = Outlet::findOne($bill->outlet_id);
        if (!$outlet) {
            return true;
        }
        $vendor = Vendor::findOne($bill->vendor_id);
        return $vendor->state_id != $outlet->state_id ? false : true;
    }

    /**
     * A tax column, zeroed when it does not apply.
     *
     * CESS is the exception: it is returned whichever way the bill falls.
     */
    public function getTaxPercentage($col)
    {
        $checkGst = !($col === 'igst_per' || $col === 'igst_amt');
        if ($this->getGstTrue($this->purchase_bill_id) == $checkGst) {
            return $this->$col;
        }
        if ($col === 'cess_per' || $col === 'cess_amt') {
            return $this->$col;
        }
        return '0.00';
    }

    /**
     * Combined GST percentage.
     *
     * The Yii 1 version reads all four components and then returns only
     * cgst + sgst - cess and igst are computed and discarded. Reproduced,
     * because the returned figure is what the Tally reports carry.
     */
    public function getTotalGstPer()
    {
        return $this->getTaxPercentage('cgst_per') + $this->getTaxPercentage('sgst_per');
    }

    /** Same shape as getTotalGstPer: four read, two summed. */
    public function getTotalGstAmt()
    {
        $total = 0;
        foreach ($this->siblings() as $detail) {
            $total += $detail->getTaxPercentage('cgst_amt') + $detail->getTaxPercentage('sgst_amt');
        }
        return $total;
    }

    /** Line amounts for this tax group, net of the tax components. */
    public function getBasicAmount()
    {
        $amount = 0;
        foreach ($this->siblings() as $detail) {
            $amount += $detail->amount
                - ($detail->getTaxPercentage('cgst_amt')
                 + $detail->getTaxPercentage('sgst_amt')
                 + $detail->getTaxPercentage('cess_amt')
                 + $detail->getTaxPercentage('igst_amt'));
        }
        return $amount;
    }

    /**
     * Always '0'.
     *
     * The Yii 1 version sums discount_amt across the tax group and then returns
     * the literal string '0', discarding it. The loop has no other effect, so
     * only the return value is reproduced - but the behaviour is the constant,
     * not the sum, and anything relying on this column is reading a zero.
     */
    public function getMainDiscount()
    {
        return '0';
    }

    public function getCgstAmount()
    {
        $amount = 0;
        foreach ($this->siblings() as $detail) {
            $amount += $detail->getTaxPercentage('cgst_amt');
        }
        return $amount;
    }

    public function getSgstAmount()
    {
        $amount = 0;
        foreach ($this->siblings() as $detail) {
            $amount += $detail->getTaxPercentage('sgst_amt');
        }
        return $amount;
    }

    public function getCessAmount()
    {
        $amount = 0;
        foreach ($this->siblings() as $detail) {
            $amount += $detail->getTaxPercentage('cess_amt');
        }
        return $amount;
    }

    /**
     * IGST for the tax group.
     *
     * Tax ids 2, 3, 16, 17 and 18 are special-cased to the bill's own
     * tax_amount - a hardcoded list in the Yii 1 source, reproduced here.
     */
    public function getIgstAmount()
    {
        if (in_array($this->tax_id, [2, 3, 16, 17, 18])) {
            $bill = PurchaseBill::findOne($this->purchase_bill_id);
            if ($bill) {
                return $bill->tax_amount;
            }
        }
        $amount = 0;
        foreach ($this->siblings() as $detail) {
            $amount += $detail->igst_amt;
        }
        return $amount;
    }

    /**
     * The bill-level "other discount", attributed to a single line.
     *
     * The Yii 1 version picks one line per bill - grouped by tax id, ordered by
     * id descending, limited to one - and reports the discount only if that is
     * this line, so the figure appears once per bill rather than on every line.
     */
    public function getSchemeDiscount()
    {
        $bill = PurchaseBill::findOne($this->purchase_bill_id);
        $detail = static::find()
            ->where(['purchase_bill_id' => $this->purchase_bill_id])
            ->groupBy(['purchase_bill_id', 'tax_id'])
            ->orderBy(['id' => SORT_DESC])
            ->limit(1)
            ->one();

        return ($detail && $detail->id == $this->id && $bill)
            ? $bill->bill_other_discount
            : 0;
    }

    /**
     * Payload from PurchaseBillDetail::toArray1(), key for key.
     *
     * The $saleTax argument exists on the Yii 1 signature but its body is
     * commented out there ("not needed for tally reports"), so it has no effect
     * and is accepted here only to keep the signature.
     *
     * Several figures are per-tax-group rather than per-line, so every line of
     * a group repeats the same totals. That is how the Tally import expects it.
     */
    /**
     * PurchaseBillDetail::toArray() in Yii 1. Renamed because yii\base\Model
     * already carries a toArray() through Arrayable.
     *
     * rec_qty is hard-coded to 0 by the Yii 1 version - the received quantity
     * is filled in by the client, not read from the bill.
     */
    public function toApiArray()
    {
        return [
            'id' => (string)$this->id,
            'item' => isset($this->item) ? $this->item->title : '',
            'bar_code' => isset($this->itemDetail) ? $this->itemDetail->bar_code : '',
            'req_qty' => isset($this->req_qty) ? (string)$this->req_qty : '',
            'mrp' => isset($this->itemDetail) ? $this->itemDetail->getItemDetailMrp() : '',
            'approved_qty' => isset($this->approved_qty) ? (string)$this->approved_qty : '',
            'rec_qty' => 0,   // a literal in Yii 1 too, so it stays an int
        ];
    }

    public function toApiArray1($saleTax = false)
    {
        $bill = $this->purchaseBill;

        return [
            'id' => (string)$this->id,
            'Date' => isset($bill) ? $bill->end_date : '',
            'Vendor' => isset($bill) ? $bill->vendor->name : '',
            'HSN Code' => isset($this->tax) ? $this->tax->hrn_code : '',
            'Bill No' => isset($bill) ? $bill->bill_no : '',
            'GST NO' => $this->getVendorTAXNO(),
            'GST Rate' => $this->getTotalGstPer(),
            'CGST Rate' => $this->getTaxPercentage('cgst_per'),
            'SGST Rate' => $this->getTaxPercentage('sgst_per'),
            'CESS Rate' => $this->getTaxPercentage('cess_per'),
            'IGST Rate' => $this->getTaxPercentage('igst_per'),
            'Net Amount' => isset($bill) ? $bill->net_bill_amount : '',
            'Basic Value' => $this->getBasicAmount(),
            'Discount' => $this->getMainDiscount(),
            'GST' => $this->getTotalGstAmt(),
            'CGST' => $this->getCgstAmount(),
            'SGST' => $this->getSgstAmount(),
            'IGST' => $this->getIgstAmount(),
            'CESS' => $this->getCessAmount(),
            'GRN NUMBER' => isset($bill) ? 'Gr-' . $bill->grn_refrence_no : '',
            'SCHEME AND DISCOUNT' => isset($bill) ? $this->getSchemeDiscount() : '',
        ];
    }

    public function getVendorTAXNO()
    {
        if ($this->purchaseBill && $this->purchaseBill->vendor) {
            return $this->purchaseBill->vendor->tax_no;
        }
        return '';
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'PurchaseBillDetail' : 'PurchaseBillDetails';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'remarks';
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
        $value = $this->hasAttribute('remarks') ? $this->remarks : null;

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

    public static function getFreeItemOptions($id = null)
    {
		$list = [
				"No",
				"Yes",
				
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
				"Pending",
				"Received",
				"Approved",
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

    public function getPBillVendorOptions(){
            $list = [];
            $query = PurchaseBill::find();
            $query->orderBy(['id' => SORT_DESC]);
            $query->andWhere('status !='.PurchaseBill::STATUS_APPROVED);
            $mrss = $query->all();
            Yii::warning( var_export($mrss, true), '$mrss');
            if($mrss){
                foreach($mrss as $mrs){
                    $create_time = date('d-m-Y',strtotime($mrs->create_time));
                    $vendor = Vendor::findOne($mrs->vendor_id);
                    if($vendor){
                        //$list[$vendor->id] = $vendor->name.'('.$create_time.')';
                        $list[$vendor->id] = $vendor->name;
                    }
                }
            }
            asort($list);
                return $list;

        }

    public function getAllTaxOptions($id = null, $poid = null) {
            $taxType = null;
            $condition = ['status'=>Tax::STATUS_ACTIVE];
            if($poid) {
                $taxType = $this->getGstTrue($poid) == true ? 0 : 1;
                $condition['type_id'] = $taxType;
            }

            $list = [];
            $taxes = Tax::find()->where($condition)->all();
            if($taxes){
                foreach($taxes as $tax){
                    $list[$tax->id] = $tax->title;
                }
            }
            return $list;
            if ($id == null)
                return $list;
                if (is_numeric ( $id ))
                    return $list [$id];
                    return $id;
        }

    public function getPOBillOptions($id = null){
            $list = [];
            $user = Yii::$app->user->model;
            //$user = User::findOne($id);
            if($user){
                $role_id = $user->role_id;
                $role = UserRole::find()->where(['title'=>'Vendor'])->one();

                if ($id != null) {
                    if ($role_id == $role->id) {
                        $query = PurchaseBill::find();
            $query->orderBy(['id' => SORT_DESC]);
                        $query->andWhere('vendor_id ='.$id);
                        $query->andWhere('status !='.PurchaseBill::STATUS_APPROVED);
                        $polist = $query->all();
                    }else{
                        $query_2 = PurchaseBill::find();
            $query_2->orderBy(['id' => SORT_DESC]);

                        $query_2->andWhere('status !='.PurchaseBill::STATUS_APPROVED);
                        $polist = $query_2->all();
                    }
                    if($polist){
                        foreach($polist as $po){
                            $list[$po->id] = $po->id;
                        }
                    }
                }
            }
            return $list;
        }

    public function getAllPOBillOptions($id = null){
            $list = [];
            $user = Yii::$app->user->model;
            if($user){
                $role_id = $user->role_id;

                $role = UserRole::find()->where(['title'=>'Vendor'])->one();

                if ($id != null) {
                    if ($role_id == $role->id) {
                        $query = PurchaseBill::find();
            $query->orderBy(['id' => SORT_DESC]);
                        $query->andWhere('vendor_id ='.$id);
                        $query->andWhere('status !='.PurchaseBill::STATUS_APPROVED);
                        $polist = $query->all();

                    }else{
                        $query_2 = PurchaseBill::find();
            $query_2->orderBy(['id' => SORT_DESC]);

                        $query_2->andWhere('status !='.PurchaseBill::STATUS_APPROVED);
                        $polist = $query_2->all();
                    }
                    if($polist){
                        foreach($polist as $po){
                            $list[] = $po->id;
                        }
                    }
                }
            }
            return $list;
        }

    public function getCreateUser()
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
    }

    public function getOutlet()
    {
        return $this->hasOne(Outlet::class, ['id' => 'outlet_id']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
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
            'req_qty' => 'Max Qty',
            'bal_qty' => 'Bal Qty',
            'approved_qty' => 'Approved Qty',
            'mrp' => 'Mrp',
            'price' => 'Price',
            'discount' => 'Discount',
            'discount_amt' => 'Discount Amt',
            'discount1' => 'Other Discount',
            'discount_amt1' => 'Other Discount Amt',
            'tax_id' => 'tax_id',
            'other_charge' => 'Other Charge',
            'amount' => 'Amount',
            'sale_rate' => 'Sale Rate',
            'tally_start_date' => 'Start Date',
            'tally_end_date' => 'End Date',
            'status' => 'Status',
            'type_id' => 'Type',
            'tax_id' => 'Tax',
            'cgst_per' => 'CGST(%age)',
            'sgst_per' => 'SGST(%age)',
            'cess_per' => 'CESS(%age)',
            'cgst_amt' => 'CGST Amount',
            'sgst_amt' => 'SGST Amount',
            'cess_amt' => 'CESS Amount',
            'charge_amount' => 'Charge Amount',
            'extra_charges' => 'Extra Charges',
            'remarks' => 'Remarks',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'create_user_id' => 'User',
            'updated_by' => 'User',
            'item_detail_id' => 'ItemDetail',
            'purchase_bill_id' => 'PurchaseBill',
            'outlet_id' => 'Outlet',
            'vendor_id' => 'Vendor',
            'createUser' => 'User',
            'itemDetail' => 'ItemDetail',
            'item_id' => 'Item Name',
            'outlet' => 'Outlet',
            'purchaseBill' => 'PurchaseBill',
            'updatedBy' => 'User',
        ];
    }

    public function getColumns($selectcolumns = []) {
            if (! empty ( $selectcolumns )) {
                $selected = $selectcolumns;
            } else {

            $selected = [
                        'date',
                        'vendor',
                        'hsn_code' ,
                        'bill_no',
                        'tax_no' ,
                        'gst_per',
                        'cgst_per',
                        'sgst_per',
                        'igst_per',
                        'cess_per',
                        'net_amount',
                        'basic_value',
                        'discount',
                        'gst_amt' ,
                        'cgst_amt',
                        'sgst_amt',
                        'igst_amt',
                        'cess_amt',
                        'grn_no' ,
                        'scheme' ,
                ];
            }

            if ($selected) {
                foreach ( $selected as $select ) {
                    if ($select == 'date') {
                        $columns [] = [
                                'label' => 'Date',
                                'value' => function ($data) {
                                return isset($data->purchaseBill)?$data->purchaseBill->end_date:"";
                                }
                                ];
                    } else if ($select == 'vendor') {
                        $columns [] = [
                                'label' => 'Vendor',
                                'value' => function ($data) {
                                return isset ( $data->purchaseBill ) ? $data->purchaseBill->vendor: "";
                                }
                                ];
                    } else if ($select == 'hsn_code') {
                        $columns [] = [
                                'label' => 'HSN Code',
                                'value' => function ($data) {
                                return isset($data->tax)?$data->tax->hrn_code:"";
                                }
                                ];
                    }else if ($select == 'bill_no') {
                        $columns [] = [
                                'label' => 'Bill No',
                                'value' => function ($data) {
                                return isset ( $data->purchaseBill ) ? '"'.$data->purchaseBill->bill_no.'"': "";
                                }
                                ];
                    }  else if ($select == 'tax_no') {
                        $columns [] = [
                                'label' => 'GST NO',
                                'value' => function ($data) {
                                return $data->getVendorTAXNO();
                                }
                                ];
                    }else if ($select == 'gst_per') {
                        $columns [] = [
                                'label' => 'GST%',
                                'value' => function ($data) {
                                return $data->getTotalGstPer();
                                }
                                ];
                    }else if ($select == 'cgst_per') {
                        $columns [] = [
                                'label' => 'CGST%',
                                'value' => function ($data) {
                                return $data->getTaxPercentage("cgst_per");
                                }
                                ];
                    } else if ($select == 'sgst_per') {
                        $columns [] = [
                                'label' => 'SGST%',
                                'value' => function ($data) {
                                return $data->getTaxPercentage("sgst_per");
                                }
                                ];
                    } else if ($select == 'igst_per') {
                        $columns [] = [
                                'label' => 'IGST%',
                                'value' => function ($data) {
                                return $data->getTaxIgstPercentage();
                                }
                                ];
                    } else if ($select == 'cess_per') {
                        $columns [] = [
                                'label' => 'CESS%',
                                'value' => function ($data) {
                                return $data->getTaxPercentage("cess_per");
                                }
                                ];
                    } else if ($select == 'net_amount') {
                        $columns [] = [
                                'label' => 'Net Amount',
                                'value' => function ($data) {
                                return isset ( $data->purchaseBill ) ? $data->purchaseBill->net_bill_amount: "";

                                }
                                ];

                    }else if ($select == 'basic_value') {
                        $columns [] = [
                                'label' => 'Basic Value',
                                'value' => function ($data) {
                                return $data->getBasicAmount();
                                }
                                ];

                    }else if ($select == 'discount') {
                        $columns [] = [
                                'label' => 'Discount',
                                'value' => function ($data) {
                                return $data->getMainDiscount();
                                }
                                ];
                    }else if ($select == 'gst_amt') {
                        $columns [] = [
                                'label' => 'GST',
                                'value' => function ($data) {
                                return $data->getTotalGstAmt();
                                }
                                ];
                    }else if ($select == 'cgst_amt') {
                        $columns [] = [
                                'label' => 'CGST',
                                'value' => function ($data) {
                                return $data->getCgstAmount();
                                }
                                ];
                    }else if ($select == 'sgst_amt') {
                        $columns [] = [
                                'label' => 'SGST',
                                'value' => function ($data) {
                                return $data->getSgstAmount();
                                }
                                ];
                    }else if ($select == 'igst_amt') {
                        $columns [] = [
                                'label' => 'IGST',
                                'value' => function ($data) {
                                return $data->getIgstAmount();
                                }
                                ];
                    }  else if ($select == 'cess_amt') {
                        $columns [] = [
                                'label' => 'CESS Amount',
                                'value' => function ($data) {
                                return  $data->getCessAmount();
                                }
                                ];
                    }else if ($select == 'grn_no') {
                        $columns [] = [
                                'label' => 'GRN NUMBER',
                                'value' => function ($data) {
                                return isset ( $data->purchaseBill ) ? 'Gr-'.$data->purchaseBill->grn_refrence_no: "";
                                //return 'Gr-'.$data->purchase_bill_id;
                                }
                                ];
                    }else if ($select == 'scheme') {
                        $columns [] = [
                                'label' => 'SCHEME AND DISCOUNT',
                                'value' => function ($data) {
                                return $data->getSchemeDiscount();
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

    public  function getDetailGstTrue(){
            $gst = false;

            Yii::warning( var_export( $gst , true), '$$gst');

            return $gst;
        }

    public function getNetAmount(){
            $amount = 0;
            $purchase_bill_id = $this->purchase_bill_id;
            $details = PurchaseBillDetail::findAll(['purchase_bill_id'=>$purchase_bill_id,
                    'tax_id'=>$this->tax_id
            ]);
            if($details){
                foreach($details as $detail){
                    $amount= $amount + $detail->amount;
                }
            }
            return $amount;
        }

    public function toArray1($saleTax = false) {
            $model = $this;
            $bill = $this;
            $json_entry = null;
            if ($model) {
                $default_img = 'default.png';
                $json_entry = [];
                $json_entry ['id'] = $model->id;
                $json_entry ['Date'] = isset($model->purchaseBill)?$model->purchaseBill->end_date:"";
        $json_entry ['Vendor'] = isset ( $model->purchaseBill ) ? $model->purchaseBill->vendor->name: "";
        $json_entry ['HSN Code'] = isset($model->tax)?$model->tax->hrn_code:"";
        $json_entry ['Bill No'] = isset ( $model->purchaseBill ) ? $model->purchaseBill->bill_no: "";
        $json_entry ['GST NO'] = $model->getVendorTAXNO();
        $json_entry ['GST Rate'] = $model->getTotalGstPer();
        $json_entry ['CGST Rate'] =  $model->getTaxPercentage("cgst_per");
        $json_entry ['SGST Rate'] = $model->getTaxPercentage("sgst_per");

        $json_entry ['CESS Rate'] =   $model->getTaxPercentage("cess_per");
        $json_entry ['IGST Rate'] = $model->getTaxPercentage("igst_per");
        $json_entry ['Net Amount'] = isset ( $model->purchaseBill ) ? $model->purchaseBill->net_bill_amount: "";
        $json_entry ['Basic Value'] = $model->getBasicAmount();
        $json_entry ['Discount'] =  $model->getMainDiscount();
        $json_entry ['GST'] = $model->getTotalGstAmt();
            $json_entry ['CGST'] = $model->getCgstAmount();
        $json_entry ['SGST'] =   $model->getSgstAmount();
        $json_entry ['IGST'] = $model->getIgstAmount();
        $json_entry ['CESS'] = $model->getCessAmount();
        $json_entry ['GRN NUMBER'] =  isset ( $model->purchaseBill ) ? 'Gr-'.$model->purchaseBill->grn_refrence_no: "";
        $json_entry ['SCHEME AND DISCOUNT'] = isset ( $model->purchaseBill ) ? $model->getSchemeDiscount(): "";
                 // not needed for tally reports
                // if ($saleTax && $model->sale_tax_id > 0 && $model->tax_id != $model->sale_tax_id) {
                //     $json_entry ['GST Rate'] = $model->sale_cgst_per + $model->sale_sgst_per;
                //     $json_entry ['CGST Rate'] =  $model->sale_cgst_per;
                //     $json_entry ['SGST Rate'] = $model->sale_sgst_per;
                //     $json_entry ['CESS Rate'] =   $model->sale_cess_per;
                //     $json_entry ['IGST Rate'] = $model->sale_igst_per;
                //     $json_entry ['GST'] = $model->sale_cgst_amt + $model->sale_sgst_amt;
                //     $json_entry ['CGST'] = $model->sale_cgst_amt;
                //     $json_entry ['SGST'] =   $model->sale_sgst_amt;
                //     $json_entry ['IGST'] = $model->sale_igst_amt;
                //     $json_entry ['CESS'] = $model->sale_cess_amt;
                // }
        }
            return $json_entry;
        }

    public function gethsncode(){
            if($this->hsn_code != null ){
                return $this->hsn_code;
            }else{
                return $this->item->hsn_code;
            }
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
        return ['order' => SORT_ASC];
    }

    public function getTaxIgstPercentage(){

             $query = Tax::find();
            $query->andWhere('id ='.$this->tax_id);
            $details= $query->one();
            $igst=$details->tax_val4;
             if($igst){
                return $igst;
                }
            else{
                return '0.00';
            }
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
     * Yii 1's reportsearch(): a listing of its own, converted as written.
     */
    public function reportsearch()
    {

		$query = self::find();
		
		$purchase_bill_ids = [];
		$query1 = PurchaseBill::find();
		if($this->start_date != null){
			Criteria::compare($query1, 'start_date', $this->start_date);
		} 
		if($this->vendor_id != null){
			Criteria::compare($query1, 'vendor_id', $this->vendor_id);
		}
		
		$query1->andWhere('status ='.PurchaseBill::STATUS_APPROVED);
		$purchasebills= $query1->all();
	//	Yii::warning( var_export( $purchasebills , true), '$mrss');
		if($purchasebills){
			foreach($purchasebills as $purchasebill){
				$purchase_bill_ids[] = $purchasebill->id;
			}
	
		}
		
		$query->groupBy('purchase_bill_id,tax_id');
		// MySQL 5.7 sorted GROUP BY results implicitly; MySQL 8.0 does not. Order
		// explicitly by the grouped columns to preserve the previous output order.
		$query->orderBy(['purchase_bill_id' => SORT_ASC, 'tax_id' => SORT_ASC]);
		$query->andWhere(['purchase_bill_id' => $purchase_bill_ids]);
		Yii::warning( var_export( Yii::$app->session ['tally_start_date'] , true), 'start_date');
		Yii::warning( var_export( Yii::$app->session ['tally_start_date'] , true), 'end_date');
		if ((Yii::$app->session ['tally_start_date'] != '') && (Yii::$app->session ['tally_end_date'] != '')) {
			$query->andWhere(['between', 'date(create_time)', Yii::$app->session ['tally_start_date'], Yii::$app->session ['tally_end_date']]);
		}
		Criteria::compare($query, 'id', $this->id);
		Criteria::compare($query, 'req_qty', $this->req_qty);
		Criteria::compare($query, 'bal_qty', $this->bal_qty);
		Criteria::compare($query, 'approved_qty', $this->approved_qty);
		Criteria::compare($query, 'mrp', $this->mrp);
		Criteria::compare($query, 'price', $this->price);
		Criteria::compare($query, 'discount', $this->discount);
		Criteria::compare($query, 'discount_amt', $this->discount_amt);
		Criteria::compare($query, 'tax_id', $this->tax_id);
		Criteria::compare($query, 'other_charge', $this->other_charge);
		Criteria::compare($query, 'amount', $this->amount);
		Criteria::compare($query, 'sale_rate', $this->sale_rate);
		Criteria::compare($query, 'status', $this->status);
		Criteria::compare($query, 'type_id', $this->type_id);
		Criteria::compare($query, 'charge_amount', $this->charge_amount);
		Criteria::compare($query, 'extra_charges', $this->extra_charges);
		Criteria::compare($query, 'remarks', $this->remarks, true);
	//	$criteria->compare ( 'create_time', $this->create_time, true );
	//	$criteria->compare ( 'update_time', $this->update_time, true );
		Criteria::compare($query, 'create_user_id', $this->create_user_id);
		Criteria::compare($query, 'updated_by', $this->updated_by);
		Criteria::compare($query, 'item_detail_id', $this->item_detail_id);
		Criteria::compare($query, 'item_id', $this->item_id);
		Criteria::compare($query, 'purchase_bill_id', $this->purchase_bill_id);
		Criteria::compare($query, 'outlet_id', $this->outlet_id);
	
		return new ActiveDataProvider([
		    'query' => $query,
		    'sort' => ['defaultOrder' => []],
		    'pagination' => ['pageSize' => Ui::PAGE_SIZE],
		]);
    }

    /**
     * Yii 1's purchasesearch(): a listing of its own, converted as written.
     */
    public function purchasesearch()
    {

		$query = self::find()->alias('t');
	
		
		$query->joinWith(['itemDetail' => function ($q) { $q->alias('itemDetail'); }, 'item' => function ($q) { $q->alias('item'); }]);
		
		Criteria::compare($query, 'itemDetail.bar_code', $this->item_detail_id, true);
		Criteria::compare($query, 'item.title', $this->item_id, true);
		Criteria::compare($query, 't.req_qty', $this->req_qty);
		Criteria::compare($query, 't.bal_qty', $this->bal_qty);
		Criteria::compare($query, 't.approved_qty', $this->approved_qty);
		Criteria::compare($query, 't.mrp', $this->mrp);
		Criteria::compare($query, 't.price', $this->price);
		Criteria::compare($query, 't.discount', $this->discount);
		Criteria::compare($query, 't.discount_amt', $this->discount_amt);
		Criteria::compare($query, 't.discount1', $this->discount1);
		Criteria::compare($query, 't.discount_amt1', $this->discount_amt1);
		Criteria::compare($query, 't.tax_id', $this->tax_id);
		Criteria::compare($query, 't.other_charge', $this->other_charge);
		Criteria::compare($query, 't.amount', $this->amount);
		Criteria::compare($query, 't.sale_rate', $this->sale_rate);
		Criteria::compare($query, 't.status', $this->status);
		Criteria::compare($query, 't.type_id', $this->type_id);
		Criteria::compare($query, 't.igst_per', $this->igst_per, true);
		Criteria::compare($query, 't.cgst_per', $this->cgst_per, true);
		Criteria::compare($query, 't.sgst_per', $this->sgst_per, true);
		Criteria::compare($query, 't.cess_per', $this->cess_per, true);
		Criteria::compare($query, 't.igst_amt', $this->igst_amt, true);
		Criteria::compare($query, 't.cgst_amt', $this->cgst_amt, true);
		Criteria::compare($query, 't.sgst_amt', $this->sgst_amt, true);
		Criteria::compare($query, 't.cess_amt', $this->cess_amt, true);
		Criteria::compare($query, 't.charge_amount', $this->charge_amount);
		Criteria::compare($query, 't.extra_charges', $this->extra_charges);
		Criteria::compare($query, 't.remarks', $this->remarks, true);
		Criteria::compare($query, 't.create_time', $this->create_time, true);
		Criteria::compare($query, 't.update_time', $this->update_time, true);
		Criteria::compare($query, 't.create_user_id', $this->create_user_id);
		Criteria::compare($query, 't.updated_by', $this->updated_by);
		
		Criteria::compare($query, 't.purchase_bill_id', $this->purchase_bill_id);
		Criteria::compare($query, 't.outlet_id', $this->outlet_id);
	
		return new ActiveDataProvider([
		    'query' => $query,
		    'sort' => ['defaultOrder' => []],
		    'pagination' => ['pageSize' => 100],
		]);
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
            [['expiry_date', 'packing_date', 'columns', 'start_date', 'tally_start_date', 'tally_end_date', 'vendor_id', 'bar_code', 'item_val_id'], 'safe'],  // form-only, declared on the Yii 1 model
            [['bill_date', 'bill_no'], 'safe'],  // form-only, declared on the Yii 1 model
            [['req_qty', 'item_detail_id', 'item_id', 'purchase_bill_id'], 'required'],
            [['status', 'type_id', 'create_user_id', 'updated_by', 'item_detail_id', 'purchase_bill_id', 'outlet_id'], 'integer'],
            [['mrp', 'price', 'discount', 'discount_amt', 'tax_id', 'other_charge', 'amount', 'sale_rate', 'charge_amount', 'extra_charges'], 'number'],
            [['hsn_code', 'item_val_id', 'grn_refrence_no', 'bar_code', 'tally_start_date', 'tally_end_date', 'start_date', 'remarks', 'is_free', 'order', 'margin', 'expiry_date', 'packing_date', 'vendor_id', 'create_time', 'columns', 'igst_amt', 'igst_per', 'update_time', 'discount1', 'discount_amt1', 'start_date', 'approved_qty', 'item_id', 'mrp', 'tax_id', 'other_charge', 'sale_rate', 'discount', 'discount_amt', 'other_charge', 'amount', 'price', 'cgst_per', 'sgst_per', 'cess_per', 'cgst_amt', 'sgst_amt', 'cess_amt'], 'safe'],
            [['bal_qty', 'status', 'type_id', 'charge_amount', 'extra_charges', 'remarks', 'create_time', 'update_time', 'updated_by', 'outlet_id'], 'default', 'value' => null],
            [['id', 'req_qty', 'bal_qty', 'approved_qty', 'mrp', 'price', 'discount', 'discount_amt', 'tax_id', 'other_charge', 'amount', 'sale_rate', 'status', 'type_id', 'charge_amount', 'extra_charges', 'remarks', 'create_time', 'update_time', 'create_user_id', 'updated_by', 'item_detail_id', 'purchase_bill_id', 'outlet_id'], 'safe', 'on' => 'search'],
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
	
		$purchase_bill_ids = array();
		$query1 = PurchaseBill::find();
        $query1->orderBy(['id' => SORT_DESC]);
		$user = Yii::$app->user->model;
		$role_id = $user->role_id;
		
		if($this->start_date != null){
			Criteria::compare($query1, 'start_date', $this->start_date);
		}
		
		if($this->vendor_id != null){
			Criteria::compare($query1, 'vendor_id', $this->vendor_id);
		}
		
		//$criteria1->addCondition('status ='.PurchaseBill::STATUS_UNAPPROVED);
		$query1->andWhere('status !='.PurchaseBill::STATUS_APPROVED);
		$purchasebills= $query1->all();
		//Yii::warning( var_export($purchasebills, true), '$mrss');
		if($purchasebills){
			foreach($purchasebills as $purchasebill){
				$purchase_bill_ids[] = $purchasebill->id;
			}
		
		}
		
		$query->andWhere(['purchase_bill_id' => $purchase_bill_ids]);
		Criteria::compare($query, 't.id', $this->id);
		Criteria::compare($query, 't.req_qty', $this->req_qty);
		Criteria::compare($query, 't.bal_qty', $this->bal_qty);
		Criteria::compare($query, 't.approved_qty', $this->approved_qty);
		Criteria::compare($query, 't.mrp', $this->mrp);
		Criteria::compare($query, 't.price', $this->price);
		Criteria::compare($query, 't.discount', $this->discount);
		Criteria::compare($query, 't.discount_amt', $this->discount_amt);
		Criteria::compare($query, 't.tax_id', $this->tax_id);
		Criteria::compare($query, 't.other_charge', $this->other_charge);
		Criteria::compare($query, 't.amount', $this->amount);
		Criteria::compare($query, 't.sale_rate', $this->sale_rate);
		Criteria::compare($query, 't.status', $this->status);
		Criteria::compare($query, 't.type_id', $this->type_id);
		Criteria::compare($query, 't.charge_amount', $this->charge_amount);
		Criteria::compare($query, 't.extra_charges', $this->extra_charges);
		Criteria::compare($query, 't.remarks', $this->remarks, true);
		Criteria::compare($query, 't.create_time', $this->create_time, true);
		Criteria::compare($query, 't.update_time', $this->update_time, true);
		Criteria::compare($query, 't.create_user_id', $this->create_user_id);
		Criteria::compare($query, 't.updated_by', $this->updated_by);
		Criteria::compare($query, 't.item_detail_id', $this->item_detail_id);
		Criteria::compare($query, 't.purchase_bill_id', $this->purchase_bill_id);
		Criteria::compare($query, 't.outlet_id', $this->outlet_id);
		
		$query->orderBy(['order' => SORT_ASC]);

		return new ActiveDataProvider([
		    'query' => $query,
		    'sort' => ['defaultOrder' => []],
		    'pagination' => false,
		]);
    }
}
