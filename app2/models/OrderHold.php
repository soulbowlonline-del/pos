<?php
namespace app\models;

use Yii;

use yii\db\ActiveRecord;

/** Ported from protected/models/OrderHold.php (Yii 1). */
class OrderHold extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%order_hold}}';
    }

    public function getModePayment()
    {
        return $this->hasOne(PaymentMode::class, ['id' => 'mode_of_payment']);
    }

    public function getModeDelivery()
    {
        return $this->hasOne(PaymentMode::class, ['id' => 'mode_of_delivery']);
    }

    public function getOrderItems()
    {
        // Line items had no explicit order, so the two frameworks listed
        // them differently within the same order. Ordered by id on both.
        // Yii 1 returns held-order lines in descending id order. Its relation
        // has no ORDER BY and the 'order' option on a Yii 1 HAS_MANY did not
        // take effect here, so that sequence is the database's rather than
        // anything chosen; this matches it explicitly so the two agree. When
        // this read path is served only by Yii 2 the order can be revisited.
        return $this->hasMany(OrderHoldItem::class, ['order_hold_id' => 'id'])
            ->orderBy(['id' => SORT_DESC]);
    }

    /**
     * Payload from OrderHold::toArray().
     *
     * Note total_amt is the sale total plus the discount, i.e. the gross before
     * discount - the opposite sense to Order::toArray(), where total_amt is the
     * net. Reproduced as-is; the two payloads genuinely differ.
     */
    public function toApiArray()
    {
        $json = [];
        $json['id'] = (string)$this->id;
        $json['mode_of_payment'] = isset($this->modePayment) ? $this->modePayment->title : '';
        $json['mode_of_delivery'] = isset($this->modeDelivery) ? $this->modeDelivery->title : '';
        $json['qty'] = $this->qty === null ? null : (string)$this->qty;
        $json['discount_amt'] = $this->discount_amt;
        $json['total_sale'] = $this->total_amt;
        $json['total_amt'] = $this->total_amt + $this->discount_amt;
        $json['paid_amt'] = $this->paid_amt;
        $json['status'] = $this->status === null ? null : (string)$this->status;
        $json['type_id'] = $this->type_id === null ? null : (string)$this->type_id;
        $json['city_id'] = $this->city_id === null ? null : (string)$this->city_id;
        $json['state_id'] = $this->state_id === null ? null : (string)$this->state_id;
        $json['country_id'] = $this->country_id === null ? null : (string)$this->country_id;
        $json['address'] = $this->address;
        $json['note'] = $this->note;
        $json['create_time'] = $this->create_time;
        $json['customer_id'] = $this->customer_id === null ? null : (string)$this->customer_id;

        $items = [];
        foreach ($this->orderItems as $item) {
            $items[] = $item->toApiArray();
        }
        $json['order_items'] = $items;

        return $json;
    }

    /** Payload from OrderHold::toArray1(). */
    public function toApiArray1()
    {
        return [
            'id' => (string)$this->id,
            'create_time' => $this->create_time,
        ];
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'OrderHold' : 'OrderHolds';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'bill_date';
    }

    /** GxActiveRecord::__toString(): the representing column, or the id. */
    public function __toString()
    {
        $value = $this->hasAttribute('bill_date') ? $this->bill_date : null;

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

    public static function getTypeOptions($id = null)
    {
		$list = ["TYPE1","TYPE2","TYPE3"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public static function getStatusOptions($id = null)
    {
		$list = ["Draft","Published","Archive"];
		if ($id === null || $id === '' )	return $list;
		if ( is_numeric( $id )) return $list [ $id ];
		return $id;
    }

    public function getCity()
    {
        return $this->hasOne(City::class, ['id' => 'city_id']);
    }

    public function getCountry()
    {
        return $this->hasOne(Country::class, ['id' => 'country_id']);
    }

    public function getCustomer()
    {
        return $this->hasOne(Customer::class, ['id' => 'customer_id']);
    }

    public function getState()
    {
        return $this->hasOne(State::class, ['id' => 'state_id']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }

    public function getOrderRefunds()
    {
        return $this->hasMany(OrderRefund::class, ['order_id' => 'id']);
    }
}
