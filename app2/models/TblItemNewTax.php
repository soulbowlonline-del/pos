<?php
namespace app\models;

use app\components\Criteria;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;

/**
 * Ported from protected/models/TblItemNewTax.php.
 *
 * It was not ported with the rest, and Item::getItemMainGSTNewActualTax() and
 * getItemMainGSTNewActualMRPOld() name it - so both would have fatalled on
 * "Class app\models\TblItemNewTax not found" the first time a page asked an
 * item for its HSN tax. Neither is on the paths the suites cover, which is
 * why it took a static check of every class name in the port to find it;
 * see tests/port/class-refs.py.
 *
 * The Yii 1 class carries the `Tbl` prefix in its own name, unlike every
 * other model here, and is kept that way: the two call sites spell it so.
 */
class TblItemNewTax extends ActiveRecord
{
    // Yii 1 hands out column values as strings.
    use LegacyColumnTypes;

    public static function tableName()
    {
        return '{{%item_new_tax}}';
    }

    /** Yii 1's label(): the model's name, singular or plural. */
    public static function label($n = 1)
    {
        return $n == 1 ? 'Item New Tax' : 'Item New Taxes';
    }

    /** The column that stands for the whole row in a link or a breadcrumb. */
    public static function representingColumn()
    {
        return 'title';
    }

    public function __toString()
    {
        return (string) $this->title;
    }

    public function rules()
    {
        return [
            [['title', 'hsn_code', 'tax'], 'required'],
            [['tax'], 'integer'],
            [['title', 'hsn_code'], 'string', 'max' => 255],
            [['id', 'title', 'hsn_code', 'tax'], 'safe', 'on' => 'search'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'hsn_code' => 'HSN Code',
            'tax' => 'Tax (%)',
        ];
    }

    public function search()
    {
        $query = static::find()->alias('t');

        Criteria::compare($query, 't.id', $this->id);
        Criteria::compare($query, 't.title', $this->title, true);
        Criteria::compare($query, 't.hsn_code', $this->hsn_code, true);
        Criteria::compare($query, 't.tax', $this->tax);

        return new ActiveDataProvider(['query' => $query]);
    }
}
