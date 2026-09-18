<?php
namespace app\models;

use app\components\Ui;

use yii\data\ActiveDataProvider;

use Yii;
use Throwable;
use yii\db\ActiveRecord;

/** Ported from protected/models/CustomerLoyalty.php (Yii 1). */
class CustomerLoyalty extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%customer_loyalty}}';
    }

    /**
     * Returns the customer's loyalty row, creating a zeroed one if absent.
     * Mirrors getOrCreateCustomerLoyalty() on the Yii 1 side, including the
     * side effect of inserting a row on first read.
     */
    public static function getOrCreate($customerId)
    {
        $loyalty = static::findOne(['customer_id' => $customerId]);
        if ($loyalty === null) {
            $loyalty = new static();
            $loyalty->customer_id = $customerId;
            $loyalty->total_points = 0;
            $loyalty->lifetime_earned = 0;
            $loyalty->lifetime_redeemed = 0;
            $loyalty->save(false);
        }
        return $loyalty;
    }

    /** Payload from CustomerLoyalty::asArray() on the Yii 1 side. */
    public function asArray()
    {
        return [
            'customer_id' => $this->customer_id === null ? null : (string)$this->customer_id,
            'total_points' => $this->total_points,
            'lifetime_earned' => $this->lifetime_earned,
            'lifetime_redeemed' => $this->lifetime_redeemed,
        ];
    }

    /**
     * Credits points and records an EARN transaction.
     *
     * The Yii 1 original has its beginTransaction/commit/rollback lines
     * commented out, so the two writes are not atomic there. That is preserved
     * rather than silently changed - see the note on the write paths in
     * LoyaltyService.
     */
    public function addPoints($points, $orderId = null, $description = 'Points earned')
    {
        try {
            $this->total_points += $points;
            $this->lifetime_earned += $points;
            $this->save(false);

            $trans = new LoyaltyTransaction();
            $trans->customer_id = $this->customer_id;
            $trans->order_id = $orderId;
            $trans->transaction_type = LoyaltyTransaction::TYPE_EARN;
            $trans->points = $points;
            $trans->description = $description;
            $trans->save(false);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Debits points and records a REDEEM transaction, atomically.
     */
    public function redeemPoints($points, $orderId = null, $description = 'Points redeemed')
    {
        if ($this->total_points < $points) {
            return false;               // insufficient points
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $this->total_points -= $points;
            $this->lifetime_redeemed += $points;
            $this->save(false);

            $trans = new LoyaltyTransaction();
            $trans->customer_id = $this->customer_id;
            $trans->order_id = $orderId;
            $trans->transaction_type = LoyaltyTransaction::TYPE_REDEEM;
            $trans->points = $points;
            $trans->description = $description;
            $trans->save(false);

            $transaction->commit();
            return true;
        } catch (Throwable $e) {
            $transaction->rollBack();
            return false;
        }
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'CustomerLoyalty' : 'CustomerLoyaltys';
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

    public function attributeLabels()
    {
        return [
        ];
    }

    public function getCustomer()
    {
        return $this->hasOne(Customer::class, ['id' => 'customer_id']);
    }

    public function getTransactions()
    {
        return $this->hasMany(LoyaltyTransaction::class, ['customer_id' => 'id']);
    }
}
