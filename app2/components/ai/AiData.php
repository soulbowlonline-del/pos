<?php
namespace app\components\ai;

use Yii;
use yii\caching\FileCache;

/**
 * Read-only access to the database for the AI screens.
 *
 * Every query here is written in this code with bound parameters; nothing a
 * user or the model types ever becomes SQL. Each one is a SELECT, is capped at
 * twenty seconds on MySQL 8 by an optimiser hint (MariaDB ignores the hint;
 * it is a comment there), and its result can be cached for a few minutes so a
 * busy screen does not ask the till database the same question twice.
 */
class AiData
{
    public const MAX_MS = 20000;

    /** @var FileCache|null */
    private static $cache = null;

    public static function cache()
    {
        if (self::$cache === null) {
            self::$cache = new FileCache(['cachePath' => '@runtime/ai-cache']);
        }
        return self::$cache;
    }

    /**
     * @param string $sql a SELECT, written in this code
     * @param array $params bound values
     * @param int $ttl seconds to cache, 0 for none
     */
    public static function rows($sql, array $params = [], $ttl = 0)
    {
        $sql = self::guard($sql);
        if ($ttl > 0) {
            $key = 'ai-rows:' . md5($sql . serialize($params));
            $hit = self::cache()->get($key);
            if ($hit !== false) {
                return $hit;
            }
        }
        $rows = Yii::$app->db->createCommand($sql, $params)->queryAll();
        if ($ttl > 0) {
            self::cache()->set($key, $rows, $ttl);
        }
        return $rows;
    }

    public static function scalar($sql, array $params = [], $ttl = 0)
    {
        $rows = self::rows($sql, $params, $ttl);
        return $rows ? reset($rows[0]) : null;
    }

    /** SELECT only, with the execution-time hint added after the keyword. */
    private static function guard($sql)
    {
        $sql = ltrim($sql);
        if (!preg_match('/^SELECT\s/i', $sql) || preg_match('/;\s*\S/', $sql)) {
            throw new \LogicException('AiData runs single SELECT statements only.');
        }
        return preg_replace('/^SELECT\s/i', 'SELECT /*+ MAX_EXECUTION_TIME(' . self::MAX_MS . ') */ ', $sql, 1);
    }

    /** "IN (:p0,:p1,...)" and its params for a list of integers. */
    public static function inList(array $ids, $prefix, array &$params)
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) {
            return '(NULL)';
        }
        $names = [];
        foreach ($ids as $i => $id) {
            $params[':' . $prefix . $i] = $id;
            $names[] = ':' . $prefix . $i;
        }
        return '(' . implode(',', $names) . ')';
    }

    /** A Y-m-d date from free input, or $default; never anything else. */
    public static function date($value, $default)
    {
        $value = trim((string) $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $t = strtotime($value);
            if ($t !== false) {
                return date('Y-m-d', $t);
            }
        }
        return $default;
    }
}
