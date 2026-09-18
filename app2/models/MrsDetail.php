<?php
namespace app\models;

use Yii;

use yii\db\ActiveRecord;

/** Ported from protected/models/MrsDetail.php (Yii 1) - a requisition line. */
class MrsDetail extends ActiveRecord
{
    public const STATUS_REJECT = 4;
    public const STATUS_ASSIGN = 3;
    public const STATUS_DONE = 1;
    public const STATUS_HALF_DONE = 2;
    public const STATUS_PENDING = 0;
    public static function tableName()
    {
        return '{{%mrs_detail}}';
    }

    /**
     * Yii 1's getGstTrue(): false when the vendor and the outlet are in
     * different states, which is what decides IGST against CGST+SGST.
     *
     * The Yii 1 version reads $vendor->state_id without checking $vendor, so a
     * requisition pointing at a missing vendor is a fatal there. Reproduced.
     */
    public function getGstTrue($mrsId)
    {
        if (!$mrsId) {
            return true;
        }
        $mrs = Mrs::findOne(['id' => $mrsId]);
        if (!$mrs) {
            return true;
        }
        $outlet = Outlet::findOne($mrs->outlet_id);
        if (!$outlet) {
            return true;
        }
        $vendor = Vendor::findOne($mrs->vendor_id);
        return $vendor->state_id != $outlet->state_id ? false : true;
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'MrsDetail' : 'MrsDetails';
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

    public static function getStatusOptions($id = null)
    {
		$list = ["Pending","Half Done","Done","Assigned","Rejected"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public static function getAdjustTypeOptions($id = null)
    {
		$list = ["Add","Substract"];
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

    public function getMrsVendorOptions(){
            $list = [];
            $mrss = Mrs::find()
                ->andWhere('status ='.Mrs::STATUS_PENDING)
                ->andWhere('Date(create_time) >= DATE_SUB(CURDATE(), INTERVAL 50 DAY)')
                ->all();
            if($mrss){
                foreach($mrss as $mrs){
                    $count = count($mrs->mrsDetails);
                    $create_time = date('d-m-Y',strtotime($mrs->create_time));
                    $vendor = Vendor::findOne($mrs->vendor_id);
                    $amt = '0.00';
                    if($mrs->bill_amount != null){
                        $amt = $mrs->bill_amount;
                    }
                    if($vendor){

                        $list[$vendor->id] = $vendor->name.'('.$create_time.')'.'[ Count- '.$count.']';
                    }
                }
            }
            //Yii::log ( CVarDumper::dumpAsString ( $list ), CLogger::LEVEL_WARNING, '$list1' );
            asort($list);
            //Yii::log ( CVarDumper::dumpAsString ( $list ), CLogger::LEVEL_WARNING, '$list2' );
            return $list;
        }

    public function getVendorOptions(){
            $list = [];
            $item_vendors = ItemVendor::findAll(['item_detail_id'=>$this->item_id]);
            if($item_vendors){
                foreach($item_vendors as $item_vendor){
                    $vendor = Vendor::findOne($item_vendor->vendor_id);
                    if($vendor){
                        $list[$vendor->id] = $vendor->name;
                    }
                }
            }
            return $list;
        }

    public function getMrsOptions($id = null) {
            $list = [];
            $user = Yii::$app->user->model;
            //$user = User::findOne( $id );
            if ($user) {
                $role_id = $user->role_id;
                $role = UserRole::findOne(['title'=>'Vendor']);

                if ($id != null) {
                    if ($role_id == $role->id) {
                        $criteria = new CDbCriteria();
                        $criteria->addCondition('vendor_id ='.$id);
                        $criteria->addCondition('status !='.Mrs::STATUS_DONE);
                        $mrslist = Mrs::model ()->findAll($criteria);
                    } else {
                        $criteria = new CDbCriteria();

                        $criteria->addCondition('status !='.Mrs::STATUS_DONE);
                        $mrslist = Mrs::model ()->findAll ($criteria);
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

    public function getAllMrsOptions($id = null) {
            $list = [];
            $user = Yii::$app->user->model;
            if ($user) {
                $role_id = $user->role_id;
                $role = UserRole::findOne(['title'=>'Vendor']);

                if ($id != null) {
                    if ($role_id == $role->id) {
                        $criteria = new CDbCriteria();
                        $criteria->addCondition('vendor_id ='.$id);
                        $criteria->addCondition('status !='.Mrs::STATUS_DONE);
                        $mrslist = Mrs::model ()->findAll($criteria);
                    }else {
                        $criteria = new CDbCriteria();

                        $criteria->addCondition('status !='.Mrs::STATUS_DONE);
                        $mrslist = Mrs::model ()->findAll ($criteria);
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

    public function getItem()
    {
        return $this->hasOne(Item::class, ['id' => 'item_id']);
    }

    public function getItemDetail()
    {
        return $this->hasOne(ItemDetail::class, ['id' => 'item_detail_id']);
    }

    public function getMrs()
    {
        return $this->hasOne(Mrs::class, ['id' => 'mrs_id']);
    }

    public function getOutlet()
    {
        return $this->hasOne(Outlet::class, ['id' => 'outlet_id']);
    }

    public function getTax()
    {
        return $this->hasOne(Tax::class, ['id' => 'tax_id']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }
}
