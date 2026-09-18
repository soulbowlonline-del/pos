<?php
namespace app\models;

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
            $mrss = PurchaseBill::find()
                ->andWhere('status ='.PurchaseBill::STATUS_UNAPPROVED)
                ->all();
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
}
