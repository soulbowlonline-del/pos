<?php
namespace app\components\ai;

use Anthropic\Beta\Messages\BetaToolUseBlock;

/**
 * "Ask DASPOS": a question in plain words, answered from the store's own data.
 *
 * Claude is given the read-only tools in AskTools and loops - asks for data,
 * gets it, asks for more - until it can answer. Each tool call is shown under
 * the answer, so whoever asked can see which figures it used.
 */
class AskDaspos
{
    public const MAX_ROUNDS = 6;

    /** No new round of lookups is started after this many seconds. */
    public const DEADLINE_SECONDS = 100;

    /** Per call: an answer round rarely takes a fraction of this. */
    private const CALL_TIMEOUT = 75;

    private const SYSTEM = <<<'TXT'
You are the data assistant inside DASPOS, the point-of-sale and ERP system of a retail and grocery business in Punjab, India. The people asking are the owner and store managers.

Answer questions about sales, items, stock, purchases from vendors, refunds and the store's daily checks by calling the tools, which read the live database. Rules:

- Every figure in your answer must come from a tool result in this conversation. Never estimate or invent a number. If the tools cannot answer the question, say so plainly and say what data would be needed.
- Call tools as needed, in parallel where the calls are independent. Prefer one call with a group_by over many calls.
- Money is in Indian rupees: write ₹ with Indian digit grouping (₹1,25,000.50). Quantities as numbers with their unit when known.
- Sales totals from sales_summary are bill totals including tax, by bill date. Item figures come from bill lines, by the time the line was billed. Mention this only when it matters for the question.
- When comparing periods, give both figures and the change (absolute and %).
- Keep answers short: a one-line direct answer first, then a small table or a few bullet points if they help. Use Markdown (**bold**, bullet lists, pipe tables). No headings longer than a few words.
- Reply in the language and script of the question: English, Hindi (Devanagari, or Roman if the question is in Roman Hindi) or Punjabi (Gurmukhi, or Roman if the question is in Roman Punjabi). Item names stay as they are in the data.
- You cannot change anything in the system. If asked to create, edit, approve or delete something, explain which DASPOS screen does it, and offer the figures that would help.
- Never try to reveal customers' personal details; the tools deliberately give only counts for customers.
TXT;

    /**
     * @param string $question
     * @param array $history earlier [question, answer] pairs from this page, oldest first
     * @return array ['answer' => markdown, 'calls' => [[name, input, summary]], 'stop' => string]
     */
    public static function answer($question, array $history = [])
    {
        $question = trim(mb_substr((string) $question, 0, 2000));
        if ($question === '') {
            throw new AiException('Type a question first.');
        }
        $context = "Today is " . date('l, j F Y') . " (India time).\n";
        $outlets = [];
        foreach (Insights::outlets() as $id => $title) {
            $outlets[] = $id . ' = ' . $title;
        }
        $context .= 'Outlets: ' . ($outlets ? implode('; ', $outlets) : 'none') . ".\n";
        $earlier = '';
        foreach (array_slice($history, -3) as $pair) {
            $earlier .= 'Q: ' . mb_substr((string) ($pair[0] ?? ''), 0, 500) . "\nA: " . mb_substr((string) ($pair[1] ?? ''), 0, 1500) . "\n\n";
        }
        if ($earlier !== '') {
            $context .= "\nEarlier in this conversation:\n" . AiPrivacy::mask($earlier);
        }

        $messages = [['role' => 'user', 'content' => [
            ['type' => 'text', 'text' => $context],
            ['type' => 'text', 'text' => 'Question: ' . AiPrivacy::mask($question)],
        ]]];
        $tools = AskTools::definitions();
        $calls = [];
        $started = microtime(true);

        for ($round = 1; $round <= self::MAX_ROUNDS; $round++) {
            if ($round > 1 && microtime(true) - $started > self::DEADLINE_SECONDS) {
                return ['answer' => 'This question needed too many lookups to answer in time. Try asking it in smaller parts.',
                        'calls' => $calls, 'stop' => 'deadline'];
            }
            $message = AiClient::create([
                'maxTokens' => 16000,
                'system' => [['type' => 'text', 'text' => self::SYSTEM, 'cacheControl' => ['type' => 'ephemeral']]],
                'tools' => $tools,
                'outputConfig' => ['effort' => 'low'],
                'messages' => $messages,
            ], 'ask', $question, self::CALL_TIMEOUT, 1);

            if ($message->stopReason === 'refusal') {
                return ['answer' => 'Claude declined to answer this question.', 'calls' => $calls, 'stop' => 'refusal'];
            }
            if ($message->stopReason !== 'tool_use') {
                $text = AiClient::text($message);
                if ($message->stopReason === 'max_tokens') {
                    $text .= "\n\n_(The answer was cut short.)_";
                }
                return ['answer' => $text !== '' ? $text : 'No answer came back. Try rephrasing the question.',
                        'calls' => $calls, 'stop' => (string) $message->stopReason];
            }

            $results = [];
            foreach ($message->content as $block) {
                if (!$block instanceof BetaToolUseBlock) {
                    continue;
                }
                $input = $block->input;
                try {
                    $data = AiPrivacy::maskAll(AskTools::run($block->name, $input));
                    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
                    $results[] = ['type' => 'tool_result', 'toolUseID' => $block->id, 'content' => $json];
                    $calls[] = [$block->name, $input, self::describe($data)];
                } catch (\InvalidArgumentException $e) {
                    $results[] = ['type' => 'tool_result', 'toolUseID' => $block->id, 'content' => $e->getMessage(), 'isError' => true];
                    $calls[] = [$block->name, $input, 'refused: ' . $e->getMessage()];
                } catch (\Throwable $e) {
                    \Yii::warning('Ask tool ' . $block->name . ' failed: ' . $e->getMessage(), __METHOD__);
                    $results[] = ['type' => 'tool_result', 'toolUseID' => $block->id,
                                  'content' => 'The database lookup failed or took too long. Try a shorter period or a narrower question.', 'isError' => true];
                    $calls[] = [$block->name, $input, 'failed'];
                }
            }
            // The assistant turn goes back exactly as it came, thinking blocks included.
            $messages[] = ['role' => 'assistant', 'content' => $message->content];
            $messages[] = ['role' => 'user', 'content' => $results];
        }
        return ['answer' => 'This question needed more lookups than allowed. Try asking it in smaller parts.',
                'calls' => $calls, 'stop' => 'rounds'];
    }

    /** A few words about a tool result, for the "figures used" list. */
    private static function describe($data)
    {
        foreach (['rows', 'items', 'batches', 'outlets'] as $k) {
            if (isset($data[$k]) && is_array($data[$k])) {
                $n = isset($data[$k]['truncated_from']) ? $data[$k]['truncated_from'] : count($data[$k]);
                return $n . ' row' . ($n === 1 ? '' : 's');
            }
        }
        return isset($data['note']) ? (string) $data['note'] : 'ok';
    }
}
