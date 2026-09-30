<?php
namespace app\components\ai;

/**
 * A short written briefing of the daily checks, for the owner.
 *
 * The checks themselves are computed by the database (Insights); Claude only
 * reads their counts and first rows and writes what to look at first. No
 * customer data is in them to begin with; masking runs anyway.
 */
class InsightSummary
{
    public const LANGUAGES = ['en' => 'English', 'hi' => 'Hindi (Devanagari script)', 'pa' => 'Punjabi (Gurmukhi script)'];

    public static function write($lang = 'en')
    {
        $language = self::LANGUAGES[$lang] ?? self::LANGUAGES['en'];
        $facts = [];
        foreach (Insights::checks() as $key => list($group, $title, $why)) {
            try {
                $r = Insights::run($key);
            } catch (\Throwable $e) {
                $facts[] = ['check' => $title, 'error' => 'could not be computed'];
                continue;
            }
            $rows = array_map(function ($row) { unset($row['_link']); return $row; }, array_slice($r['rows'], 0, 8));
            $facts[] = ['group' => $group, 'check' => $title, 'what_it_means' => $why, 'count' => $r['total'],
                        'note' => $r['note'], 'first_rows' => $rows];
        }
        $json = json_encode(AiPrivacy::maskAll($facts), JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

        $message = AiClient::create([
            'maxTokens' => 8000,
            'outputConfig' => ['effort' => 'low'],
            'system' => [['type' => 'text', 'cacheControl' => ['type' => 'ephemeral'], 'text' =>
                'You write the morning briefing for the owner of a retail and grocery business in Punjab, India, from the results of '
                . 'the store\'s automatic daily checks. Be brief and practical: at most eight bullet points, most urgent first, each '
                . 'saying what to do and naming the items, bills or vendors involved. Skip checks with nothing found, then end with '
                . 'one line listing them as clear. Use only the figures given; do not guess causes you cannot see in the data. Money '
                . 'in rupees with ₹ and Indian digit grouping. Markdown bullets and **bold** only.']],
            'messages' => [['role' => 'user', 'content' =>
                'Today is ' . date('l, j F Y') . ". Write the briefing in {$language}.\n\nCheck results (JSON):\n" . $json]],
        ], 'summary', 'insights summary (' . $lang . ')');

        if ($message->stopReason === 'refusal') {
            throw new AiException('Claude declined to write the summary.');
        }
        return AiClient::text($message);
    }
}
