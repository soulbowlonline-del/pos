<?php
namespace app\components\ai;

/**
 * The database is full of real customers' phone numbers and names. The
 * AI tools are written so that none of those leave the server - they answer in
 * totals, items, vendors and outlets, never per customer - and this is the
 * second line: anything that looks like a phone number or an e-mail address is
 * masked before it is sent or logged.
 */
class AiPrivacy
{
    public static function mask($text)
    {
        $text = (string) $text;
        $text = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[email]', $text);
        // Indian mobile numbers, with or without +91 / 0 and spacing.
        $text = preg_replace('/(?<!\d)(?:\+?91[\s-]?|0)?[6-9]\d{4}[\s-]?\d{5}(?!\d)/', '[phone]', $text);
        return $text;
    }

    /**
     * mask() over every string in a nested array, for tool results. Barcodes
     * are left alone: a ten-digit one starting 6-9 looks like a phone number.
     */
    public static function maskAll($value, $key = null)
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = self::maskAll($v, $k);
            }
            return $out;
        }
        if (!is_string($value) || in_array($key, ['barcode', 'bar_code'], true)) {
            return $value;
        }
        return self::mask($value);
    }
}
