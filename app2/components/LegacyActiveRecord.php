<?php
namespace app\components;

use yii\db\ActiveRecord;

/**
 * ActiveRecord that inserts the way Yii 1's does.
 *
 * Yii 1 leaves a NOT NULL column out of the INSERT when its value is null, so
 * the database fills in the column's own default. CDbCommandBuilder:
 *
 *     if(($column=$table->getColumn($name))!==null
 *         && ($value!==null || $column->allowNull))
 *
 * Yii 2 has no such rule: every dirty attribute goes into the INSERT, nulls
 * and all, and MySQL answers "Column 'x' cannot be null".
 *
 * That matters here because 67 of the 78 models carry the rule Gii generates
 *
 *     [[...], 'default', 'value' => null]
 *
 * which sets an attribute to null precisely when the form left it blank. Add
 * Item on itemReturnItem/admin posts an empty Disc1 %, the rule turns it into
 * null, tbl_item_return_item.discount1 is NOT NULL DEFAULT 0.00, and the
 * insert that Yii 1 completes - the column omitted, the default applied - the
 * port refused. The same pairing exists in most of the other 66.
 *
 * Validation still runs, and still runs first; only the column list narrows,
 * and only for columns the database can fill itself.
 */
class LegacyActiveRecord extends ActiveRecord
{
    public function insert($runValidation = true, $attributeNames = null)
    {
        if ($runValidation && !$this->validate($attributeNames)) {
            return false;
        }

        $names = $attributeNames === null
            ? array_keys($this->getDirtyAttributes())
            : $attributeNames;

        $schema = static::getTableSchema();
        $kept = [];
        foreach ($names as $name) {
            $column = $schema->getColumn($name);
            if ($column !== null && !$column->allowNull && self::wouldInsertNull($column, $this->getAttribute($name))) {
                continue;
            }
            $kept[] = $name;
        }

        // Validation has already run; parent::insert() must not repeat it,
        // because a second pass would re-apply the default rules it is the
        // point of this class to work around.
        return parent::insert(false, $kept);
    }

    /**
     * Would this value reach the database as null?
     *
     * Two ways it can, and Yii 1 survives both. The attribute is already null,
     * which Yii 1 omits outright. Or it is the empty string on a column that is
     * not textual, which yii\db\ColumnSchema::typecast() turns into null on the
     * way out - Yii 1 sends the empty string instead and MySQL, in the
     * sql_mode="" this database runs under, stores 0.00.
     *
     * Either way the column is left out and the database fills it, which lands
     * on the same value Yii 1 records: a blank Disc % is stored as 0.00 by
     * both stacks, one by coercion and one by default.
     */
    private static function wouldInsertNull($column, $value)
    {
        if ($value === null) {
            return true;
        }

        return $value === '' && !in_array($column->type, ['string', 'text', 'char', 'binary'], true);
    }
}
