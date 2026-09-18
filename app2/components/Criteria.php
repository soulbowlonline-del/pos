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
        if ($value === null || $value === '' || is_array($value)) {
            return $query;
        }

        return $partial
            ? $query->andWhere(['like', $column, $value])
            : $query->andWhere([$column => $value]);
    }
}
