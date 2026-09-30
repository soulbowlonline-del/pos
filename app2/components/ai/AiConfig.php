<?php
namespace app\components\ai;

use Yii;

/**
 * Settings for the AI features, all read from .env so nothing is committed.
 *
 *   ANTHROPIC_API_KEY          the key. Without it the Claude features say so
 *                              and do nothing; the rule-based insights still
 *                              work, because they need no key and cost nothing.
 *   POS_AI_MODEL               default claude-sonnet-5-5 (claude-opus-5-5 costs twice as much)
 *   POS_AI_MONTHLY_BUDGET_USD  hard stop for the calendar month, default 50
 *   POS_AI_BILL_FALLBACK_MODEL the model a vendor bill is read again with when
 *                              the first reading fails, default claude-opus-5-5;
 *                              empty or "none" turns the second reading off
 *   POS_AI_ROLES               role ids allowed in, comma separated, default 1
 *                              (admin). Store staff see nothing new.
 *
 * Nothing here changes a formula, a stock figure or a bill: every AI screen
 * reads. A vendor's bill becomes a checklist, or - read on the GRN screen -
 * values typed into that screen's grid for a person to check and save with
 * its own Update button (GrnBillFill).
 */
class AiConfig
{
    /**
     * Sonnet 5.5, on the owner's instruction of 30 Sep 2026: half Opus 5.5's
     * price per token ($2/$10 per million against $4/$20) and ample for
     * lookups, summaries and reading bills. POS_AI_MODEL=claude-opus-5-5 in
     * .env switches back without a code change.
     */
    public const DEFAULT_MODEL = 'claude-sonnet-5-5';

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

    /**
     * The model a vendor bill is read again with when the first model's
     * reading fails, or null for no second reading. Opus 5.5 by default, on
     * the owner's instruction of 30 Sep 2026: Sonnet 5.5 reads first, at half
     * the price, and Opus only when Sonnet could not manage.
     */
    public static function billFallbackModel()
    {
        $m = getenv('POS_AI_BILL_FALLBACK_MODEL');
        $m = $m === false ? 'claude-opus-5-5' : trim($m);
        if ($m === '' || strtolower($m) === 'none' || $m === self::model()) {
            return null;
        }
        return $m;
    }

    /** A model id as people know it: claude-sonnet-5-5 -> Claude Sonnet 5.5. */
    public static function label($model)
    {
        if (preg_match('/^claude-([a-z]+)-(\d+)(?:-(\d))?(?:-\d{8})?$/', (string) $model, $m)) {
            return 'Claude ' . ucfirst($m[1]) . ' ' . $m[2] . (isset($m[3]) ? '.' . $m[3] : '');
        }
        return (string) $model;
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
