<?php
namespace app\models;

use app\components\Ui;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/** Ported from protected/models/PurchaseBill.php (Yii 1). */
class PurchaseBill extends ActiveRecord
{
    public const IS_CONSIGNMENT_CHECK = 1;
    public const IS_CONSIGNMENT = 1;
    public const PAYMENT_DONE = 1;
    public const PAYMENT_PENDING = 0;
    public const STATUS_UNAPPROVED = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_RECEIVED = 2;

    public static function tableName()
    {
        return '{{%purchase_bill}}';
    }

    public function getVendor()
    {
        return $this->hasOne(Vendor::class, ['id' => 'vendor_id']);
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'PurchaseBill' : 'PurchaseBills';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'code';
    }

    /** GxActiveRecord::__toString(): the representing column, or the id. */
    public function __toString()
    {
        $value = $this->hasAttribute('code') ? $this->code : null;

        return (string) ($value === null || $value === '' ? $this->id : $value);
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

    public static function getStatusOptions($id = null)
    {
		$list = ["UnApproved","Approved","Received"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public static function getTypeOptions($id = null)
    {
		$list = ["TYPE1","TYPE2","TYPE3"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public function getPOVendorOptions(){
            $list = [];
            $query = PurchaseBill::find();
            $query->andWhere('status ='.PurchaseBill::STATUS_UNAPPROVED);
            $mrss = $query->all();
            if($mrss){
                foreach($mrss as $mrs){
                    $vendor = Vendor::findOne($mrs->vendor_id);
                    if($vendor){
                        $list[$vendor->id] = $vendor->name;
                    }
                }
            }
            return $list;
        }

    public function getConsignmentOptions() {
            $model = new PurchaseBill ();
            $model->getConsignmentData ();
            $criteria = new CDbCriteria ();
            $criteria->addCondition ( 'is_consignment =' . PurchaseBill::IS_CONSIGNMENT );
            $criteria->addCondition ( 'is_consignment_checked !=' . PurchaseBill::IS_CONSIGNMENT_CHECK );
            $criteria->addCondition ( 'status =' . PurchaseBill::STATUS_APPROVED );
            $purchaseBills = PurchaseBill::model ()->findAll ( $criteria );

            Yii::log ( CVarDumper::dumpAsString ( $purchaseBills ), CLogger::LEVEL_WARNING, '$purchaseBills' );
            if ($purchaseBills) {
                foreach ( $purchaseBills as $purchaseBill ) {

                    $criteria1 = new CDbCriteria ();
                    $criteria1->addCondition ( 'is_consignment_checked !=' . PurchaseBill::IS_CONSIGNMENT_CHECK );
                    $criteria1->addCondition ( 'purchase_bill_id =' . $purchaseBill->id );
                    $purchaseBillDetails = PurchaseBillDetail::model ()->findAll ( $criteria1 );

                    Yii::log ( CVarDumper::dumpAsString ( $purchaseBillDetails ), CLogger::LEVEL_WARNING, '$purchaseBillDetails' );
                    if ($purchaseBillDetails) {
                        foreach ( $purchaseBillDetails as $purchaseBillDetail ) {
                            $date = $purchaseBill->end_date;
                            $criteria2 = new CDbCriteria ();
                            $criteria2->addCondition ( 'item_id =' . $purchaseBillDetail->item_id );
                            $criteria2->addCondition ( 'date(create_time) >=' . "'" . $date . "'" );
                            $criteria2->addCondition ( 'item_detail_id =' . $purchaseBillDetail->item_detail_id );
                            $orderitems = OrderItem::model ()->findAll ( $criteria2 );

                            Yii::log ( CVarDumper::dumpAsString ( $orderitems ), CLogger::LEVEL_WARNING, '$orderitems' );
                            if ($orderitems) {
                                $qty = 0;
                                foreach ( $orderitems as $orderitem ) {
                                    $addqty = $orderitem->qty;
                                    $orderrefund = OrderRefund::findOne( [
                                            'order_id' => $orderitem->order_id
                                    ] );
                                    if ($orderrefund) {

                                        $criteria3 = new CDbCriteria ();
                                        $criteria3->addCondition ( 'order_refund_id =' . $orderrefund->id );
                                        $criteria2->addCondition ( 'item_id =' . $orderitem->item_id );
                                        $criteria3->addCondition ( 'item_detail_id =' . $orderitem->item_detail_id );
                                        $orderrefunditems = OrderRefundItem::model ()->findAll ( $criteria3 );
                                        Yii::log ( CVarDumper::dumpAsString ( $orderrefunditems ), CLogger::LEVEL_WARNING, '$orderrefunditems' );
                                        if ($orderrefunditems) {
                                            $refundqty = 0;
                                            foreach ( $orderrefunditems as $orderrefunditem ) {
                                                $refundqty = $refundqty + $orderrefunditem->qty;
                                            }
                                            if ($refundqty < $addqty) {
                                                $addqty = $addqty - $refundqty;
                                            } else {
                                                $addqty = 0;
                                            }
                                        }
                                    }
                                    $qty = $qty + $addqty;
                                }

                                Yii::log ( CVarDumper::dumpAsString ( $addqty ), CLogger::LEVEL_WARNING, '$addqty' );

                                if ($qty > $purchaseBillDetail->approved_qty || $qty = $purchaseBillDetail->approved_qty) {
                                    $purchaseBillDetail->is_consignment_checked = PurchaseBill::IS_CONSIGNMENT_CHECK;
                                    $purchaseBillDetail->saveAttributes ( [
                                            'is_consignment_checked'
                                    ] );
                                }
                            }
                        }
                    } else {
                        $purchaseBill->is_consignment_checked = PurchaseBill::IS_CONSIGNMENT_CHECK;
                        $purchaseBill->start_date = date ( 'Y-m-d' );
                        $purchaseBill->saveAttributes ( [
                                'is_consignment_checked',
                                'start_date'
                        ] );
                    }
                }
            }
        }

    public function getCreateUser()
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
    }

    public function getOrganization()
    {
        return $this->hasOne(Organization::class, ['id' => 'organization_id']);
    }

    public function getOutlet()
    {
        return $this->hasOne(Outlet::class, ['id' => 'outlet_id']);
    }

    public function getPurchaseOrder()
    {
        return $this->hasOne(PurchaseOrder::class, ['id' => 'purchase_order_id']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }

    public function getPurchaseBillDetails()
    {
        return $this->hasMany(PurchaseBillDetail::class, ['purchase_bill_id' => 'id']);
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
            'id' => 'Grn No',
            'code' => 'Code',
            'start_date' => 'Grn Date',
            'end_date' => 'Bill Date',
            'receiving_date' => 'Receiving Date',
            'status' => 'Status',
            'type_id' => 'Type',
            'is_open_po' => 'Is Open Po',
            'is_po_received' => 'Is Po Received',
            'remarks' => 'Remarks',
            'payment_terms' => 'Payment Terms',
            'transport_mode' => 'Transport Mode',
            'gross_amt' => 'Gross Amt (without Tax Amount)',
            'net_bill_amount' => 'Gross Net Bill Amount <small>(Gross Amt + Tax Amount - Total Discount - Bill Other Discount)</small>',
            'purchase_order_amount' => 'Purchase Order Amount',
            'charges_total_amount' => 'Charges Total Amount',
            'credit_note_disc' => 'Credit Note Discount',
            'discount_amount' => 'Discount Amount',
            'frieght_charges' => 'Frieght Charges',
            'extra_charges' => 'Extra Charges',
            'total_amount' => 'Total Amount',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'print_id' => 'Item',
            'create_user_id' => 'User',
            'updated_by' => 'User',
            'outlet_id' => 'Outlet',
            'vendor_id' => 'Vendor',
            'purchase_order_id' => 'PurchaseOrder',
            'organization_id' => 'Organization',
            'createUser' => 'Create User',
            'organization' => 'Organization',
            'outlet' => 'Outlet',
            'purchaseOrder' => 'PurchaseOrder',
            'updatedBy' => 'Updated By',
            'vendor' => 'Vendor',
            'purchaseBillDetails' => 'PurchaseBillDetails',
        ];
    }

    public function getVendorEmail() {
            $email = '';
            if ($this->vendor) {
                $user = User::findOne( $this->vendor->create_user_id );
                if ($user) {
                    $email = $user->email;
                }
            }
            return $email;
        }

    public function isSold() {
        }

    public function getColumns($selectcolumns = []) {
            if (! empty ( $selectcolumns )) {
                $selected = $selectcolumns;
            } else {
                $selected = [
                        'transaction_type',
                        'ben_code',
                        'ben_acc_no',
                        'instrument_amt',
                        'ben_name',
                        'drawee_loc',
                        'print_loc',
                        'ben_add1',
                        'ben_add2',
                        'ben_add3',
                        'ben_add4',
                        'ben_add5',
                        'Inst_ref_no',
                        'customer_refrence_no',
                        'pay_detail1',
                        'pay_detail2',
                        'pay_detail3',
                        'pay_detail4',
                        'pay_detail5',
                        'pay_detail6',
                        'pay_detail7',
                        'cheque_no',
                        'chq' ,
                        'micr_no' ,
                        'ifsc_code' ,
                        'bene_bank_name',
                        'bene_branch_name' ,
                        'beneficiary_email',
                        /* 'transaction_type',
                        'ben_code',
                        'ben_name',
                        'instrument_amt',
                        'cheque_no',
                        'chq',
                        'customer_refrence_no',
                        'pay_detail1',
                        'pay_detail2',
                        'ben_acc_no',
                        'Inst_ref_no',
                        'transaction_status',
                        'reject_reason',
                        'ifsc_code',
                        'micr_no',
                        'utr_no', */


                        /* 'drawee_loc',
                        'print_loc',
                        'ben_add1',
                        'ben_add2',
                        'ben_add3',
                        'ben_add4',
                        'ben_add5',
                        'Inst_ref_no',


                        'pay_detail3',
                        'pay_detail4',
                        'pay_detail5',
                        'pay_detail6',
                        'pay_detail7',




                        'bene_bank_name',
                        'bene_branch_name',
                        'beneficiary_email' */
                ]
                ;
            }

            if ($selected) {
                foreach ( $selected as $select ) {
                    if ($select == 'transaction_type') {
                        $columns [] = [
                                'label' => 'Transaction Type (N � NFET, R � RTGS)',
                                'value' => function ($data) {
                                    return "N";
                                }
                        ];
                    } else if ($select == 'ben_code') {
                        $columns [] = [
                                'label' => 'Beneficiary Code',
                                'value' => function ($data) {
                                    return '';
                                }
                        ];
                    } else if ($select == 'ben_acc_no') {
                        $columns [] = [
                                'label' => 'Beneficiary Account Number',
                                'value' => function ($data) {
                                    return isset ( $data->vendor ) ? "'".$data->vendor->acc_no ."'": "";
                                }
                        ];
                    } else if ($select == 'instrument_amt') {
                        $columns [] = [
                                'label' => 'Instrument Amount',
                                'value' => function ($data) {
                                    return isset ( $data->net_bill_amount ) ? $data->net_bill_amount : "";
                                }
                        ];
                    } else if ($select == 'ben_name') {
                        $columns [] = [
                                'label' => 'Beneficiary Name (Upto 40 character withput any special character)',
                                'value' => function ($data) {

                                    return isset ( $data->vendor ) ? $data->clean ( $data->vendor->name ) : "";
                                }
                        ];
                    } else if ($select == 'drawee_loc') {
                        $columns [] = [
                                'label' => 'Drawee Location',
                                'value' => function ($data) {
                                    return '';
                                }
                        ];
                    } else if ($select == 'print_loc') {
                        $columns [] = [
                                'label' => 'Print Location',
                                'value' => function ($data) {
                                    return '';
                                }
                        ];
                    } else if ($select == 'ben_add1') {
                        $columns [] = [
                                'label' => 'Bene Address 1',
                                'value' => function ($data) {
                                    return '';
                                }
                        ];
                    } else if ($select == 'ben_add2') {
                        $columns [] = [
                                'label' => 'Bene Address 2',
                                'value' => function ($data) {
                                    return '';
                                }
                        ];
                    } else if ($select == 'ben_add3') {
                        $columns [] = [
                                'label' => 'Bene Address 3',
                                'value' => function ($data) {
                                    return '';
                                }
                        ];
                    } else if ($select == 'ben_add4') {
                        $columns [] = [
                                'label' => 'Bene Address 4',
                                'value' => function ($data) {
                                    return '';
                                }
                        ];
                    } else if ($select == 'ben_add5') {
                        $columns [] = [
                                'label' => 'Bene Address 5',
                                'value' => function ($data) {
                                    return '';
                                }
                        ];
                    } else if ($select == 'Inst_ref_no') {
                        $columns [] = [
                                'label' => 'Bank Reference Number',
                                'value' => function ($data) {
                                    return '';
                                }
                        ];
                    } else if ($select == 'customer_refrence_no') {
                        $columns [] = [
                                'label' => 'Customer Reference Number(Any alpha numeric character upto 20)',
                                'value' => function ($data) {
                                    return isset ( $data->bill_no ) ? "'".$data->bill_no."'": "";
                                }
                        ];
                    } else if ($select == 'pay_detail1') {
                        $columns [] = [
                                'label' => 'Payment details 1',
                                'value' => function ($data) {
                                return isset ( $data->purchaseBill ) ? 'Gr-'.$data->purchaseBill->grn_refrence_no: "";
                                }
                        ];
                    } else if ($select == 'pay_detail2') {
                        $columns [] = [
                                'label' => 'Payment details 2',
                                'value' => function ($data) {
                                    return '';
                                }
                        ];
                    } else if ($select == 'pay_detail3') {
                        $columns [] = [
                                'label' => 'Payment details 3',
                                'value' => function ($data) {
                                    return '';
                                }
                        ];
                    } else if ($select == 'pay_detail4') {
                        $columns [] = [
                                'label' => 'Payment details 4',
                                'value' => function ($data) {
                                    return '';
                                }
                        ];
                    } else if ($select == 'pay_detail5') {
                        $columns [] = [
                                'label' => 'Payment details 5',
                                'value' => function ($data) {
                                    return '';
                                }
                        ];
                    } else if ($select == 'pay_detail6') {
                        $columns [] = [
                                'label' => 'Payment details 6',
                                'value' => function ($data) {
                                    return '';
                                }
                        ];
                    } else if ($select == 'pay_detail7') {
                        $columns [] = [
                                'label' => 'Payment details 7',
                                'value' => function ($data) {
                                    return '';
                                }
                        ];
                    } else if ($select == 'cheque_no') {
                        $columns [] = [
                                'label' => 'Cheque Number',
                                'value' => function ($data) {
                                    return '';
                                }
                        ];
                    } else if ($select == 'chq') {
                        $columns [] = [
                                'label' => 'Chq / Trn Date (DD/MM/YYYY)',
                                'value' => function ($data) {
                                    return isset ( $data->start_date ) ? $data->getChqDate () : "";
                                }
                        ];
                    } else if ($select == 'micr_no') {
                        $columns [] = [
                                'label' => 'Micr Code',
                                'value' => function ($data) {
                                    return '';
                                }
                        ];
                    }
                    else if ($select == 'transaction_status') {
                        $columns [] = [
                                'label' => 'Transaction Status',
                                'value' => function ($data) {
                                return '';
                                }
                                ];
                    }
                    else if ($select == 'reject_reason') {
                        $columns [] = [
                                'label' => 'Reject Reason',
                                'value' => function ($data) {
                                return '';
                                }
                                ];
                    }
                    else if ($select == 'utr_no') {
                        $columns [] = [
                                'label' => 'UTR no for RTGS',
                                'value' => function ($data) {
                                return '';
                                }
                                ];
                    }else if ($select == 'ifsc_code') {
                        $columns [] = [
                                'label' => 'IFSC Code',
                                'value' => function ($data) {
                                    return isset ( $data->vendor ) ? $data->vendor->ifsc : "";
                                }
                        ];
                    } else if ($select == 'bene_bank_name') {
                        $columns [] = [
                                'label' => 'Bene Bank Name',
                                'value' => function ($data) {
                                    return isset ( $data->vendor ) ? $data->vendor->bank_name : "";
                                }
                        ];
                    } else if ($select == 'bene_branch_name') {
                        $columns [] = [
                                'label' => 'Bene Bank Branch Name',
                                'value' => function ($data) {
                                    return isset ( $data->vendor ) ? $data->vendor->bank_name : "";
                                }
                        ];
                    } else if ($select == 'beneficiary_email') {
                        $columns [] = [
                                'label' => 'Beneficiary email id',
                                'value' => function ($data) {
                                    return $data->getVendorEmail ();
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

    public function clean($string) {
            $string = str_replace ( ' ', '', $string ); // Replaces all spaces with hyphens.

            return preg_replace ( '/[^A-Za-z0-9\-]/', '', $string ); // Removes special chars.
        }

    public function getBillDiscountAmount() {
            $discount = 0;
            $purchaseBilldetails = PurchaseBillDetail::findAll( [
                    'purchase_bill_id' => $this->id
            ] );
            if ($purchaseBilldetails) {
                foreach ( $purchaseBilldetails as $purchaseBilldetail ) {
                    $discount = $discount + $purchaseBilldetail->discount_amt;
                }
            }
            return $discount;
        }

    public function getTotalAmount() {
            $discount = 0;
            $purchaseBilldetails = PurchaseBillDetail::findAll( [
                    'purchase_bill_id' => $this->id
            ] );
            if ($purchaseBilldetails) {
                foreach ( $purchaseBilldetails as $purchaseBilldetail ) {
                    $discount = $discount + $purchaseBilldetail->amount;
                }
            }
            return $discount;
        }

    public function getChqDate() {

            $chq_date = '';
            $set = false;
            if ($this->is_consignment != PurchaseBill::IS_CONSIGNMENT) {
                $set = true;
            } else {
                if (($this->is_consignment == PurchaseBill::IS_CONSIGNMENT) && ($this->is_consignment_checked == PurchaseBill::IS_CONSIGNMENT_CHECK)) {
                    $set = true;
                }
            }
            if ($set == true) {
                $Date = $this->start_date;
                $days = $this->payment_days;
                if ($days != 0) {
                    $chq_date = date ( 'Y-m-d', strtotime ( $Date . ' + ' . $days . ' days' ) );
                } else {
                    $chq_date = $this->start_date;
                }
            }

            return $chq_date;
        }

    public function getAdvancePaymentValue() {
            $vendor_id = $this->vendor_id;
            $amount = $this->net_bill_amount;
            $vendor = Vendor::findOne( $vendor_id );
            if ($vendor) {


                if ($vendor->is_advance_payment == Vendor::ADVANCE_PAYMENT) {
                    $advancePayment = AdvancePayment::findOne( [
                            'vendor_id' => $vendor_id
                    ] );

                    if ($advancePayment) {
                        if (($advancePayment->balance_amt >= $amount)) {
                            return true;
                        } else {
                            return false;
                        }
                    } else {
                        return false;
                    }
                } else {
                    return true;
                }
            } else {
                return false;
            }
        }

    public function getPurchasePrintDetails() {
            $bill_detail_ids = [];
            $list = [];
            if (isset ( Yii::$app->session ['billidList'] ) && (Yii::$app->session ['billidList'] != '')) {
                $query = PurchaseBillDetail::find();
                $query->andWhere(['id' => Yii::$app->session ['billidList']]);
                $purchasebilldetails = $query->all();
                if ($purchasebilldetails) {
                    foreach ( $purchasebilldetails as $purchasebilldetail ) {
                        $list [$purchasebilldetail->id] = isset ( $purchasebilldetail->item ) ? $purchasebilldetail->item : "";
                    }
                }
            }
            return $list;
        }
}
