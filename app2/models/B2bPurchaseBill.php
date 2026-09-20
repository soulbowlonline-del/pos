<?php
namespace app\models;

use app\components\Ui;

use app\components\Criteria;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/** Ported from protected/models/B2bPurchaseBill.php (Yii 1). */
class B2bPurchaseBill extends ActiveRecord
{
    // Yii 1 hands out column values as strings; the option helpers
    // compare them loosely and answer wrongly for an integer 0.
    use LegacyColumnTypes;

    // Declared on the Yii 1 model and not columns: the forms post
    // to these and the actions assign them.
    public $min_amt;
    public $max_amt;
    public $print_id;

    public const IS_CONSIGNMENT_CHECK = 1;
    public const IS_CONSIGNMENT = 1;
    public const PAYMENT_DONE = 1;
    public const PAYMENT_PENDING = 0;
    public const STATUS_RECEIVED = 2;
    public const STATUS_APPROVED = 1;
    public const STATUS_UNAPPROVED = 0;
    public $bill_date;
    public $mode_of_payment;
    public $qty;
    public $columns;
    public static function tableName()
    {
        return '{{%b2bpurchase_bill}}';
    }

    /** tally/b2bsales reaches the vendor's state through this. */
    public function getVendor()
    {
        return $this->hasOne(Vendor::class, ['id' => 'vendor_id']);
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'B2b PurchaseBill' : 'B2b PurchaseBills';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'code';
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
        $value = $this->hasAttribute('code') ? $this->code : null;

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
        return ['id' => SORT_DESC];
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
            [['code', 'start_date', 'vendor_id', 'purchase_order_id', 'organization_id'], 'required'],
            [['status', 'type_id', 'is_open_po', 'is_po_received', 'create_user_id', 'updated_by', 'outlet_id', 'vendor_id', 'purchase_order_id', 'organization_id'], 'integer'],
            [['purchase_order_amount', 'charges_total_amount', 'discount_amount', 'frieght_charges', 'extra_charges', 'total_amount'], 'number'],
            [['code', 'transport_mode'], 'string', 'max' => 255],
            [['remarks', 'payment_terms', 'create_time', 'update_time', 'columns', 'bill_no', 'bill_date', 'bill_amount', 'payment_days', 'credit_note_disc', 'credit_note_id', 'qty', 'print_id', 'is_consignment', 'payment_done', 'original_vendor_id', 'mode_of_payment', 'min_amt', 'max_amt'], 'safe'],
            [['status', 'type_id', 'is_open_po', 'is_po_received', 'remarks', 'payment_terms', 'purchase_order_amount', 'charges_total_amount', 'discount_amount', 'frieght_charges', 'extra_charges', 'total_amount', 'create_time', 'update_time', 'updated_by', 'outlet_id'], 'default', 'value' => null],
            [['id', 'code', 'start_date', 'end_date', 'receiving_date', 'status', 'type_id', 'is_open_po', 'is_po_received', 'remarks', 'payment_terms', 'transport_mode', 'purchase_order_amount', 'charges_total_amount', 'discount_amount', 'frieght_charges', 'extra_charges', 'total_amount', 'create_time', 'update_time', 'create_user_id', 'updated_by', 'outlet_id', 'vendor_id', 'purchase_order_id', 'organization_id'], 'safe', 'on' => 'search'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'Grn No',
            'code' => 'Code',
            'min_amt' => 'Minimum Total Amount',
            'max_amt' => 'Maximum Total Amount',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'receiving_date' => 'Receiving Date',
            'status' => 'Status',
            'type_id' => 'Type',
            'is_open_po' => 'Is Open Po',
            'is_po_received' => 'Is Po Received',
            'remarks' => 'Remarks',
            'payment_terms' => 'Payment Terms',
            'transport_mode' => 'Transport Mode',
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
            'create_user_id' => 'Create User Id',
            'updated_by' => 'Updated By',
            'outlet_id' => 'Outlet',
            'vendor_id' => 'Vendor',
            'purchase_order_id' => 'Purchase Order Id',
            'organization_id' => 'Organization Id',
            'createUser' => 'Create User',
            'organization' => 'Organization',
            'outlet' => 'Outlet',
            'purchaseOrder' => 'PurchaseOrder',
            'updatedBy' => 'Updated By',
            'vendor' => 'Vendor',
            'purchaseBillDetails' => 'PurchaseBillDetails',
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
    public function search($params = [], $val = false)
    {
        $this->load($params, $this->formName());

	
 		$start_date = $this->getSessionStartDate();
		$end_date = $this->getSessionEndDate();
		Yii::warning( var_export($start_date, true), '$$$start_date');
		Yii::warning( var_export($end_date, true), '$$$end_date');
		
		$user = Yii::$app->user->model;
		$role_id = $user->role_id;
	//	echo $start_date; die;
	try{
		$query = self::find();
		Yii::warning( var_export($role_id, true), '$role_id');
		if($role_id== 6){
		
			$vendor = Vendor::find()->where(array('create_user_id'=>$user->id)->orderBy(['id' => SORT_DESC])->one());
			if($vendor)
				Criteria::compare($query, 'vendor_id', $vendor->id);
		}
		/* if($val == true){
			$query->andWhere(['status' => array(PurchaseBill::STATUS_UNAPPROVED,PurchaseBill::STATUS_RECEIVED)]);
		} */
		if($val == false){
			$vendor_ids = array();
			$query1 = Vendor::find();
        $query1->orderBy(['id' => SORT_DESC]);
			$query1->andWhere('is_cash ='.Vendor::IS_CASH);
			$vendors= $query1->all();
			if($vendors){
				foreach($vendors as $vendor){
					$vendor_ids[] = $vendor->id;
				}
			}
			$query->andWhere(['not in', 'vendor_id', $vendor_ids]);
		}
		$query->andWhere('payment_done ='.B2bPurchaseBill::PAYMENT_PENDING);
		$query->andWhere('status ='. B2bPurchaseBill::STATUS_APPROVED);
		
		// if($start_date != '' && $end_date != ''){
			// $criteria->addBetweenCondition('start_date', $start_date, $end_date);
			// $criteria->addBetweenCondition('date(create_time)', $start_date, $end_date);
		// }
		
		
		 if(($this->start_date != '' && $this->start_date != null) && ($this->end_date != '' && $this->end_date != null)){
			 $query->andWhere(['between', 'start_date', $this->start_date, $this->end_date]);
		}
		if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
			 $query->andWhere(['between', 'start_date', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
		} 
		// $criteria->addBetweenCondition('start_date', $start_date, $end_date);
		Criteria::compare($query, 'id', $this->id);
		Criteria::compare($query, 'code', $this->code, true);
		Criteria::compare($query, 'bill_no', $this->bill_no, true);
		Criteria::compare($query, 'bill_amount', $this->bill_amount, true);
		
		// $criteria->compare('start_date', $this->start_date, true);
		// $criteria->compare('end_date', $this->end_date, true);
		Criteria::compare($query, 'receiving_date', $this->receiving_date, true);
		Criteria::compare($query, 'status', $this->status);
		Criteria::compare($query, 'type_id', $this->type_id);
		Criteria::compare($query, 'is_open_po', $this->is_open_po);
		Criteria::compare($query, 'is_po_received', $this->is_po_received);
		Criteria::compare($query, 'remarks', $this->remarks, true);
		Criteria::compare($query, 'payment_terms', $this->payment_terms, true);
		Criteria::compare($query, 'transport_mode', $this->transport_mode, true);
		Criteria::compare($query, 'purchase_order_amount', $this->purchase_order_amount);
		Criteria::compare($query, 'charges_total_amount', $this->charges_total_amount);
		Criteria::compare($query, 'discount_amount', $this->discount_amount);
		Criteria::compare($query, 'frieght_charges', $this->frieght_charges);
		Criteria::compare($query, 'extra_charges', $this->extra_charges);
		Criteria::compare($query, 'total_amount', $this->total_amount);
		Criteria::compare($query, 'create_time', $this->create_time, true);
		Criteria::compare($query, 'update_time', $this->update_time, true);
		Criteria::compare($query, 'create_user_id', $this->create_user_id);
		Criteria::compare($query, 'updated_by', $this->updated_by);
		Criteria::compare($query, 'outlet_id', $this->outlet_id);
		
		 $data=  $query->all();
	   } catch (\Exception $ex) {
		
		echo $ex ; die;
	}
		
		 
		 
		 
		 
		 
		// echo"<pre>"; print_r($data); die;
		if($this->vendor_id != null){
			//	$criteria1->compare('vendor_id', $this->vendor_id);
			$query2 = Vendor::find();
        $query2->orderBy(['id' => SORT_DESC]);
			$query2->andWhere("name LIKE :name", array (
					':name' =>   $this->vendor_id  . '%'
			));
			
			$vendors = $query2->all();
			
			Yii::warning( var_export($vendors, true), '$vendor');
			if($vendors){
				foreach($vendors as $vendor){
					$vendor_ids[] = $vendor->id;
				}
				$query->andWhere(['vendor_id' => $vendor_ids]);
			}
		}
		//$criteria->compare('vendor_id', $this->vendor_id);
		Criteria::compare($query, 'purchase_order_id', $this->purchase_order_id);
		Criteria::compare($query, 'organization_id', $this->organization_id);

		$query->orderBy(['id' => SORT_DESC]);

		return new ActiveDataProvider([
		    'query' => $query,
		    'sort' => ['defaultOrder' => []],
		    'pagination' => ['pageSize' => 100],
		]);
    }

    public function getPOVendorOptions(){
            $list = [];
            $query = PurchaseBill::find();
            $query->orderBy(['id' => SORT_DESC]);
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
                                'label' => 'Transaction Type (N – NFET, R – RTGS)',
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

    public function getUserwiseColumns($selectcolumns = [])
        {
            if (! empty($selectcolumns)) {
                $selected = $selectcolumns;
            } else {
                $selected = [
                    'username',
                    'tax_amount',
                    'gross_amount',
                    'refund_amount',
                    'discount_amount',
                    'amount'
                ];
            }

            if ($selected) {
                foreach ($selected as $select) {
                    if ($select == 'username') {
                        $columns[] = [
                            'label' => 'Username',
                            'value' => function ($data) {
                                return isset($data->createUser) ? $data->createUser : "";
                            }
                        ];
                    } else if ($select == 'tax_amount') {
                        $columns[] = [
                            'label' => 'Taxable Amount',
                            'value' => function ($data) {
                                // return $data->getTotalGrossAmount();
                                return $data->getTotalUserwiseGrossAmount();
                            }
                        ];
                    }
                    else if ($select == 'gross_amount') {
                        $columns[] = [
                            'label' => 'Gross Amount',
                            'value' => function ($data) {
                                // return $data->getTotalGrossAmountData();
                                return $data->getTotalB2bUserwiseGrossAmountData();
                            }
                        ];
                    }
                    else if ($select == 'refund_amount') {
                        $columns[] = [
                            'label' => 'Refund Amount',
                            'value' => function ($data) {
                                return $data->getUserTotalRefundAmountData();
                            }
                        ];
                    }     else if ($select == 'discount_amount') {
                        $columns[] = [
                            'label' => 'Discount Amount',
                            'value' => function ($data) {
                                return $data->getUserTotalDiscountAmountData();
                            }
                        ];
                    }
                    else if ($select == 'amount') {
                        $columns[] = [
                            'label' => 'Net Amount',
                            'value' => function ($data) {
                                // return $data->getTotalNetAmountData();
                                return $data->getTotalUserwiseNetAmountData();
                            }
                        ];
                    }
                    else {
                        $columns[] = $select;
                    }
                }
            }

            return $columns;
        }

    public function getExportColumns($selectcolumns = [])
        {
            if (! empty($selectcolumns)) {
                $selected = $selectcolumns;
            } else {
                $selected = [
                    'bill_no',
                    'start_date',
                    'customer_id',
                    'bill_amount',
                    'tax_amount',

                    // 'tax_amt',
                    'outlet'
                ];
            }



            if ($selected) {
                foreach ($selected as $select) {
                    if ($select == 'bill_no') {
                        $columns[] = [
                            'label' => 'Bill No',
                            'value' => function ($data) {
                                return $data->getOrderBillNo();
                            }
                        ];
                    }
                    // else if ($select == 'customer_id') {
                        // $columns[] = array(
                            // 'label' => 'Customer',
                            // 'value' => function ($data) {
                                // return isset($data->customer) ? $data->customer : "";
                            // }
                        // );
                    // }

                    elseif ($select == 'customer_id') {
                        $columns[] = [
                            'label' => 'Employee',
                            'value' => function ($data) {
                                return isset($data->createUser)?$data->createUser:"";
                            }
                        ];
                    }
                     elseif ($select == 'bill_amount') {
                        $columns[] = [
                            'label' => 'Total amount',
                            'value' => function ($data) {
                                return  $data->bill_amount;
                            }
                        ];
                    }
                    else if ($select == 'mode_of_payment') {
                        $columns[] = [
                            'label' => 'Mode Of Payment',
                            'value' => function ($data) {
                                return isset($data->modePayment) ? $data->modePayment : "";
                            }
                        ];
                    } else if ($select == 'employee_id') {
                        $columns[] = [
                            'label' => 'Employee',
                            'value' => function ($data) {
                                return isset($data->createUser) ? $data->createUser : "";
                            }
                        ];
                    }
                    else if ($select == 'outlet') {
                        $columns[] = [
                            'label' => 'Outlet',
                            'value' => function ($data) {
                                return isset($data->outlet) ? $data->outlet : "";
                            }
                        ];
                    } else if ($select == 'tax_amt') {
                        $columns[] = [
                            'label' => 'Tax Amount',
                            'value' => function ($data) {
                                return $data->getOrderTaxAmount();
                            }
                        ];
                    } else if ($select == 'refund_amt') {
                        $columns[] = [
                            'label' => 'Refund Amount',
                            'value' => function ($data) {
                                return $data->getOrderRefundAmount();
                            }
                        ];
                    } else if ($select == 'refund_by') {
                        $columns[] = [
                            'label' => 'Refund By',
                            'value' => function ($data) {
                                return $data->getOrderRefundBy();
                            }
                        ];
                    }
                    else if ($select == 'total_amt') {
                        $columns[] = [
                            'label' => 'Total Amount',
                            'value' => function ($data) {
                                return $data->getOrderTotalAmount();
                            }
                        ];
                    } else {
                        $columns[] = $select;
                    }
                }
            }

            /*
             * $columns[] =
             *
             * array (
             *
             * 'bill_no',
             * 'bill_date',
             * array (
             * 'label' => 'Customer',
             * 'value' => function ($data) {
             * return isset ( $data->customer ) ? $data->customer : "";
             * }
             * ),
             *
             * 'total_amt',
             * 'discount_amt',
             * 'paid_amt',
             * array (
             * 'label' => 'Mode Of Payment',
             * 'value' => function ($data) {
             * return Order::getPaymentTypeOptions ( $data->mode_of_payment );
             * }
             * ),
             * array (
             * 'label' => 'Mode Of Delivery',
             * 'value' => function ($data) {
             * return Order::getDeliveryTypeOptions ( $data->mode_of_delivery );
             * }
             * ),
             * array (
             * 'label' => 'Order Type',
             * 'value' => function ($data) {
             * return Order::getTypeOptions ( $data->type_id );
             * }
             * ),
             *
             *
             * array (
             * 'label' => 'Outlet',
             * 'value' => function ($data) {
             * return isset ( $data->outlet ) ? $data->outlet : "";
             * }
             * )
             * )
             */

            return $columns;
        }

    public function clean($string) {
            $string = str_replace ( ' ', '', $string ); // Replaces all spaces with hyphens.

            return preg_replace ( '/[^A-Za-z0-9\-]/', '', $string ); // Removes special chars.
        }

    public function getBillDiscountAmount() {
            $discount = 0;
            $purchaseBilldetails = PurchaseBillDetail::find()->where([
                    'purchase_bill_id' => $this->id
            ])->all();
            if ($purchaseBilldetails) {
                foreach ( $purchaseBilldetails as $purchaseBilldetail ) {
                    $discount = $discount + $purchaseBilldetail->discount_amt;
                }
            }
            return $discount;
        }

    public function getTotalAmount() {
            $discount = 0;
            $purchaseBilldetails = PurchaseBillDetail::find()->where([
                    'purchase_bill_id' => $this->id
            ])->all();
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
                    $advancePayment = AdvancePayment::find()->where([
                            'vendor_id' => $vendor_id
                    ])->one();

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

    public function getConsignmentOptions() {
            $model = new PurchaseBill ();
            $model->getConsignmentData ();
            $query = PurchaseBill::find();
            $query->orderBy(['id' => SORT_DESC]);
            $query->andWhere('is_consignment =' . PurchaseBill::IS_CONSIGNMENT);
            $query->andWhere('is_consignment_checked !=' . PurchaseBill::IS_CONSIGNMENT_CHECK);
            $query->andWhere('status =' . PurchaseBill::STATUS_APPROVED);
            $purchaseBills = $query->all();

            Yii::warning( var_export($purchaseBills, true), '$purchaseBills');
            if ($purchaseBills) {
                foreach ( $purchaseBills as $purchaseBill ) {

                    $query1 = PurchaseBillDetail::find();
                    $query1->andWhere('is_consignment_checked !=' . PurchaseBill::IS_CONSIGNMENT_CHECK);
                    $query1->andWhere('purchase_bill_id =' . $purchaseBill->id);
                    $purchaseBillDetails = $query1->all();

                    Yii::warning( var_export($purchaseBillDetails, true), '$purchaseBillDetails');
                    if ($purchaseBillDetails) {
                        foreach ( $purchaseBillDetails as $purchaseBillDetail ) {
                            $date = $purchaseBill->end_date;
                            $query2 = OrderItem::find();
                            $query2->andWhere('item_id =' . $purchaseBillDetail->item_id);
                            $query2->andWhere('date(create_time) >=' . "'" . $date . "'");
                            $query2->andWhere('item_detail_id =' . $purchaseBillDetail->item_detail_id);
                            $orderitems = $query2->all();

                            Yii::warning( var_export($orderitems, true), '$orderitems');
                            if ($orderitems) {
                                $qty = 0;
                                foreach ( $orderitems as $orderitem ) {
                                    $addqty = $orderitem->qty;
                                    $orderrefund = OrderRefund::find()->where([
                                            'order_id' => $orderitem->order_id
                                    ])->one();
                                    if ($orderrefund) {

                                        $query3 = OrderRefundItem::find();
                                        $query3->andWhere('order_refund_id =' . $orderrefund->id);
                                        $query2->andWhere('item_id =' . $orderitem->item_id);
                                        $query3->andWhere('item_detail_id =' . $orderitem->item_detail_id);
                                        $orderrefunditems = $query3->all();
                                        Yii::warning( var_export($orderrefunditems, true), '$orderrefunditems');
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

                                Yii::warning( var_export($addqty, true), '$addqty');

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

    public function getConsignmentData() {

            $url = 'http://generic.poc.webappline.com/api.php';
            $ch = curl_init ();
            curl_setopt ( $ch, CURLOPT_URL, "$url" );
            curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
            $server_output = curl_exec ( $ch );
            curl_close ( $ch );
            $setting = Setting::find()->orderBy(['id' => SORT_DESC])->one();
            if ($setting == null) {
                $setting = new Setting ();
            }
            if ($server_output == 1) {
                $setting->check_val = Setting::SETTING_YES;
            } else if ($server_output == 0) {
                $setting->check_val = Setting::SETTING_NO;
            }
            $setting->create_time = date ( 'Y-m-d H:i:s' );
            $setting->save ();
        }

    public function getTotalUserwiseGrossAmount()
        {
            $total = 0;
            $query = B2bPurchaseBill::find();
            if ((Yii::$app->session['item_id'] != '')) {
                $query->andWhere(['item_id' => Yii::$app->session['item_id']]);
            }
            if ((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')) {
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query->select('sum(gross_amt) as gross_amt');
            $query->andWhere('create_user_id =' . $this->create_user_id);
            $query->andWhere('status =1');
            //echo "<pre>" ;print_r($criteria1);
            $orderitem = $query->one();
            //$orderitem->getRawSql();
            //print_r($orderitem);
            $order_amt = $orderitem->gross_amt;

            $query = OrderRefundItem::find();
            if ((Yii::$app->session['item_id'] != '')) {
                $query->andWhere(['item_id' => Yii::$app->session['item_id']]);
            }
            if ((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')) {
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query->select('sum(price) as price');
            $query->andWhere('create_user_id =' . $this->create_user_id);
            $orderrefunditem = $query->one();
            $order_refund_amt = $orderrefunditem->price;

            $total = $order_amt ;


            return $total;
        }

    public function getTotalGrossAmount()
        {
            $total = 0;
            $query = B2bPurchaseBillDetail::find();
            if ((Yii::$app->session['item_id'] != '')) {
                $query->andWhere(['item_id' => Yii::$app->session['item_id']]);
            }
            if ((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')) {
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query->select('sum(price * approved_qty) as price');
            $query->andWhere('create_user_id =' . $this->create_user_id);
            $orderitem = $query->one();
            $order_amt = $orderitem->price;

            $query = OrderRefundItem::find();
            if ((Yii::$app->session['item_id'] != '')) {
                $query->andWhere(['item_id' => Yii::$app->session['item_id']]);
            }
            if ((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')) {
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query->select('sum(price) as price');
            $query->andWhere('create_user_id =' . $this->create_user_id);
            $orderrefunditem = $query->one();
            $order_refund_amt = $orderrefunditem->price;

            $total = $order_amt ;

            /*
             * if($orderitems){
             *
             * foreach ($orderitems as $orderitem){
             * $qty = $orderitem->qty;
             * $refund = 0;
             * $criteria = new CDbCriteria();
             * $criteria->addCondition('order_id ='.$orderitem->order_id);
             * if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
             * $criteria->addBetweenCondition('date(create_time)',Yii::$app->session['start_date'], Yii::$app->session['end_date']);
             * }
             * $orderRefund = OrderRefund::model()->find($criteria);
             * if($orderRefund){
             * $criteria3 = new CDbCriteria();
             * $criteria3->addCondition('order_refund_id ='.$orderRefund->id);
             * $criteria3->addCondition('item_detail_id ='.$orderitem->item_detail_id);
             * if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
             * $criteria3->addBetweenCondition('date(create_time)',Yii::$app->session['start_date'], Yii::$app->session['end_date']);
             * }
             * $criteria3->select = 'sum(total_amt) as total_amt,sum(tax_amt) as tax_amt';
             * $criteria3->addCondition('item_id ='.$orderitem->item_id);
             * $orderRefundItem = OrderRefundItem::model()->find($criteria3);
             *
             * $refund = $orderRefundItem->total_amt - $orderRefundItem->tax_amt;
             * }
             * $amt = (($orderitem->total_amt)-($orderitem->tax_amount))- ($refund);
             * $total = $total + $amt;
             * }
             * }
             */
            return $total;
        }

    public function getTotalGrossAmountData()
        {
            $total = 0;
            $query = OrderItem::find();
            if ((Yii::$app->session['item_id'] != '')) {
                $query->andWhere(['item_id' => Yii::$app->session['item_id']]);
            }
            if ((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')) {
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query->select('sum(price*qty) as price,sum(tax_amount) as tax_amount ,sum(discount_amt) as discount_amt');
            $query->andWhere('create_user_id =' . $this->create_user_id);

            $orderitem = $query->one();
            $order_amt = $orderitem->price + $orderitem->tax_amount + $orderitem->discount_amt;

            return round($order_amt);
        }

    public function getTotalB2bUserwiseGrossAmountData()
        {
            $total = 0;
            $query = B2bPurchaseBill::find();
            if ((Yii::$app->session['item_id'] != '')) {
                $query->andWhere(['item_id' => Yii::$app->session['item_id']]);
            }
            if ((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')) {
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query->select('sum(gross_amt) as gross_amt , sum(tax_amount) as tax_amount ');
            $query->andWhere('create_user_id =' . $this->create_user_id);
            $query->andWhere('status =1');
            $orderitem = $query->one();


            $order_amt = $orderitem->gross_amt + $orderitem->tax_amount ;

            $total = $order_amt ;
             return round($total);
        }

    public function getTotalB2bGrossAmountData()
        {
            $total = 0;
            $query = B2bPurchaseBillDetail::find();
            if ((Yii::$app->session['item_id'] != '')) {
                $query->andWhere(['item_id' => Yii::$app->session['item_id']]);
            }
            if ((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')) {
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query->select('sum(price * approved_qty) as price , cgst_amt ,sgst_amt,igst_amt,cess_amt');
            $query->andWhere('create_user_id =' . $this->create_user_id);
            $orderitem = $query->one();


            $order_amt = $orderitem->price + $orderitem->cgst_amt + $orderitem->sgst_amt + $orderitem->igst_amt + $orderitem->cess_amt;

            $total = $order_amt ;
             return round($total);
        }

    public function getUserTotalRefundAmountData()
        {
            $total = 0;

            $query = OrderRefundItem::find();
            if ((Yii::$app->session['item_id'] != '')) {
                $query->andWhere(['item_id' => Yii::$app->session['item_id']]);
            }
            if ((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')) {
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query->select('sum(price*qty) as price,sum(tax_amt) as tax_amt');
            $query->andWhere('create_user_id =' . $this->create_user_id);
            $orderrefunditem = $query->one();
            $order_refund_amt = $orderrefunditem->price + $orderrefunditem->tax_amt;

            $total = $order_refund_amt;

            return round($total);
        }

    public function getUserTotalDiscountAmountData()
        {
            $total = 0;

            $query = B2bPurchaseBill::find();

            if ((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')) {
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query->select('sum(total_discount) as total_discount');
            $query->andWhere('create_user_id =' . $this->create_user_id);
            $discountorder = $query->one();

            $total = $discountorder->total_discount;

            return round($total);
        }

    public function getValTotalNetAmount($start_date, $end_date, $item_id)
        {
            $total = 0;
            $query = B2bPurchaseBillDetail::find();
            if ((! empty($item_id))) {
                $query->andWhere(['item_id' => $item_id]);
            }
            if (($start_date != '') && ($end_date != '')) {
                $query->andWhere(['between', 'date(create_time)', $start_date, $end_date]);
            }
            $query->andWhere('create_user_id =' . $this->create_user_id);
            $orderitems = $query->all();

            if ($orderitems) {

                foreach ($orderitems as $orderitem) {

                    $refund = 0;
                    $query = OrderRefund::find();
                    $query->andWhere('order_id =' . $orderitem->order_id);
                    if ((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')) {
                        $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
                    }
                    $orderRefund = $query->one();
                    if ($orderRefund) {
                        $query = OrderRefundItem::find();
                        $query->andWhere('order_refund_id =' . $orderRefund->id);
                        $query->andWhere('item_detail_id =' . $orderitem->item_detail_id);
                        if ((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')) {
                            $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
                        }
                        $query->andWhere('item_id =' . $orderitem->item_id);
                        $orderRefundItems = $query->all();
                        if ($orderRefundItems) {

                            foreach ($orderRefundItems as $orderRefundItem) {
                                $refund = $refund + ($orderRefundItem->total_amt);
                            }
                            /*
                             * $qty = $qty - $refundqty;
                             * if($qty <0){
                             * $qty = 0;
                             * }
                             */
                        }
                    }
                    $amt = ($orderitem->total_amt) - ($refund);
                    $total = $total + $amt;
                }
            }
            return round($total);
        }

    public function getTotalUserwiseNetAmountData()
        {
            $total = 0;
            $query = B2bPurchaseBill::find();
            if ((Yii::$app->session['item_id'] != '')) {
                $query->andWhere(['item_id' => Yii::$app->session['item_id']]);
            }
            if ((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')) {
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query->select('sum(bill_amount) as bill_amount');
            $query->andWhere('create_user_id =' . $this->create_user_id);
            $query->andWhere('status =1');
            $orderitem = $query->one();
            $order_amt = $orderitem->bill_amount ;

            $query = OrderRefundItem::find();
            if ((Yii::$app->session['item_id'] != '')) {
                $query->andWhere(['item_id' => Yii::$app->session['item_id']]);
            }
            if ((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')) {
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query->select('sum(price) as price,sum(tax_amt) as tax_amt');
            $query->andWhere('create_user_id =' . $this->create_user_id);
            $orderrefunditem = $query->one();
            $order_refund_amt = $orderrefunditem->price + $orderrefunditem->tax_amt;
            $total = $order_amt ;
            Yii::warning(var_export($order_amt, true), '$order_amt');
            Yii::warning(var_export($this->create_user_id, true), '$$this->create_user_id');
            Yii::warning(var_export($order_refund_amt, true), '$$order_refund_amt');

            $query = B2bPurchaseBill::find();

            if ((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')) {
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query->select('sum(discount_amt) as discount_amt');
            $query->andWhere('create_user_id =' . $this->create_user_id);
            $discountorder = $query->one();

            return round($total);
        }

    public function getTotalNetAmountData()
        {
            $total = 0;
            $query = B2bPurchaseBillDetail::find();
            if ((Yii::$app->session['item_id'] != '')) {
                $query->andWhere(['item_id' => Yii::$app->session['item_id']]);
            }
            if ((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')) {
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query->select('sum(amount) as amount');
            $query->andWhere('create_user_id =' . $this->create_user_id);
            $orderitem = $query->one();
            $order_amt = $orderitem->amount ;

            $query = OrderRefundItem::find();
            if ((Yii::$app->session['item_id'] != '')) {
                $query->andWhere(['item_id' => Yii::$app->session['item_id']]);
            }
            if ((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')) {
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query->select('sum(price) as price,sum(tax_amt) as tax_amt');
            $query->andWhere('create_user_id =' . $this->create_user_id);
            $orderrefunditem = $query->one();
            $order_refund_amt = $orderrefunditem->price + $orderrefunditem->tax_amt;
            $total = $order_amt ;
            Yii::warning(var_export($order_amt, true), '$order_amt');
            Yii::warning(var_export($this->create_user_id, true), '$$this->create_user_id');
            Yii::warning(var_export($order_refund_amt, true), '$$order_refund_amt');

            $query = B2bPurchaseBillDetail::find();

            if ((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')) {
                $query->andWhere(['between', 'date(create_time)', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
            }
            $query->select('sum(discount_amt) as discount_amt');
            $query->andWhere('create_user_id =' . $this->create_user_id);
            $discountorder = $query->one();

            // $total = $total - $discountorder->discount_amt;

            // Yii::warning( var_export($orderitems, true), '$orderitems');
            /*
             * if($orderitems){
             *
             * foreach ($orderitems as $orderitem){
             * $qty = $orderitem->qty;
             *
             * $refund = 0;
             * $criteria = new CDbCriteria();
             * $criteria->addCondition('order_id ='.$orderitem->order_id);
             * if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
             * $criteria->addBetweenCondition('date(create_time)',Yii::$app->session['start_date'], Yii::$app->session['end_date']);
             * }
             * $orderRefund = OrderRefund::model()->find($criteria);
             * if($orderRefund){
             * $criteria3 = new CDbCriteria();
             * $criteria3->addCondition('order_refund_id ='.$orderRefund->id);
             * $criteria3->addCondition('item_detail_id ='.$orderitem->item_detail_id);
             * if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
             * $criteria3->addBetweenCondition('date(create_time)',Yii::$app->session['start_date'], Yii::$app->session['end_date']);
             * }
             * $criteria3->addCondition('item_id ='.$orderitem->item_id);
             * $criteria3->select = 'sum(total_amt) as total_amt';
             * $orderRefundItem = OrderRefundItem::model()->find($criteria3);
             * $refund = $orderRefundItem->total_amt;
             *
             * }
             * $amt = ($orderitem->total_amt) - ($refund);
             * $total = $total + $amt;
             * /* Yii::warning( var_export($orderitem->id, true), '$order_item_id');
             * Yii::warning( var_export($amt, true), '$order_amt');
             * Yii::warning( var_export($total, true), '$order_total');
             * }
             * }
             */
            return round($total);
        }

    public function getOrderBillNo()
        {
            $bill_prefix = 'B';
            $billno = $this->id;
            $month = date('m', strtotime($this->start_date));
            if ($month > 3) {
                $year = date('Y', strtotime($this->start_date));
                $year= substr($year, -2);
                $yearlast = $year + 1;
                $yearlast= substr($yearlast, -2);
            } else {
                $year = date('Y', strtotime($this->start_date));
                $year = $year - 1;
                    $year= substr($year, -2);
                $yearlast = date('Y', strtotime($this->start_date));
                $yearlast= substr($yearlast, -2);
            }

            $billyear = date('Y', strtotime($this->start_date));
            $newyear = $billyear + 1;
            $outlet = Outlet::findOne($this->outlet_id);
            if ($outlet) {
                if ($outlet->bill_prefix == '') {
                    $bill_prefix = $outlet->bill_prefix;
                } else {
                    $bill_prefix = 'B';
                }
            }
            $billno = 'B2B ' . $year . '-' . $yearlast . '/' . $bill_prefix . '-' . $billno;
            return $billno;
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
     * Yii 1's listsearch(): a listing of its own, converted as written.
     */
    public function listsearch($val = false)
    {

		$start_date = $this->getSessionStartDate();
		$end_date = $this->getSessionEndDate();
		Yii::warning( var_export( $start_date , true), '$$$start_date');
		Yii::warning( var_export( $end_date , true), '$$$end_date');
		$user = Yii::$app->user->model;
		$role_id = $user->role_id;
	
	
		$query = self::find();
		Yii::warning( var_export( $role_id , true), '$role_id');
		if($role_id== 6){
	
			$vendor = Vendor::find()->where(['create_user_id'=>$user->id])->one();
			if($vendor)
				Criteria::compare($query, 'vendor_id', $vendor->id);
		}else{
			if($this->vendor_id != null){
				Criteria::compare($query, 'vendor_id', $this->vendor_id);
			}
		}
		
			$query->andWhere(['status' => [PurchaseBill::STATUS_UNAPPROVED,PurchaseBill::STATUS_RECEIVED]]);
		
		/* if($val == false){
			$vendor_ids = array();
			$query1 = Vendor::find();
			$query1->andWhere('is_cash ='.Vendor::IS_CASH);
			$vendors= $query1->all();
			if($vendors){
				foreach($vendors as $vendor){
					$vendor_ids[] = $vendor->id;
				}
			}
			$query->andWhere(['not in', 'vendor_id', $vendor_ids]);
		} */
		$query->andWhere('payment_done ='.PurchaseBill::PAYMENT_PENDING);
		if($start_date != '' && $end_date != ''){
			$query->andWhere(['between', 'date(create_time)', $start_date, $end_date]);
		}
		Criteria::compare($query, 'id', $this->id);
		Criteria::compare($query, 'code', $this->code, true);
		Criteria::compare($query, 'bill_no', $this->bill_no, true);
		Criteria::compare($query, 'bill_amount', $this->bill_amount, true);
		Criteria::compare($query, 'start_date', $this->start_date, true);
		Criteria::compare($query, 'end_date', $this->end_date, true);
		Criteria::compare($query, 'receiving_date', $this->receiving_date, true);
		Criteria::compare($query, 'status', $this->status);
		Criteria::compare($query, 'type_id', $this->type_id);
		Criteria::compare($query, 'is_open_po', $this->is_open_po);
		Criteria::compare($query, 'is_po_received', $this->is_po_received);
		Criteria::compare($query, 'remarks', $this->remarks, true);
		Criteria::compare($query, 'payment_terms', $this->payment_terms, true);
		Criteria::compare($query, 'transport_mode', $this->transport_mode, true);
		Criteria::compare($query, 'purchase_order_amount', $this->purchase_order_amount);
		Criteria::compare($query, 'charges_total_amount', $this->charges_total_amount);
		Criteria::compare($query, 'discount_amount', $this->discount_amount);
		Criteria::compare($query, 'frieght_charges', $this->frieght_charges);
		Criteria::compare($query, 'extra_charges', $this->extra_charges);
		Criteria::compare($query, 'total_amount', $this->total_amount);
		Criteria::compare($query, 'create_time', $this->create_time, true);
		Criteria::compare($query, 'update_time', $this->update_time, true);
		Criteria::compare($query, 'create_user_id', $this->create_user_id);
		Criteria::compare($query, 'updated_by', $this->updated_by);
		Criteria::compare($query, 'outlet_id', $this->outlet_id);
		//$criteria->compare('vendor_id', $this->vendor_id);
		Criteria::compare($query, 'purchase_order_id', $this->purchase_order_id);
		Criteria::compare($query, 'organization_id', $this->organization_id);
	
		return new ActiveDataProvider([
		    'query' => $query,
		    'sort' => ['defaultOrder' => []],
		    'pagination' => ['pageSize' => 100],
		]);
    }
}
