<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/Vendor.php (Yii 1). */
class Vendor extends ActiveRecord
{
    public const IS_CASH = 1;
    public const ADVANCE_PAYMENT = 1;
    public const STATUS_INACTIVE = 1;
    public const STATUS_ACTIVE = 0;
    public static function tableName()
    {
        return '{{%vendor}}';
    }

    /** A vendor may hang off a parent whose name is used in reporting. */
    public function getParentvendor()
    {
        return $this->hasOne(Vendor::class, ['id' => 'parent_id']);
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'Vendor' : 'Vendors';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'name';
    }

    /** GxActiveRecord::__toString(): the representing column, or the id. */
    public function __toString()
    {
        $value = $this->hasAttribute('name') ? $this->name : null;

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
        return ['id' => SORT_DESC];
    }
}
