<?php
namespace app\models;

use app\components\Criteria;

use app\components\Ui;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/**
 * Ported from protected/models/ItemReturnItem.php (Yii 1).
 *
 * Only the Tally export payload and the helpers it needs are ported.
 */
class ItemReturnItem extends ActiveRecord
{
    // Yii 1 hands out column values as strings; the option helpers
    // compare them loosely and answer wrongly for an integer 0.
    use LegacyColumnTypes;

    public $credit_note_date;
    public $credit_note_no;
    public $start_date;
    public $item_val_id;
    public $bar_code;
    public $tally_end_date;
    public $tally_start_date;
    public $columns;
    public static function tableName()
    {
        return '{{%item_return_item}}';
    }

    public function getItemReturn()
    {
        return $this->hasOne(ItemReturn::class, ['id' => 'return_id']);
    }

    public function getTax()
    {
        return $this->hasOne(Tax::class, ['id' => 'tax_id']);
    }

    /**
     * Whether this return attracts CGST/SGST rather than IGST.
     *
     * True when the vendor and the outlet are in the same state. The Yii 1
     * version dereferences $vendor without checking it was found, so a return
     * whose vendor row is missing fails there; that is left as-is rather than
     * papered over, since silently returning a different tax treatment would be
     * worse than the error.
     */
    public function getReturnGstTrue($returnId)
    {
        if (!$returnId) {
            return true;
        }
        $return = ItemReturn::findOne(['id' => $returnId]);
        if (!$return) {
            return true;
        }
        $outlet = Outlet::findOne($return->outlet_id);
        if (!$outlet) {
            return true;
        }
        $vendor = Vendor::findOne($return->vendor_id);
        return $vendor->state_id != $outlet->state_id ? false : true;
    }

    /**
     * A tax percentage column, zeroed when it does not apply: the CGST/SGST
     * columns read 0.00 on an inter-state return and IGST reads 0.00 on an
     * intra-state one.
     */
    public function getTaxPercentage($col)
    {
        $checkGst = !($col === 'igst_per' || $col === 'igst_amt');
        return $this->getReturnGstTrue($this->return_id) == $checkGst
            ? $this->$col
            : '0.00';
    }

    public function getTotalGstPer()
    {
        return $this->getTaxPercentage('cgst_per')
             + $this->getTaxPercentage('sgst_per')
             + $this->getTaxPercentage('cess_per')
             + $this->getTaxPercentage('igst_per');
    }

    public function getVendorTAXNO()
    {
        if ($this->itemReturn && $this->itemReturn->vendor) {
            return $this->itemReturn->vendor->tax_no;
        }
        return '';
    }

    public function getCustomGRNNumber()
    {
        $grnNo = isset($this->itemReturn) ? $this->itemReturn->grn_no : '';
        return $grnNo != '' ? 'GR-' . $grnNo : '';
    }

    /**
     * Payload from ItemReturnItem::toTallyArray(), key for key.
     *
     * Keys carry spaces ('Bill Date', 'GST NO') because they become column
     * headings in the Tally import.
     */
    public function toTallyApiArray()
    {
        $vendorName = '';
        $billNo = '';
        $billDate = '';
        $grnSaveDate = '';

        if (isset($this->itemReturn)) {
            if (isset($this->itemReturn->vendor)) {
                $vendor = $this->itemReturn->vendor;
                $vendorName = $vendor->name;
                if ($vendor->parent_id !== null) {
                    $vendorName = $vendor->parentvendor->name;
                }
            }
            $billNo = $this->itemReturn->bill_no;
            $grnSaveDate = date('Y-m-d', strtotime((string)$this->itemReturn->grn_save_date));
        }

        $grnDate = '';
        if ($billNo != '') {
            $purchaseBill = PurchaseBill::findOne(['bill_no' => $billNo]);
            if ($purchaseBill) {
                $billDate = $purchaseBill->end_date;
                $grnDate = date('Y-m-d', strtotime((string)$purchaseBill->start_date));
            }
        }

        $json = [];
        $json['id'] = (string)$this->id;
        $json['grn_date'] = $grnDate;
        $json['Bill Date'] = $billDate;
        $json['Date'] = $grnSaveDate;
        $json['Vendor'] = $vendorName;
        $json['HSN Code'] = isset($this->tax) ? $this->tax->hrn_code : '';
        $json['GST NO'] = $this->getVendorTAXNO();
        $json['Credit Note No'] = isset($this->itemReturn) ? $this->itemReturn->credit_note_no : '';
        $json['Credit Note Date'] = isset($this->itemReturn) ? $this->itemReturn->credit_note_date : '';
        $json['Bill No'] = isset($this->itemReturn) ? $this->itemReturn->bill_no : '';
        $json['Supplier Invoice No'] = isset($this->itemReturn) ? $this->itemReturn->invoice_no : '';
        $json['GST Rate'] = $this->getTotalGstPer();
        $json['CGST Rate'] = $this->cgst_per;
        $json['SGST Rate'] = $this->sgst_per;
        $json['CESS Rate'] = $this->cess_per;
        $json['IGST Rate'] = $this->igst_per;
        $json['Net Amount'] = isset($this->itemReturn) ? $this->itemReturn->total_amt : '';
        $json['Basic Value'] = ($this->price * $this->qty) - $this->discount_amt;
        $json['Discount'] = 0;
        $json['GST'] = $this->cgst_amt + $this->sgst_amt + $this->cess_amt + $this->igst_amt;
        $json['CGST'] = $this->cgst_amt;
        $json['SGST'] = $this->sgst_amt;
        $json['IGST'] = $this->igst_amt;
        $json['CESS'] = $this->cess_amt;
        $json['GRN NUMBER'] = $this->getCustomGRNNumber();

        return $json;
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'ItemReturnItem' : 'ItemReturnItems';
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
        return self::defaultOrder();
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

    public static function getAPIOptions($id = null)
    {
		$list = ["Yes","No"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
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

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'item_id' => 'Item',
            'item_detail_id' => 'ItemDetail',
            'mrp' => 'Mrp',
            'price' => 'Price',
            'sale_rate' => 'Sale Rate',
            'free' => 'Free',
            'qty' => 'Qty',
            'discount' => 'Discount',
            'discount_amt' => 'Discount Amount',
            'discount1' => 'Other Discount(%age)',
            'discount_amt1' => 'Other Discount',
            'cgst_per' => 'Cgst Per',
            'sgst_per' => 'Sgst Per',
            'cess_per' => 'Cess Per',
            'cgst_amt' => 'Cgst Amt',
            'sgst_amt' => 'Sgst Amt',
            'cess_amt' => 'Cess Amt',
            'igst_per' => 'Igst Per',
            'igst_amt' => 'Igst Amt',
            'tax_id' => 'Tax',
            'other_charge' => 'Other Charge',
            'total_amt' => 'Total Amt',
            'vendor_id' => 'Vendor',
            'outlet_id' => 'Outlet',
            'status' => 'Status',
            'type_id' => 'Type',
            'return_id' => 'Return',
            'create_time' => 'Create Time',
            'create_user_id' => 'User',
            'updated_by' => 'User',
            'createUser' => 'User',
            'itemDetail' => 'ItemDetail',
            'item' => 'Item',
            'updatedBy' => 'User',
        ];
    }

    public function getGstTrue($vendor_id,$outlet_id){
            $gst = true;
            if($vendor_id !='' && $outlet_id != ''){
                $vendor = Vendor::findOne($vendor_id);
                if($vendor){
                    $outlet = Outlet::findOne($outlet_id);

                    if($outlet){

                        if($vendor->state_id != $outlet->state_id){
                            $gst = false;
                        }
                    }
                }
            }
            return $gst;
        }

    public function getItemReturnCgstAmount($vendorId, $outletId, $taxId, $col)
        {
            $amount = '0.00';
            $itemReturnItemDetails = ItemReturnItem::find()->where([
                'vendor_id' => $vendorId,
                'outlet_id' => $outletId,
                'tax_id' => $taxId,
                'status' => ItemReturn::STATUS_PENDING
            ])->all();
            if ($itemReturnItemDetails) {
                foreach ($itemReturnItemDetails as $itemReturnItemDetail) {
                    if ($itemReturnItemDetail->getGstTrue($vendorId, $outletId) == true && $itemReturnItemDetail->free == 0) {
                        $amount = $amount + $itemReturnItemDetail->$col;
                    }
                }
            }
            return $amount;
        }

    public function getAllTaxOptions($id = null, $vendorId = null, $outletId = null) {
            $taxType = null;
            $condition = ['status'=>Tax::STATUS_ACTIVE];
            if($vendorId && $outletId) {
                $taxType = $this->getGstTrue($vendorId, $outletId) == true ? 0 : 1;
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

    public function getChangeItemReturnCgstAmount($id,$col,$return_item_ids){
            $amount = 0;
            if(!empty($return_item_ids)){
                foreach($return_item_ids as $return_item_id){
                    $array = json_decode(($return_item_id), true);
                    $itemReturnItem = ItemReturnItem::findOne($array['id']);
                    if(isset($array['cgst']) && ($col == 'cgst_amt')&&($array['tax_id'] == $id)&& ($itemReturnItem->free == 0)){
                        $amount = $amount + $array['cgst'];
                    }else if(isset($array['sgst']) && ($col == 'sgst_amt')&&($array['tax_id'] == $id)){
                        $amount = $amount + $array['sgst'];
                    }else if(isset($array['cess']) && ($col == 'cess_amt')&&($array['tax_id'] == $id)){
                        $amount = $amount + $array['cess'];
                    }else if(isset($array['igst']) && ($col == 'igst_amt')&&($array['tax_id'] == $id)){
                        $amount = $amount + $array['igst'];
                    }
                }
            }
            return $amount;


        }

    public function getChangeItemReturnAmount($id,$return_item_ids){
        //    $array = json_decode(json_encode($return_item_ids), true);
            $amount = 0;
            if(!empty($return_item_ids)){
                foreach($return_item_ids as $return_item_id){
                    $array = json_decode(($return_item_id), true);
                    $itemReturnItem = ItemReturnItem::findOne($array['id']);
                    if($array['tax_id'] == $id && $itemReturnItem->free == 0){
                        $amount = $amount + (($array['qty'] * $array['price'])-($array['discount']+$array['discount1']));
                    }
                }
            }
            return $amount;


        }

    public function getItemReturnItemAmount($vendorId, $outletId,$id){
            $amount = '0.00';
            $itemReturnItemDetails = ItemReturnItem::find()->where([
                'vendor_id' => $vendorId,
                'outlet_id' => $outletId,
                'tax_id'=>$id,
                'status' => ItemReturn::STATUS_PENDING
            ])->all();
            if($itemReturnItemDetails){
                foreach($itemReturnItemDetails as $itemReturnItemDetail){
                    if($itemReturnItemDetail->free == 0){
                        $amount = $amount + (($itemReturnItemDetail->qty * $itemReturnItemDetail->price)-($itemReturnItemDetail->discount_amt+$itemReturnItemDetail->discount_amt1));
                    }
                }

            }
            return $amount;

        }

    public function getColumns($selectcolumns = []) {
            if (! empty ( $selectcolumns )) {
                $selected = $selectcolumns;
            } else {

            $selected = [
                    //    'grn_date',
                        'start_date',
                        'vendor_id' ,
                        'tax_id',
                            'gst_no' ,
                        'credit_note_no',
                    'credit_note_date',
                    'debit_note_date',
                        'invoice_no',
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
                        'grn_no'

                ];
            }

            if ($selected) {
                foreach ( $selected as $select ) {
                    /*if ($select == 'grn_date') {
                        $columns [] = array (
                                'label' => 'grn_date',
                                'value' => function ($data) {
                                return date("Y-m-d",strtotime($data->create_time));
                                }
                                );
                    } else*/ if ($select == 'start_date') {
                         $columns[] = [
                            'label' => 'Date',
                            'value' => function ($data) {
                            return $data->getGRNDateData();
                                //return date("d/m/Y", strtotime($data->create_time));
                            }
                        ];

                        $columns[] = [
                            'label' => 'Bill Date',
                            'value' => function ($data) {
                            return $data->getBillDateData();
                            //return date("d/m/Y", strtotime($data->create_time));
                            }
                            ];
                    } else if ($select == 'vendor_id') {
                        $columns [] = [
                                'label' => 'Vendor',
                                'value' => function ($data) {
                                return isset($data->itemReturn)?$data->itemReturn->vendor:"";
                                }
                                ];
                    }else if ($select == 'tax_id') {
                        $columns [] = [
                                'label' => 'HSN Code',
                                'value' => function ($data) {
                                return isset($data->tax)?$data->tax->hrn_code:"";
                                }
                                ];
                    } else if ($select == 'bill_no') {
                        $columns [] = [
                                'label' => 'Bill No.',
                                'value' => function ($data) {
                                return isset ( $data->itemReturn ) ? $data->itemReturn->bill_no: "";
                                }
                                ];
                    } else if ($select == 'gst_no') {
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
                    } else if ($select == 'cgst_per') {
                        $columns [] = [
                                'label' => 'CGST (%age)',
                                'value' => function ($data) {
                                return $data->cgst_per;
                                }
                                ];
                    } else if ($select == 'sgst_per') {
                        $columns [] = [
                                'label' => 'SGST (%age)',
                                'value' => function ($data) {
                                return $data->sgst_per;
                                }
                                ];
                    } else if ($select == 'cess_per') {
                        $columns [] = [
                                'label' => 'CESS (%age)',
                                'value' => function ($data) {
                                return $data->cess_per;
                                }
                                ];
                    } else if ($select == 'igst_per') {
                        $columns [] = [
                                'label' => 'IGST (%age)',
                                'value' => function ($data) {
                                return $data->igst_per;
                                }
                                ];
                    } else if ($select == 'credit_note_no') {
                        $columns [] = [
                                'label' => 'Credit Note No.',
                                'value' => function ($data) {
                                return isset ( $data->itemReturn ) ? $data->itemReturn->credit_note_no: "";
                                }
                                ];
                    }else if ($select == 'credit_note_date') {
                        $columns [] = [
                                'label' => 'Credit Note Date',
                                'value' => function ($data) {
                                return isset ( $data->itemReturn ) ? $data->itemReturn->credit_note_date: "";
                                }
                                ];
                    }else if ($select == 'debit_note_date') {
                        $columns [] = [
                                'label' => 'Debit Note Date',
                                'value' => function ($data) {
                                return isset ( $data->itemReturn ) ? $data->itemReturn->grn_save_date: "";
                                }
                                ];
                    } else if ($select == 'invoice_no') {
                        $columns [] = [
                                'label' => 'Supplier Invoice No.',
                                'value' => function ($data) {
                                return isset ( $data->itemReturn ) ? $data->itemReturn->invoice_no: "";
                                }
                                ];
                    }else if ($select == 'net_amount') {
                        $columns [] = [
                                'label' => 'Net Amount',
                                'value' => function ($data) {
                                return isset ( $data->itemReturn ) ? $data->itemReturn->total_amt: "";

                                }
                                ];

                    }else if ($select == 'basic_value') {
                        $columns [] = [
                                'label' => 'Basic Value',
                                'value' => function ($data) {
                                return ($data->price * $data->qty) - $data->discount_amt;
                                }
                                ];

                    }else if ($select == 'discount') {
                        $columns [] = [
                                'label' => 'Discount',
                                'value' => function ($data) {
                                // return isset ( $data->itemReturn ) ? $data->itemReturn->discount_amt: "";
                                return $data->discount_amt;
                                }
                                ];
                    }else if ($select == 'gst_amt') {
                        $columns [] = [
                                'label' => 'GST',
                                'value' => function ($data) {
                                return $data->cgst_amt + $data->sgst_amt + $data->cess_amt + $data->igst_amt;
                                }
                                ];
                    }else if ($select == 'cgst_amt') {
                        $columns [] = [
                                'label' => 'CGST',
                                'value' => function ($data) {
                                return $data->cgst_amt;
                                }
                                ];
                    }else if ($select == 'sgst_amt') {
                        $columns [] = [
                                'label' => 'SGST',
                                'value' => function ($data) {
                                return $data->sgst_amt;
                                }
                                ];
                    }else if ($select == 'igst_amt') {
                        $columns [] = [
                                'label' => 'IGST',
                                'value' => function ($data) {
                                return $data->igst_amt;
                                }
                                ];
                    }  else if ($select == 'cess_amt') {
                        $columns [] = [
                                'label' => 'CESS Amount',
                                'value' => function ($data) {
                                return  $data->cess_amt;
                                }
                                ];
                    }else if ($select == 'grn_no') {
                        $columns [] = [
                                'label' => 'GRN NUMBER',
                                'value' => function ($data) {
                                return isset ( $data->itemReturn ) ? $data->itemReturn->grn_no: "";
                                //return 'Gr-'.$data->purchase_bill_id;
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

    public function getVendorName(){
            $vendor_name = '';
    if(isset($this->itemReturn)){
        if(isset($this->itemReturn->vendor)){
            $vendor = $this->itemReturn->vendor;
            $vendor_name = $this->itemReturn->vendor->name;
            if($this->itemReturn->vendor->parent_id != null){
                $vendor_name = $vendor->parentvendor->name;
            }

        }
    }
    return $vendor_name;
        }

    public function getGRNDateData()
        {

        $grn_date = '';

        $model = $this;

    if($model)
    {
        $bill_no = "";

    if(isset($model->itemReturn)){
        $bill_no = isset ( $model->itemReturn ) ? $model->itemReturn->bill_no: "";
    }
    if($bill_no != ""){
        $purchasebill = PurchaseBill::find()->where(['bill_no'=>$bill_no])->one();
        if($purchasebill){
            $grn_date = date("Y-m-d",strtotime($purchasebill->start_date));

        }
    }
    return $grn_date;


    }
        }

    public function getBillDateData()
        {

        $bill_date = '';

        $model = $this;

    if($model)
    {
        $bill_no = "";

    if(isset($model->itemReturn)){
        $bill_no = isset ( $model->itemReturn ) ? $model->itemReturn->bill_no: "";
    }
    if($bill_no != ""){
        $purchasebill = PurchaseBill::find()->where(['bill_no'=>$bill_no])->one();
        if($purchasebill){
            $bill_date = date("Y-m-d",strtotime($purchasebill->end_date));

        }
    }
    return $bill_date;


    }
        }

    public function toTallyArray() {
            $model = $this;
            $json_entry = null;
            if ($model) {
    $vendor_name = '';
    $bill_no = "";
    $bill_date = '';
    $grn_save_date = '';
    if(isset($model->itemReturn)){
        if(isset($model->itemReturn->vendor)){
            $vendor = $model->itemReturn->vendor;
            $vendor_name = $model->itemReturn->vendor->name;
            if($model->itemReturn->vendor->parent_id != null){
                $vendor_name = $vendor->parentvendor->name;
            }

        }
        $bill_no = isset ( $model->itemReturn ) ? $model->itemReturn->bill_no: "";

        $grn_save_date = date("Y-m-d",strtotime( $model->itemReturn->grn_save_date ));
    }

    $grn_date = '';
    if($bill_no != ""){
        $purchasebill = PurchaseBill::find()->where(['bill_no'=>$bill_no])->one();
        if($purchasebill){
            $bill_date = $purchasebill->end_date;

             $grn_date = date("Y-m-d",strtotime( $purchasebill->start_date ));
        }
    }
                $batch_no =  [];
                $default_img = 'default.png';
                $json_entry = [];
                $json_entry ['id'] = $model->id;
                  $json_entry ['grn_date'] = $grn_date;
                $json_entry['Bill Date'] = $bill_date;
                $json_entry['Date'] = $grn_save_date;

                 //$json_entry['Date'] = date("Y-m-d", strtotime($model->grn_save_date));
                $json_entry ['Vendor'] = $vendor_name;
                $json_entry ['HSN Code'] = isset($model->tax)?$model->tax->hrn_code:"";
                $json_entry ['GST NO'] = $model->getVendorTAXNO();
                $json_entry ['Credit Note No'] = isset ( $model->itemReturn ) ? $model->itemReturn->credit_note_no: "";
                $json_entry ['Credit Note Date'] = isset ( $model->itemReturn ) ? $model->itemReturn->credit_note_date: "";


                $json_entry ['Bill No'] = isset ( $model->itemReturn ) ? $model->itemReturn->bill_no: "";

                $json_entry ['Supplier Invoice No'] = isset ( $model->itemReturn ) ? $model->itemReturn->invoice_no: "";
                $json_entry ['GST Rate'] = $model->getTotalGstPer();
                $json_entry ['CGST Rate'] = $model->cgst_per;
                $json_entry ['SGST Rate'] = $model->sgst_per;
                $json_entry ['CESS Rate'] = $model->cess_per;
                $json_entry ['IGST Rate'] = $model->igst_per;
                $json_entry ['Net Amount'] =isset ( $model->itemReturn ) ? $model->itemReturn->total_amt: "";
                // $json_entry ['Basic Value'] =  $model->price * $model->qty;
                $json_entry ['Basic Value'] =  ($model->price * $model->qty) - $model->discount_amt;

                // $json_entry ['Discount'] = isset ( $model->itemReturn ) ? $model->itemReturn->discount_amt: "";
                // $json_entry ['Discount'] = $model->discount_amt;
                $json_entry ['Discount'] = 0;
                $json_entry ['GST'] = $model->cgst_amt + $model->sgst_amt + $model->cess_amt + $model->igst_amt;
                $json_entry ['CGST'] = $model->cgst_amt;
                $json_entry ['SGST'] = $model->sgst_amt;
                $json_entry ['IGST'] = $model->igst_amt;
                $json_entry ['CESS'] = $model->cess_amt;
                $json_entry ['GRN NUMBER'] =  $model->getCustomGRNNumber() ;

                //isset ( $model->itemReturn ) ? $model->itemReturn->grn_no: "";

            }
            return $json_entry;
        }

    public function getCreateUser()
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
    }

    public function getItemDetail()
    {
        return $this->hasOne(ItemDetail::class, ['id' => 'item_detail_id']);
    }

    public function getItem()
    {
        return $this->hasOne(Item::class, ['id' => 'item_id']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
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
            [['columns', 'bar_code', 'item_val_id', 'start_date', 'credit_note_no', 'credit_note_date'], 'safe'],  // form-only, declared on the Yii 1 model
            [['tally_start_date', 'tally_end_date'], 'safe'],  // form-only, declared on the Yii 1 model
            [['price', 'tax_id', 'vendor_id', 'outlet_id', 'return_id', 'create_time', 'create_user_id'], 'required'],
            [['item_id', 'item_detail_id', 'free', 'tax_id', 'vendor_id', 'outlet_id', 'status', 'type_id', 'return_id', 'create_user_id', 'updated_by'], 'integer'],
            [['price', 'discount', 'discount_amt', 'discount1', 'discount_amt1', 'cgst_per', 'sgst_per', 'cess_per', 'cgst_amt', 'sgst_amt', 'cess_amt', 'igst_per', 'igst_amt', 'other_charge'], 'number'],
            [['margin', 'tally_end_date', 'tally_start_date'], 'safe'],
            [['mrp', 'sale_rate', 'total_amt'], 'string', 'max' => 10],
            [['item_id', 'item_detail_id', 'mrp', 'sale_rate', 'free', 'qty', 'discount1', 'discount_amt1', 'igst_per', 'igst_amt', 'total_amt', 'status', 'type_id', 'updated_by'], 'default', 'value' => null],
            [['id', 'item_id', 'item_detail_id', 'mrp', 'price', 'sale_rate', 'free', 'qty', 'discount', 'discount_amt', 'discount1', 'discount_amt1', 'cgst_per', 'sgst_per', 'cess_per', 'cgst_amt', 'sgst_amt', 'cess_amt', 'igst_per', 'igst_amt', 'tax_id', 'other_charge', 'total_amt', 'vendor_id', 'outlet_id', 'status', 'type_id', 'return_id', 'create_time', 'create_user_id', 'updated_by'], 'safe', 'on' => 'search'],
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
            // Yii 1 turns pagination off for this one: every matching
            // row on one page, and no pager.
            'pagination' => false,
        ]);

        if (self::listingOrder()) {
            $query->orderBy(self::listingOrder());
        }

        $this->load($params, $this->formName());

        foreach ([['id', 'id'], ['item_id', 'item_id'], ['item_detail_id', 'item_detail_id'], ['price', 'price'], ['free', 'free'], ['qty', 'qty'], ['discount', 'discount'], ['discount_amt', 'discount_amt'], ['discount1', 'discount1'], ['discount_amt1', 'discount_amt1'], ['cgst_per', 'cgst_per'], ['sgst_per', 'sgst_per'], ['cess_per', 'cess_per'], ['cgst_amt', 'cgst_amt'], ['sgst_amt', 'sgst_amt'], ['cess_amt', 'cess_amt'], ['igst_per', 'igst_per'], ['igst_amt', 'igst_amt'], ['tax_id', 'tax_id'], ['other_charge', 'other_charge'], ['vendor_id', 'vendor_id'], ['outlet_id', 'outlet_id'], ['status', 'status'], ['type_id', 'type_id'], ['return_id', 'return_id'], ['create_user_id', 'create_user_id'], ['updated_by', 'updated_by']] as [$col, $attr]) {
            Criteria::compare($query, $col, $this->$attr);
        }
        foreach ([['mrp', 'mrp'], ['sale_rate', 'sale_rate'], ['total_amt', 'total_amt'], ['create_time', 'create_time']] as [$col, $attr]) {
            Criteria::compare($query, $col, $this->$attr, true);
        }

        return $provider;
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
     * Yii 1's reportsearch(): a listing of its own, converted as written.
     */
    public function reportsearch()
    {

		$query = self::find();
		$query->orderBy(['id' => SORT_DESC]);
	if ((Yii::$app->session ['returnitem_start_date'] != '') && (Yii::$app->session ['returnitem_end_date'] != '')) {
			$query->andWhere(['between', 'date(create_time)', Yii::$app->session ['returnitem_start_date'], Yii::$app->session ['returnitem_end_date']]);
		}
		Criteria::compare($query, 'id', $this->id);
		Criteria::compare($query, 'item_id', $this->item_id);
		Criteria::compare($query, 'item_detail_id', $this->item_detail_id);
		Criteria::compare($query, 'mrp', $this->mrp, true);
		Criteria::compare($query, 'price', $this->price);
		Criteria::compare($query, 'sale_rate', $this->sale_rate, true);
		Criteria::compare($query, 'free', $this->free);
		Criteria::compare($query, 'qty', $this->qty);
		Criteria::compare($query, 'discount', $this->discount);
		Criteria::compare($query, 'discount_amt', $this->discount_amt);
		Criteria::compare($query, 'discount1', $this->discount1);
		Criteria::compare($query, 'discount_amt1', $this->discount_amt1);
		Criteria::compare($query, 'cgst_per', $this->cgst_per);
		Criteria::compare($query, 'sgst_per', $this->sgst_per);
		Criteria::compare($query, 'cess_per', $this->cess_per);
		Criteria::compare($query, 'cgst_amt', $this->cgst_amt);
		Criteria::compare($query, 'sgst_amt', $this->sgst_amt);
		Criteria::compare($query, 'cess_amt', $this->cess_amt);
		Criteria::compare($query, 'igst_per', $this->igst_per);
		Criteria::compare($query, 'igst_amt', $this->igst_amt);
		Criteria::compare($query, 'tax_id', $this->tax_id);
		Criteria::compare($query, 'other_charge', $this->other_charge);
		Criteria::compare($query, 'total_amt', $this->total_amt, true);
		Criteria::compare($query, 'vendor_id', $this->vendor_id);
		Criteria::compare($query, 'outlet_id', $this->outlet_id);
		Criteria::compare($query, 'status', $this->status);
		Criteria::compare($query, 'type_id', $this->type_id);
		Criteria::compare($query, 'return_id', $this->return_id);
		//$criteria->compare ( 'create_time', $this->create_time, true );
		Criteria::compare($query, 'create_user_id', $this->create_user_id);
		Criteria::compare($query, 'updated_by', $this->updated_by);
		
		return new ActiveDataProvider([
		    'query' => $query,
		    'sort' => ['defaultOrder' => []],
		    'pagination' => false,
		]);
    }
}
