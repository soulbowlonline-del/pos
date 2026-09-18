<?php
namespace app\models;

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

    /** GxActiveRecord::__toString(): the representing column, or the id. */
    public function __toString()
    {
        $value = $this->hasAttribute('remarks') ? $this->remarks : null;

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
            $query->andWhere('status !='.PurchaseBill::STATUS_APPROVED);
            $mrss = $query->all();
            Yii::warning( var_export( $mrss ), '$mrss');
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
            $taxes = Tax::findAll($condition);
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
                $role = UserRole::findOne(['title'=>'Vendor']);

                if ($id != null) {
                    if ($role_id == $role->id) {
                        $query = PurchaseBill::find();
                        $query->andWhere('vendor_id ='.$id);
                        $query->andWhere('status !='.PurchaseBill::STATUS_APPROVED);
                        $polist = $query->all();
                    }else{
                        $query = PurchaseBill::find();

                        $query->andWhere('status !='.PurchaseBill::STATUS_APPROVED);
                        $polist = $query->all();
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

                $role = UserRole::findOne(['title'=>'Vendor']);

                if ($id != null) {
                    if ($role_id == $role->id) {
                        $query = PurchaseBill::find();
                        $query->andWhere('vendor_id ='.$id);
                        $query->andWhere('status !='.PurchaseBill::STATUS_APPROVED);
                        $polist = $query->all();

                    }else{
                        $query = PurchaseBill::find();

                        $query->andWhere('status !='.PurchaseBill::STATUS_APPROVED);
                        $polist = $query->all();
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

            Yii::warning( var_export( $gst ), '$$gst');

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
}
