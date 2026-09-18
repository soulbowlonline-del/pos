<?php
namespace app\models;

use yii\db\ActiveRecord;

/** Ported from protected/models/State.php (Yii 1). */
class State extends ActiveRecord
{
    public const STATUS_INACTIVE = 1;
    public const STATUS_ACTIVE = 0;
    public static function tableName()
    {
        return '{{%state}}';
    }

    /**
     * Payload from State::toArray(). Named toApiArray() because yii\base\Model
     * declares toArray() as part of Arrayable.
     *
     * id is cast to string: Yii 1 served every column as a string and Yii 2's
     * ActiveRecord type-casts from the schema.
     */
    public function toApiArray()
    {
        return [
            'id' => (string)$this->id,
            'title' => isset($this->title) ? $this->title : '',
        ];
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'State' : 'States';
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
}
