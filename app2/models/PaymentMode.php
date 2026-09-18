<?php
namespace app\models;

use app\components\Criteria;
use app\components\Ui;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;

/**
 * Ported from protected/models/PaymentMode.php and its Gii base class.
 *
 * The API port only needed tableName(); the web UI needs the rest - the
 * validation rules the forms enforce, the option lists the dropdowns and grid
 * filters use, and search(), which backs the admin grid.
 */
class PaymentMode extends ActiveRecord
{
    // Yii 1 hands out column values as strings; the option helpers below
    // compare them loosely and give the wrong answer for an integer 0.
    use LegacyColumnTypes;

    public static function tableName()
    {
        return '{{%payment_mode}}';
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'Payment Mode' : 'Payment Modes';
    }

    /**
     * The column that stands for the whole row in a link or a breadcrumb -
     * Yii 1's representingColumn(), used by __toString().
     */
    public static function representingColumn()
    {
        return 'title';
    }

    /**
     * GxActiveRecord::__toString(): the representing column's value, falling
     * back to the primary key when it is empty.
     */
    public function __toString()
    {
        $column = static::representingColumn();
        $value = $this->hasAttribute($column) ? $this->$column : null;

        return (string) ($value === null || $value === '' ? $this->id : $value);
    }

    /**
     * The ordering Yii 1's defaultScope() put on every query for this model.
     * BasePaymentMode does not override it, so it is the inherited id DESC.
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

    public static function getTypeOptions($id = null)
    {
        $list = ['Payment Mode', 'Mode Of Delivery'];
        // Yii 1's test is loose, and with string column values it is also
        // correct: '0' == null is false, so status 0 resolves to its label.
        if ($id == null) {
            return $list;
        }
        return is_numeric($id) ? ($list[$id] ?? $id) : $id;
    }

    public static function getStatusOptions($id = null)
    {
        $list = ['Draft', 'Published', 'Archive'];
        if ($id == null) {
            return $list;
        }
        return is_numeric($id) ? ($list[$id] ?? $id) : $id;
    }

    /**
     * BasePaymentMode's rules. The `default` rule with setOnEmpty is the one
     * that matters beyond validation: it rewrites empty attributes to NULL on
     * every save, so a blank field becomes NULL rather than ''.
     */
    public function rules()
    {
        return [
            [['title', 'create_user_id'], 'required'],
            [['type_id', 'status', 'create_user_id', 'updated_by'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['create_time', 'update_time'], 'safe'],
            [['type_id', 'status', 'create_time', 'update_time', 'updated_by'],
             'default', 'value' => null],
            [['id', 'title', 'type_id', 'status', 'create_time', 'update_time',
              'create_user_id', 'updated_by'], 'safe', 'on' => 'search'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'type_id' => 'Type',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'create_user_id' => 'Create User',
            'updated_by' => 'Updated By',
        ];
    }

    /**
     * Backs the admin grid.
     *
     * The comparison rules are Yii 1's: title, create_time and update_time are
     * partial matches, everything else is exact, and an empty value drops the
     * condition rather than matching on emptiness. Yii 1's CActiveDataProvider
     * has no ORDER BY here, so the page order was the database's; ordered by id
     * explicitly, because MySQL 8 does not sort implicitly and a grid that
     * reshuffles between page 1 and page 2 loses rows.
     */
    public function search($params = [])
    {
        $query = self::find();
        $provider = new ActiveDataProvider([
            'query' => $query,
            // GxActiveRecord::defaultScope() puts `ORDER BY id DESC` on every
            // model that has an id, so this is the order Yii 1 lists rows in -
            // newest first - not the ascending order an unordered query happens
            // to produce.
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
            'pagination' => ['pageSize' => Ui::PAGE_SIZE],
        ]);

        // No validate() here. Yii 1's search() compares whatever is set and
        // never validates, and it matters: the `required` rule on title has no
        // `on` clause, so it applies in the search scenario too. Validating
        // would reject every filtered request with "Title cannot be blank" and
        // silently return the unfiltered list.
        $this->load($params, $this->formName());

        foreach (['id', 'type_id', 'status', 'create_user_id', 'updated_by'] as $attr) {
            Criteria::compare($query, $attr, $this->$attr);
        }
        foreach (['title', 'create_time', 'update_time'] as $attr) {
            Criteria::compare($query, $attr, $this->$attr, true);
        }

        return $provider;
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
     * GxActiveRecord::getRelationLabel(). The generated attributeLabels()
     * above already resolves a relation or foreign key to the related
     * model's label, so this is the attribute label.
     */
    public function getRelationLabel($name, $n = null)
    {
        return $this->getAttributeLabel($name);
    }
}
