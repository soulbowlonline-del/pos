<?php
namespace app\models;

use app\components\Criteria;

use app\components\Ui;

use yii\data\ActiveDataProvider;

use Yii;

use yii\db\ActiveRecord;

/** Ported from protected/models/Tax.php (Yii 1). */
class Tax extends ActiveRecord
{
    // Yii 1 hands out column values as strings; the option helpers
    // compare them loosely and answer wrongly for an integer 0.
    use LegacyColumnTypes;

    public const STATUS_INACTIVE = 1;
    public const STATUS_ACTIVE = 0;
    public const TYPE_GST = 0;
    public const TYPE_IGST = 1;

    public static function tableName()
    {
        return '{{%tax}}';
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'Tax' : 'Taxs';
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

    public static function getTypeOptions($id = null)
    {
		$list = [
				"Gst",
				"IGST",
				
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
				"Active",
				"Inactive" 
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
            'title' => 'Title',
            'tax_val1' => 'CGST (%age)',
            'tax_val2' => 'SGST (%age)',
            'tax_val3' => 'CESS (%age)',
            'tax_val4' => 'IGST (%age)',
            'hrn_code' => 'Hsn Code',
            'type_id' => 'Type',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'updatedBy' => 'Updated By',
            'createUser' => 'Created By',
            'itemDetails' => 'ItemDetails',
            'itemStocks' => 'ItemStocks',
            'itemTaxes' => 'ItemTaxes',
        ];
    }

    public static function getHsnCodeList(){
            $list = [];
            $taxes = Tax::findAll(['status'=>Tax::STATUS_ACTIVE]);
            if($taxes){
                foreach($taxes as $tax){
                    $list[$tax->id] = $tax->hrn_code;
                }
            }
            return $list;
        }

    public function getColumns($selectcolumns = []) {
            if (! empty ( $selectcolumns )) {
                $selected = $selectcolumns;
            } else {
                $selected = [
                        'title' ,
                        'hrn_code' ,
                        'tax_val1' ,
                        'tax_val2' ,
                        'tax_val3' ,

                ];
            }

            if ($selected) {
                foreach ( $selected as $select ) {

                        $columns [] = $select;

                }
            }

            return $columns;
        }

    public function getPBillAmount($poid,$id){
            $amount = '0.00';
            $purchaseBillDetails = PurchaseBillDetail::findAll(['purchase_bill_id'=>$poid,
                    'tax_id'=>$id
            ]);
            if($purchaseBillDetails){
                foreach($purchaseBillDetails as $purchaseBillDetail){
                    if($purchaseBillDetail->is_free == 0){
                    $amount = $amount + (($purchaseBillDetail->approved_qty * $purchaseBillDetail->price)-($purchaseBillDetail->discount_amt+$purchaseBillDetail->discount_amt1));
                    }
                }

            }
            return $amount;

        }

    public function getPBillCgstAmount($poid,$id,$col){
            $amount = '0.00';
            $purchaseBillDetails = PurchaseBillDetail::findAll(['purchase_bill_id'=>$poid,
                    'tax_id'=>$id
            ]);
            if($purchaseBillDetails){
                foreach($purchaseBillDetails as $purchaseBillDetail){
                    if($purchaseBillDetail->getGSTTrue($poid) == true && $purchaseBillDetail->is_free == 0){
                        $amount = $amount + $purchaseBillDetail->$col;
                    }else{
                         $amount = $amount + $purchaseBillDetail->$col;
                    }
                }

            }
            return $amount;

        }

    public function getChangePBillCgstAmount($poid,$id,$col,$detail_id,$tax_id,$purchase_bill_ids){
            //echo '<pre>';
    //print_R($purchase_bill_ids);exit;
            //    $array = json_decode(json_encode($purchase_bill_ids), true);
            $amount = 0;
            if(!empty($purchase_bill_ids)){
                foreach($purchase_bill_ids as $purchase_bill){
                    $array = json_decode(($purchase_bill), true);
                    if(isset($array['cgst']) && ($col == 'cgst_amt')&&($array['tax_id'] == $id)&& ($array['is_free'] == 0)){
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

    public function getChangePBillAmount($poid,$id,$col,$detail_id,$tax_id,$purchase_bill_ids){
        //    $array = json_decode(json_encode($purchase_bill_ids), true);
            $amount = 0;
            if(!empty($purchase_bill_ids)){
                foreach($purchase_bill_ids as $purchase_bill){
                    $array = json_decode(($purchase_bill), true);
                    if($array['tax_id'] == $id){
                        $amount = $amount + (($array['qty'] * $array['price'])-($array['discount']+$array['discount1']));
                    }
                }
            }
            return $amount;


        }

    public function getPB2bBillCgstAmount($poid, $id, $col)
        {
            $amount = '0.00';
            $purchaseBillDetails = B2bPurchaseBillDetail::findAll([
                'purchase_bill_id' => $poid,
                'tax_id' => $id
            ]);
            if ($purchaseBillDetails) {
                foreach ($purchaseBillDetails as $purchaseBillDetail) {
                    if ($purchaseBillDetail->getGSTTrue($poid) == true && $purchaseBillDetail->is_free == 0) {
                        $amount = $amount + $purchaseBillDetail->$col;

               }else{
                         $amount = $amount + $purchaseBillDetail->$col;
                    }
            }
            }
            return $amount;
        }

    public function getPB2bBillAmount($poid, $id)
        {
            $amount = '0.00';
            $purchaseBillDetails = B2bPurchaseBillDetail::findAll([
                'purchase_bill_id' => $poid,
                'tax_id' => $id
            ]);
            if ($purchaseBillDetails) {
                foreach ($purchaseBillDetails as $purchaseBillDetail) {
                    if ($purchaseBillDetail->is_free == 0) {
                        $amount = $amount + (($purchaseBillDetail->approved_qty * $purchaseBillDetail->price) - ($purchaseBillDetail->discount_amt + $purchaseBillDetail->discount_amt1));
            // $amount = $amount + ((($detail->amount)) - (($detail->getTaxPercentage("cgst_amt")) + ($detail->getTaxPercentage("sgst_amt")) + ($detail->getTaxPercentage("cess_amt")) + ($detail->getTaxPercentage("igst_amt"))));
                    }
                }
            }
            return $amount;
        }

    public function getItemDetails()
    {
        return $this->hasMany(ItemDetail::class, ['tax_id' => 'id']);
    }

    public function getItemStocks()
    {
        return $this->hasMany(ItemStock::class, ['tax_id' => 'id']);
    }

    public function getItemTaxes()
    {
        return $this->hasMany(ItemTax::class, ['tax_id' => 'id']);
    }

    public function getCreateUser()
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
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
            [['title', 'tax_val1', 'create_user_id'], 'required'],
            [['type_id', 'status', 'create_user_id', 'updated_by'], 'integer'],
            [['tax_val1', 'tax_val2', 'tax_val3', 'tax_val4'], 'number'],
            [['title'], 'string', 'max' => 255],
            [['title'], 'unique'],
            [['create_time', 'update_time', 'hrn_code', 'tax_val3', 'tax_val4', 'columns'], 'safe'],
            [['title', 'tax_val1', 'tax_val2', 'type_id', 'status', 'create_time', 'update_time', 'updated_by'], 'default', 'value' => null],
            [['id', 'title', 'tax_val1', 'tax_val2', 'type_id', 'status', 'create_time', 'update_time', 'create_user_id', 'updated_by'], 'safe', 'on' => 'search'],
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
            // The order Yii 1's search() gives its provider, which is not
            // always the model's defaultScope(): the grid can name its own.
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE],
        ]);

        $this->load($params, $this->formName());

        foreach (['id', 'tax_val1', 'tax_val2', 'tax_val3', 'tax_val4', 'hrn_code', 'type_id', 'status', 'create_user_id', 'updated_by'] as $attr) {
            Criteria::compare($query, $attr, $this->$attr);
        }
        foreach (['title', 'create_time', 'update_time'] as $attr) {
            Criteria::compare($query, $attr, $this->$attr, true);
        }

        return $provider;
    }

    public function setAllValues($rows) {

            $output = 0;
            $count = count($rows);


            if ($count > 1) {

                $o = explode(',', $rows[0]);
                $arrays = array_flip($o);
                $set = true;
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    for ($i = 1; $i < $count; $i++) {
                        $tax_values = explode(',', $rows[$i]);


                        $tax = new Tax();

                        if (isset($arrays['Title']) || isset($arrays['﻿"Title"']) || isset($arrays['���"Title"'])) {

                            if (isset($arrays['Title'])) {
                                $tax->title = $tax_values[$arrays['Title']];

                            } else if(isset($arrays['﻿"Title"'])) {
                                $tax->title = $tax_values[$arrays['﻿"Title"']];
                            }else{
                                $tax->title = $tax_values[$arrays['���"Title"']];
                            }
                        }



                        if (isset($arrays['Total Tax(%age)'])) {
                            $tax->hrn_code =$tax_values[$arrays['Hrn Code']];
                        }
                        if (isset($arrays['CGST (%age)'])) {
                            $tax->tax_val1 =$tax_values[$arrays['CGST (%age)']];
                        }
                        if (isset($arrays['SGST (%age)'])) {
                            $tax->tax_val2 =$tax_values[$arrays['SGST (%age)']];
                        }
                        if (isset($arrays['CESS (%age)'])) {
                            $tax->tax_val3 =$tax_values[$arrays['CESS (%age)']];
                        }

                        if ($tax->save()) {


                        } else {
                            print_R($tax->getErrors());
                            exit;
                            $set = false;
                        }
                    }
                    if ($set == true) {
                        $transaction->commit();
                        return 1;
                    }
                } catch (Exception $e) {
                    $transaction->rollback();
                }
            }
            return $output;
        }
}
