<?php
namespace app\components\ai;

use Yii;

/**
 * Every Claude call is written to tbl_ai_log: who asked, which feature, which
 * model answered, the tokens, and what it cost. The monthly budget is the sum
 * of that column, so a call is refused before it is made once the month's
 * spend reaches POS_AI_MONTHLY_BUDGET_USD.
 */
class AiLog
{
    private static $exists = null;

    public static function tableExists()
    {
        if (self::$exists === null) {
            try {
                self::$exists = Yii::$app->db->getTableSchema('tbl_ai_log', true) !== null;
            } catch (\Throwable $e) {
                self::$exists = false;
            }
        }
        return self::$exists;
    }

    public static function spentThisMonth()
    {
        if (!self::tableExists()) {
            return 0.0;
        }
        return (float) Yii::$app->db->createCommand(
            'SELECT COALESCE(SUM(cost_usd), 0) FROM tbl_ai_log WHERE create_time >= :m',
            [':m' => date('Y-m-01 00:00:00')])->queryScalar();
    }

    public static function cost($model, $usage)
    {
        list($in, $out, $cacheRead, $cacheWrite) = AiConfig::price($model);
        return ($usage['input'] * $in + $usage['output'] * $out
                + $usage['cache_read'] * $cacheRead + $usage['cache_write'] * $cacheWrite) / 1e6;
    }

    /** @param float|null $cost USD; worked out from $model and $usage when null */
    public static function write($feature, $model, $usage, $status, $summary, $cost = null)
    {
        if (!self::tableExists()) {
            return;
        }
        Yii::$app->db->createCommand()->insert('tbl_ai_log', [
            'feature' => substr($feature, 0, 32),
            'user_id' => Yii::$app->has('user') ? Yii::$app->user->getId() : null,
            'model' => substr((string) $model, 0, 64),
            'input_tokens' => (int) $usage['input'],
            'output_tokens' => (int) $usage['output'],
            'cache_read_tokens' => (int) $usage['cache_read'],
            'cache_write_tokens' => (int) $usage['cache_write'],
            'cost_usd' => round($cost === null ? self::cost($model, $usage) : (float) $cost, 5),
            'status' => substr($status, 0, 16),
            'summary' => mb_substr((string) $summary, 0, 250),
            'create_time' => date('Y-m-d H:i:s'),
        ])->execute();
    }

    /** Spend per feature and per day, for the usage page. */
    public static function report($days = 31)
    {
        if (!self::tableExists()) {
            return ['features' => [], 'days' => [], 'recent' => []];
        }
        $db = Yii::$app->db;
        $from = date('Y-m-d 00:00:00', strtotime('-' . (int) $days . ' days'));
        return [
            'features' => $db->createCommand(
                'SELECT feature, COUNT(*) calls, SUM(cost_usd) cost, SUM(input_tokens) input_tokens, SUM(output_tokens) output_tokens'
                . ' FROM tbl_ai_log WHERE create_time >= :f GROUP BY feature ORDER BY cost DESC', [':f' => $from])->queryAll(),
            'days' => $db->createCommand(
                'SELECT DATE(create_time) day, COUNT(*) calls, SUM(cost_usd) cost'
                . ' FROM tbl_ai_log WHERE create_time >= :f GROUP BY DATE(create_time) ORDER BY day DESC', [':f' => $from])->queryAll(),
            'recent' => $db->createCommand(
                'SELECT l.create_time, l.feature, l.model, l.status, l.cost_usd, l.summary, u.full_name'
                . ' FROM tbl_ai_log l LEFT JOIN tbl_user u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 30')->queryAll(),
        ];
    }
}
