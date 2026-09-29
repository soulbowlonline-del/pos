<?php
namespace app\components\ai;

use Yii;

/**
 * Settings for the AI features, all read from .env so nothing is committed.
 *
 *   ANTHROPIC_API_KEY          the key. Without it the Claude features say so
 *                              and do nothing; the rule-based insights still
 *                              work, because they need no key and cost nothing.
 *   POS_AI_MODEL               default claude-opus-5-5
 *   POS_AI_MONTHLY_BUDGET_USD  hard stop for the calendar month, default 50
 *   POS_AI_ROLES               role ids allowed in, comma separated, default 1
 *                              (admin). Store staff see nothing new.
 *
 * Nothing here changes a formula, a stock figure or a bill: every AI screen
 * reads, and the one that reads a vendor's bill produces a checklist for a
 * person to enter through the existing GRN screen.
 */
class AiConfig
{
    public const DEFAULT_MODEL = 'claude-opus-5-5';

    /**
     * USD per million tokens: input, output, cache read, cache write (5 min).
     * List prices, including the models a server-side fallback can answer
     * with. Matched on the id without a dated-snapshot suffix; an unknown
     * model is priced at the dearest row, so the budget errs high.
     */
    public const PRICES = [
        'claude-fable-5-1' => [10.00, 50.00, 0.25, 12.50],
        'claude-fable-5' => [10.00, 50.00, 1.00, 12.50],
        'claude-opus-5-5' => [4.00, 20.00, 0.20, 5.00],
        'claude-opus-5' => [5.00, 25.00, 0.50, 6.25],
        'claude-opus-4-8' => [5.00, 25.00, 0.50, 6.25],
        'claude-opus-4-7' => [5.00, 25.00, 0.50, 6.25],
        'claude-sonnet-5-5' => [2.00, 10.00, 0.20, 2.50],
        'claude-sonnet-5' => [2.00, 10.00, 0.20, 2.50],
        'claude-sonnet-4-6' => [3.00, 15.00, 0.30, 3.75],
        'claude-haiku-4-5' => [1.00, 5.00, 0.10, 1.25],
    ];

    /** Models that take the server-side fallback ("default") this code sends. */
    public const FALLBACK_MODELS = ['claude-fable-5-1', 'claude-opus-5-5', 'claude-opus-5', 'claude-sonnet-5-5'];

    public static function apiKey()
    {
        $key = getenv('ANTHROPIC_API_KEY');
        return $key === false ? '' : trim($key);
    }

    public static function hasKey()
    {
        return self::apiKey() !== '';
    }

    /** The SDK is installed by `composer install`; a deploy that skipped it must not break the page. */
    public static function sdkInstalled()
    {
        return class_exists(\Anthropic\Client::class) && class_exists(\GuzzleHttp\Client::class);
    }

    public static function model()
    {
        $m = getenv('POS_AI_MODEL');
        return ($m === false || trim($m) === '') ? self::DEFAULT_MODEL : trim($m);
    }

    public static function monthlyBudget()
    {
        $b = getenv('POS_AI_MONTHLY_BUDGET_USD');
        return ($b === false || !is_numeric($b)) ? 50.0 : max(0.0, (float) $b);
    }

    public static function allowedRoles()
    {
        $r = getenv('POS_AI_ROLES');
        $r = ($r === false || trim($r) === '') ? '1' : $r;
        return array_values(array_filter(array_map('intval', explode(',', $r))));
    }

    /** May the signed-in user open the AI screens? */
    public static function userAllowed()
    {
        $user = Yii::$app->user->getIdentity();
        return $user !== null && in_array((int) $user->role_id, self::allowedRoles(), true);
    }

    /** Why Claude cannot be called right now, or null if it can. */
    public static function unavailableReason()
    {
        if (!self::sdkInstalled()) {
            return 'The AI library is not installed on this server yet. Run `composer install` (see docs/ai-features.md).';
        }
        if (!self::hasKey()) {
            return 'No Anthropic API key is set. Add ANTHROPIC_API_KEY to .env to switch on the Claude features.';
        }
        if (!AiLog::tableExists()) {
            return 'The AI usage table is missing. Run db_changes/2026-09-30-ai-log.sql first - spending is not allowed without it.';
        }
        return null;
    }

    public static function price($model)
    {
        $id = self::baseId($model);
        if (isset(self::PRICES[$id])) {
            return self::PRICES[$id];
        }
        $max = [0, 0, 0, 0];
        foreach (self::PRICES as $p) {
            if ($p[1] > $max[1]) {
                $max = $p;
            }
        }
        return $max;
    }

    public static function supportsFallback($model)
    {
        return in_array(self::baseId($model), self::FALLBACK_MODELS, true);
    }

    /** output_config.effort is rejected by Haiku 4.5. */
    public static function supportsEffort($model)
    {
        return strpos(self::baseId($model), 'claude-haiku') !== 0;
    }

    /** The table id a model id belongs to: itself, or itself minus a -YYYYMMDD suffix. */
    private static function baseId($model)
    {
        $model = (string) $model;
        return preg_replace('/-\d{8}$/', '', $model);
    }
}
