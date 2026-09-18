<?php
namespace app\models;

use app\components\Criteria;
use app\components\Gx;
use app\components\Ui;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\helpers\Html;

/**
 * Ported from protected/models/MrnDetail.php and its giix base class.
 */
class MrnDetail extends ActiveRecord
{
    public const STATUS_PENDING = 0;

    public const STATUS_HALF_DONE = 2;

    public const STATUS_DONE = 1;

    public const STATUS_ASSIGN = 3;

    public const STATUS_REJECT = 4;

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'MrnDetail' : 'MrnDetails';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'id';
    }

    /** GxActiveRecord::__toString(): the representing column, or the id. */
    public function __toString()
    {
        $value = $this->hasAttribute('id') ? $this->id : null;

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

    public static function getStatusOptions($id = null)
    {
		$list = ["Pending","Half Done","Done","Assigned","Rejected"];
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

    public function getMrnVendorOptions(){
            $list = [];
            $query = Mrn::find();
            $query->andWhere('status ='.Mrn::STATUS_UNAPPROVED);
            $mrss = $query->all();
            if($mrss){
                foreach($mrss as $mrs){
                    $vendor = Vendor::findOne($mrs->vendor_id);
                    if($vendor){
                        $list[$vendor->id] = $vendor->name;
                    }
                }
            }
            asort($list);
            return $list;
        }

    public function getVendorOptions() {
            $list = [];
            $item_vendors = ItemVendor::findAll( [
                    'item_detail_id' => $this->item_id
            ] );
            if ($item_vendors) {
                foreach ( $item_vendors as $item_vendor ) {
                    $vendor = Vendor::findOne( $item_vendor->vendor_id );
                    if ($vendor) {
                        $list [$vendor->id] = $vendor->name;
                    }
                }
            }
            return $list;
        }

    public function getMrnOptions($id = null) {
            $list = [];
            $user = Yii::$app->user->model;
            if ($user) {
                $role_id = $user->role_id;
                $role = UserRole::findOne(['title'=>'Vendor']);

                if ($id != null) {
                    if ($role_id == $role->id) {
                        $query = Mrn::find();
                        $query->andWhere('vendor_id ='.$id);
                        $query->andWhere('status ='.Mrn::STATUS_UNAPPROVED);
                        $mrslist = $query->all();

                    } else {
                        $query = Mrn::find();
                        $query->andWhere('status ='.Mrn::STATUS_UNAPPROVED);
                        $mrslist = $query->all();
                    }
                    if ($mrslist) {
                        foreach ( $mrslist as $mrs ) {
                            $list [$mrs->id] = $mrs->id;
                        }
                    }
                }
            }
            return $list;
        }

    public function getAllMrnOptions($id = null) {
            $list = [];
            $user = Yii::$app->user->model;
            if ($user) {
                $role_id = $user->role_id;
                $role = UserRole::findOne(['title'=>'Vendor']);

                if ($id != null) {
                    if ($role_id == $role->id) {
                        $query = Mrn::find();
                        $query->andWhere('vendor_id ='.$id);
                        $query->andWhere('status ='.Mrn::STATUS_UNAPPROVED);
                        $mrslist = $query->all();
                    }else {
                        $query = Mrn::find();
                        $query->andWhere('status ='.Mrn::STATUS_UNAPPROVED);
                        $mrslist = $query->all();
                    }
                if ($mrslist) {
                    foreach ( $mrslist as $mrs ) {
                        $list [] = $mrs->id;
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

    public function getItemDetail()
    {
        return $this->hasOne(ItemDetail::class, ['id' => 'item_detail_id']);
    }

    public function getTax()
    {
        return $this->hasOne(Tax::class, ['id' => 'tax_id']);
    }

    public function getItem()
    {
        return $this->hasOne(Item::class, ['id' => 'item_id']);
    }

    public function getMrn()
    {
        return $this->hasOne(Mrn::class, ['id' => 'mrn_id']);
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
            'approved_qty' => 'Approved Qty',
            'bal_qty' => 'Bal Qty',
            'status' => 'Status',
            'type_id' => 'Type',
            'remarks' => 'Remarks',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'item_id' => 'Item',
            'create_user_id' => 'User',
            'updated_by' => 'User',
            'item_detail_id' => 'Bar Code',
            'mrn_id' => 'Mrn',
            'outlet_id' => 'Outlet',
            'createUser' => 'User',
            'itemDetail' => 'Bar Code',
            'vendor_id' => 'Vendor',
            'mrn' => 'Mrn',
            'outlet' => 'Outlet',
            'updatedBy' => 'User',
        ];
    }

    public function getGstTrue($mrnid){
            $gst = true;
            if($mrnid){
                $mrs = Mrn::findOne(['id'=>$mrnid]);
                if($mrs){
                    $outlet = Outlet::findOne($mrs->outlet_id);
                    if($outlet){
                        $vendor = Vendor::findOne($mrs->vendor_id);
                        if($vendor->state_id != $outlet->state_id){
                            $gst = false;
                        }
                    }
                }
            }
            return $gst;
        }

    public function getCssClass()
        {
            // Was a bare `$cssClass;` - a statement that reads an undefined
            // variable and discards it, which is a PHP 8 warning and therefore a
            // 500 from Yii 1's error handler on every call. Initialised instead.
            $cssClass = '';
            $purchase_amount = $this->getPurchaseAmount();
            $sale_amount = $this->getSaleAmount();
            $purchase_qty = $this->getPurchaseQty();
            $per_purchase_qty = (($this->getPurchaseQty()) - (10/100) * ($this->getPurchaseQty()));
            $sale_qty = $this->getSaleQty();
            if($purchase_amount > $sale_amount){
                $cssClass='mrsred';
            }else if($sale_qty > $per_purchase_qty){
                $cssClass='mrsgreen';
            }else if($this->margin < 10){
                $cssClass='mrsorange';
            }else{
                $cssClass='';
            }
            Yii::warning( var_export( $cssClass ), '$cssClass');

            return $cssClass;
        }
}
