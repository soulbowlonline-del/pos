<?php
namespace app\models;

use Yii;

use yii\db\ActiveRecord;

/** Ported from protected/models/Discount.php (Yii 1). */
class Discount extends ActiveRecord
{
    public const TIME_DEPENDENT = 1;
    public const STATUS_INACTIVE = 1;
    public const STATUS_ACTIVE = 0;
    public const DISCOUNT_ORDER = 0;
    public const DISCOUNT_ITEM = 1;
    public const TYPE_AMOUNT = 0;
    public const TYPE_PERCENTAGE = 1;

    public static function tableName()
    {
        return '{{%discount}}';
    }

    public static function getTypeOptions($id = null)
    {
        $list = ['amount', '%age'];
        if ($id === null) {
            return $list;
        }
        if (is_numeric($id)) {
            return isset($list[$id]) ? $list[$id] : null;
        }
        return $id;
    }

    public static function getDiscountTypeOptions($id = null)
    {
        $list = ['Order', 'Item'];
        if ($id === null) {
            return $list;
        }
        if (is_numeric($id)) {
            return isset($list[$id]) ? $list[$id] : null;
        }
        return $id;
    }

    /** Payload from Discount::toArray(), key for key. */
    public function toApiArray()
    {
        return [
            'id' => (string)$this->id,
            'title' => $this->title,
            'amount' => $this->amount,
            'applicable_amt' => $this->applicable_amt,
            'type_id' => $this->type_id === null ? null : (string)$this->type_id,
            'type_name' => self::getTypeOptions($this->type_id),
            'discount_type' => $this->discount_type === null ? null : (string)$this->discount_type,
            'discount_type_name' => self::getDiscountTypeOptions($this->discount_type),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
        ];
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'Discount' : 'Discounts';
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
		$list = ["Active","Inactive"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public function getItemOptions($vendor_id=null){
            $item_ids = [];
            $role = UserRole::findOne(['title'=>'Admin']);
            $user = Yii::$app->user->model;
            if($user->role_id != $role->id){
                $itemvendor_ids = [];
                $vendor = Vendor::findOne(['create_user_id'=>$user->id]);
                if($vendor){
                    $itemvendors = ItemVendor::findAll(['vendor_id'=>$vendor->id]);
                    if($itemvendors){

                        foreach($itemvendors as $itemvendor){
                            $itemvendor_ids[] = $itemvendor->item_detail_id;
                        }
                    }
                }
                $criteria->addInCondition('item_id', $itemvendor_ids);
            }
            $itemdetails = ItemDetail::find()
                ->all();
            if($itemdetails != null)
            {
                foreach($itemdetails as $itemdetail)
                {
                    $item = Item::findOne($itemdetail->item_id);
                    $item_ids[$itemdetail->id] = $item->title.'('.$itemdetail->bar_code.')';
                }
            }

            return $item_ids;
        }

    public function getCreateUser()
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }

    public function getItemDiscounts()
    {
        return $this->hasMany(ItemDiscount::class, ['discount_id' => 'id']);
    }

    public function getOrderHoldItems()
    {
        return $this->hasMany(OrderHoldItem::class, ['discount_id' => 'id']);
    }

    public function getOrderItems()
    {
        return $this->hasMany(OrderItem::class, ['discount_id' => 'id']);
    }

    public function getOrderRefundItems()
    {
        return $this->hasMany(OrderRefundItem::class, ['discount_id' => 'id']);
    }
}
