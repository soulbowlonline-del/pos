<?php
namespace app\components;

use yii\db\ActiveQuery;

/**
 * CDbCriteria::compare(), which every search() in the application is built on.
 *
 * The behaviour worth preserving is that an empty value adds no condition at
 * all: a grid filter left blank must not become `WHERE title = ''`. Yii 1
 * treats '' and null as "not set" but keeps 0 and '0', which is why the test
 * below is on the string and not on truthiness.
 *
 * Two further behaviours of CDbCriteria::compare() are not decoration, and
 * leaving them out made every grid in the application answer the wrong rows:
 *
 *   * A value may carry a leading comparison operator - `<`, `<=`, `>`, `>=`,
 *     `<>` or `=`. Yii 1 strips it and builds that comparison. Treating the
 *     whole string as a value instead means MySQL casts `>1373695` to 0 on a
 *     numeric column, so `id = >1373695` matches nothing, and `bal_qty = <0`
 *     matches every row whose bal_qty is 0 - the filter appears to be ignored
 *     and the grid shows everything. Reported against mrsDetail/admin.
 *   * An array value is an IN condition, not a reason to skip the filter.
 *     Dropping it silently is the same failure: no condition, all rows.
 *
 * The operator is stripped *before* the empty test, as Yii 1 does, so a value
 * of just `>` adds no condition rather than comparing against ''.
 */
class Criteria
{
    /**
     * @param ActiveQuery $query   the query being built
     * @param string      $column  column name
     * @param mixed       $value   the filter value, possibly empty
     * @param bool        $partial true for a LIKE %value% match, as Yii 1's
     *                             third argument
     */
    public static function compare($query, $column, $value, $partial = false)
    {
        // Yii 1 pastes the column into SQL - `mrs_id =:ycp0` - so whitespace
        // around it is just whitespace. Yii 2 quotes it, and `mrs_id ` with the
        // trailing space is a column no table has. Five call sites carry that
        // typo across from the Yii 1 tree, four of them "mrs_id ", and one of
        // those sits in the purchase-bill save: the count threw inside the
        // transaction, the catch swallowed it, and the Save button did nothing.
        //
        // Only the ends are trimmed, which is all Yii 1 effectively ignored.
        // BaseUser's 'last_a ction_time' has the space in the middle and is a
        // real typo for last_action_time; it is broken on Yii 1 too and is left
        // that way.
        $column = is_string($column) ? trim($column) : $column;

        // CDbCriteria::compare() turns an array into addInCondition(), and
        // only an empty array into no condition at all.
        if (is_array($value)) {
            return $value === [] ? $query : $query->andWhere(['in', $column, $value]);
        }

        // Yii 1 casts first, so null becomes '' and is dropped by the test
        // further down rather than becoming `IS NULL`.
        $value = (string) $value;

        if (preg_match('/^(?:\s*(<>|<=|>=|<|>|=))?(.*)$/', $value, $matches)) {
            $value = $matches[2];
            $op = $matches[1];
        } else {
            $op = '';
        }

        if ($value === '') {
            return $query;
        }

        if ($partial) {
            // Only a bare value and `<>` become LIKE / NOT LIKE. Any other
            // operator falls through to the comparison below, which is what
            // Yii 1 does - `>abc` on a partial-match column is `col > 'abc'`.
            if ($op === '') {
                return $query->andWhere(['like', $column, $value]);
            }
            if ($op === '<>') {
                return $query->andWhere(['not like', $column, $value]);
            }
        } elseif ($op === '') {
            $op = '=';
        }

        return $query->andWhere([$op, $column, $value]);
    }
}
