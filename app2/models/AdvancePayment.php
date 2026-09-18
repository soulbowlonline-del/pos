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
 * Ported from protected/models/AdvancePayment.php and its giix base class.
 */
class AdvancePayment extends ActiveRecord
{
    // Yii 1 hands out column values as strings; the option helpers
    // compare them loosely and answer wrongly for an integer 0.
    use LegacyColumnTypes;

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'AdvancePayment' : 'AdvancePayments';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'payment_date';
    }

    /** GxActiveRecord::__toString(): the representing column, or the id. */
    public function __toString()
    {
        $value = $this->hasAttribute('payment_date') ? $this->payment_date : null;

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

    public static function tableName()
    {
        return '{{%advance_payment}}';
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
				"TYPE1",
				"TYPE2",
				"TYPE3" 
		];
		if ($id == null)
			return $list;
		if (is_numeric ( $id ))
			return $list [$id];
		return $id;
    }

    public static function getStatusOptions($id = null)
    {
		$list = [
				"Draft",
				"Published",
				"Archive" 
		];
		if ($id == null)
			return $list;
		if (is_numeric ( $id ))
			return $list [$id];
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
            [['payment_date', 'payment', 'vendor_id', 'create_user_id'], 'required'],
            [['type_id', 'status', 'vendor_id', 'create_user_id', 'updated_by'], 'integer'],
            [['payment'], 'number'],
            [['create_time', 'update_time', 'balance_amt'], 'safe'],
            [['type_id', 'status', 'create_time', 'update_time', 'updated_by'], 'default', 'value' => null],
            [['id', 'payment_date', 'payment', 'type_id', 'status', 'create_time', 'update_time', 'vendor_id', 'create_user_id', 'updated_by'], 'safe', 'on' => 'search'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'payment_date' => 'Payment Date',
            'payment' => 'Payment',
            'type_id' => 'Type',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'is_advance_payment' => 'Advance Payment',
            'update_time' => 'Update Time',
            'vendor_id' => 'Vendor',
            'create_user_id' => 'User',
            'updated_by' => 'User',
            'createUser' => 'User',
            'updatedBy' => 'User',
            'vendor' => 'Vendor',
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
            'sort' => ['defaultOrder' => self::defaultOrder() ?: []],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE],
        ]);

        $this->load($params, $this->formName());

        foreach (['id', 'payment', 'type_id', 'status', 'vendor_id', 'create_user_id', 'updated_by'] as $attr) {
            Criteria::compare($query, $attr, $this->$attr);
        }
        foreach (['payment_date', 'create_time', 'update_time'] as $attr) {
            Criteria::compare($query, $attr, $this->$attr, true);
        }

        return $provider;
    }

    public function getAdvancePayVendorOptions(){
            $list = [];
            $vendors = Vendor::find()
                ->andWhere('status ='.Vendor::STATUS_ACTIVE)
                ->andWhere('is_advance_payment ='.Vendor::ADVANCE_PAYMENT)
                ->orderBy(['name' => SORT_ASC])
                ->all();
            if($vendors){
                foreach($vendors as $vendor){
                    $list[$vendor->id] = $vendor->name;
                }
            }
            return $list;
        }

    public function getCreateUser()
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }

    public function getVendor()
    {
        return $this->hasOne(Vendor::class, ['id' => 'vendor_id']);
    }

    public function getAdvancelogs()
    {
        return $this->hasMany(AdvanceLogs::class, ['advance_payment_id' => 'id']);
    }
}
