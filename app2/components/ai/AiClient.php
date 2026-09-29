<?php
namespace app\components\ai;

use Anthropic\Beta\Messages\BetaMessage;
use Anthropic\Client;
use Anthropic\Core\Exceptions\AnthropicException;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\APITimeoutException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\PermissionDeniedException;
use Anthropic\Core\Exceptions\RateLimitException;
use Yii;

/**
 * The one place the application talks to Claude.
 *
 * Every call goes through here so that three things hold for all of them:
 * nothing is sent without a key, the usage table and money left in the
 * month's budget; every call is logged with its cost; and a failure reaches the
 * screen as a sentence, never as a stack trace in the middle of a page.
 *
 * Requests use the beta messages endpoint for the server-side refusal
 * fallback (on the models that support it): if the model declines a request on
 * policy grounds, the API retries it on its default fallback model instead of
 * failing. Every attempt is priced at the rate of the model that made it.
 */
class AiClient
{
    private const FALLBACK_BETA = 'server-side-fallback-2026-07-01';

    /**
     * What a call that timed out is booked at. Anthropic may have done (and
     * billed) the work although no answer arrived; the tokens are unknown, so
     * a typical call's cost is counted against the budget rather than none.
     */
    private const TIMEOUT_ESTIMATE_USD = ['ask' => 0.05, 'bill' => 0.15, 'summary' => 0.03];

    /**
     * @param array $params named arguments for beta->messages->create():
     *                      messages, maxTokens, system, tools, outputConfig...
     * @param string $feature short name for the log (ask, bill, summary)
     * @param string $summary what was asked, for the log - masked here
     * @param int $timeout seconds to wait for this call
     * @param int $retries retries on a transport failure or 429/5xx
     */
    public static function create(array $params, $feature, $summary, $timeout = 110, $retries = 1)
    {
        $reason = AiConfig::unavailableReason();
        if ($reason !== null) {
            throw new AiException($reason);
        }
        $budget = AiConfig::monthlyBudget();
        $spent = AiLog::spentThisMonth();
        if ($spent >= $budget) {
            AiLog::write($feature, AiConfig::model(), self::noUsage(), 'budget', AiPrivacy::mask($summary), 0.0);
            throw new AiException(sprintf(
                'This month\'s AI budget of $%.2f has been used ($%.2f spent). The AI features pause until next month or until POS_AI_MONTHLY_BUDGET_USD is raised; everything else keeps working.',
                $budget, $spent));
        }

        $model = AiConfig::model();
        $params += ['model' => $model];
        if (AiConfig::supportsFallback($model)) {
            // Server-side refusal fallback: if the model declines on policy
            // grounds, the API retries on its default fallback model.
            $params += ['fallbacks' => 'default', 'betas' => [self::FALLBACK_BETA]];
        }
        if (!AiConfig::supportsEffort($model) && isset($params['outputConfig']['effort'])) {
            unset($params['outputConfig']['effort']);
        }
        // POS_AI_BASE_URL exists for tests against a stand-in server. The
        // SDK's own ANTHROPIC_BASE_URL is not honoured: a value inherited from
        // the host's environment must not redirect the store's key.
        $base = getenv('POS_AI_BASE_URL');
        $client = new Client(
            apiKey: AiConfig::apiKey(),
            authToken: '', // the key only; no token from the environment
            baseUrl: ($base === false || $base === '') ? 'https://api.anthropic.com' : $base,
        );
        // The SDK leaves timeouts to the transport, so the transport carries them.
        $params['requestOptions'] = [
            'transporter' => new \GuzzleHttp\Client(['timeout' => $timeout, 'connect_timeout' => 10]),
            'maxRetries' => $retries,
        ];

        @set_time_limit(($timeout + 15) * ($retries + 1) + 30);
        try {
            /** @var BetaMessage $message */
            $message = $client->beta->messages->create(...$params);
        } catch (AuthenticationException | PermissionDeniedException $e) {
            self::fail($feature, $summary, $e);
            throw new AiException('The Anthropic API key was refused. Check ANTHROPIC_API_KEY in .env.');
        } catch (RateLimitException $e) {
            self::fail($feature, $summary, $e);
            throw new AiException('Claude is receiving too many requests right now. Try again in a minute.');
        } catch (APIStatusException $e) {
            self::fail($feature, $summary, $e);
            if ((int) $e->status >= 500) {
                throw new AiException('Claude is busy or unavailable right now (' . (int) $e->status . '). Try again in a minute.');
            }
            throw new AiException('Claude could not process this request (' . (int) $e->status . '). It has been logged.');
        } catch (APITimeoutException $e) {
            self::fail($feature, $summary, $e, 'timeout', self::TIMEOUT_ESTIMATE_USD[$feature] ?? 0.05);
            throw new AiException('Claude took too long to answer. Try again, or ask a narrower question.');
        } catch (APIConnectionException $e) {
            self::fail($feature, $summary, $e);
            throw new AiException('Could not reach Claude from the server. Check the server\'s internet connection.');
        } catch (AnthropicException $e) {
            self::fail($feature, $summary, $e);
            throw new AiException('The answer from Claude could not be read. It has been logged; try again in a minute.');
        }

        list($usage, $cost) = self::usageAndCost($message, $params['model']);
        $status = $message->stopReason === 'refusal' ? 'refused' : 'ok';
        AiLog::write($feature, $message->model, $usage, $status, AiPrivacy::mask($summary), $cost);
        return $message;
    }

    /** The text of a message, thinking and tool blocks left out. */
    public static function text($message)
    {
        $out = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $out .= $block->text;
            }
        }
        return trim($out);
    }

    /**
     * Tokens and cost of a response. With a server-side fallback the
     * top-level usage covers only the attempt that answered; every attempt,
     * declined ones included, is listed in usage.iterations with its own model,
     * and each is priced at that model's rate.
     *
     * @return array [usage totals, cost in USD]
     */
    public static function usageAndCost($message, $requestModel)
    {
        $u = $message->usage;
        $iterations = isset($u->iterations) && is_array($u->iterations) ? $u->iterations : [];
        $total = self::noUsage();
        $cost = 0.0;
        $counted = false;
        foreach ($iterations as $it) {
            if (!in_array($it->type ?? '', ['message', 'fallback_message'], true)) {
                continue; // compaction / advisor entries are not produced by these calls
            }
            $one = [
                'input' => (int) $it->inputTokens,
                'output' => (int) $it->outputTokens,
                'cache_read' => (int) ($it->cacheReadInputTokens ?? 0),
                'cache_write' => (int) ($it->cacheCreationInputTokens ?? 0),
            ];
            foreach ($one as $k => $v) {
                $total[$k] += $v;
            }
            $cost += AiLog::cost(($it->model ?? null) ?: $requestModel, $one);
            $counted = true;
        }
        if (!$counted) {
            $total = [
                'input' => (int) $u->inputTokens,
                'output' => (int) $u->outputTokens,
                'cache_read' => (int) ($u->cacheReadInputTokens ?? 0),
                'cache_write' => (int) ($u->cacheCreationInputTokens ?? 0),
            ];
            $cost = AiLog::cost($message->model ?: $requestModel, $total);
        }
        return [$total, $cost];
    }

    private static function noUsage()
    {
        return ['input' => 0, 'output' => 0, 'cache_read' => 0, 'cache_write' => 0];
    }

    private static function fail($feature, $summary, \Throwable $e, $status = 'error', $cost = 0.0)
    {
        Yii::warning('Claude call failed (' . $feature . '): ' . get_class($e) . ' ' . $e->getMessage(), __METHOD__);
        AiLog::write($feature, AiConfig::model(), self::noUsage(), $status, AiPrivacy::mask($summary), $cost);
    }
}
