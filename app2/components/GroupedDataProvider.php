<?php
namespace app\components;

use yii\data\ActiveDataProvider;
use yii\db\Expression;

/**
 * ActiveDataProvider for the report queries that GROUP BY.
 *
 * Ten search() methods across the report models build a grouped query and used
 * to pass their own total:
 *
 *     'totalCount' => (clone $query)->select(new Expression('1'))->count(),
 *
 * The reason given was that a grouped query's count(*) counts one group and
 * the pager would read 1. That is not so - Query::queryScalar() wraps the
 * whole query in `SELECT COUNT(*) FROM (...) c` as soon as groupBy is set, and
 * the default answers correctly. But the line was doing something else that is
 * real, and only shows on some of these queries: replacing the select list
 * with `1`.
 *
 * Yii 2's own prepareTotalCount() keeps the select list and wraps it, and
 * several of these queries name a column twice across their joins -
 * b2bTaxwisesearch and the b2b item-wise report both select cgst_amt from two
 * tables. A derived table may not have two columns of one name, so the count
 * fails with "Duplicate column name 'cgst_amt'" and the page is a 500.
 * `SELECT 1` has no such problem, and is cheaper besides.
 *
 * What was wrong with passing it was only that it is eager. Several of these
 * views call search() twice - grouptax.php calls it once at the top and throws
 * the result away - so the count ran twice where the pager needs it once, and
 * Yii 1 never paid for the discarded call because a CActiveDataProvider builds
 * nothing until it is asked. Here the count is built the same way and taken
 * only when something reads totalCount.
 */
class GroupedDataProvider extends ActiveDataProvider
{
    protected function prepareTotalCount()
    {
        $query = clone $this->query;

        return (int) $query
            ->select(new Expression('1'))
            ->limit(-1)
            ->offset(-1)
            ->orderBy([])
            ->count('*', $this->db);
    }
}
