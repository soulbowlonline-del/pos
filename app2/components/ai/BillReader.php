<?php
namespace app\components\ai;

use app\components\Ui;

/**
 * Reads a vendor's bill - a photo or a PDF - into lines, matches each line to
 * the item master and flags what does not agree.
 *
 * The result is a checklist for the storekeeper entering the GRN on the usual
 * screen: which item each line is, and what to look at before approving (an
 * MRP different from the master, a GST rate that does not match the item's
 * slab, a rate above the last purchase, a short expiry, lines that do not add
 * up to the bill total). Nothing is saved: no GRN, no stock, no price. The
 * uploaded file is read from PHP's temporary upload and not kept.
 *
 * On the GRN screen the same reading is typed into the grid: GrnBillFill.
 */
class BillReader
{
    public const MAX_IMAGE_BYTES = 5 * 1024 * 1024;
    public const MAX_PDF_BYTES = 10 * 1024 * 1024;
    public const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    private const PROMPT = <<<'TXT'
This is a purchase bill (tax invoice) from a supplier to a retail and grocery store in India. Read it and return its contents in the given JSON shape.

- One entry in "lines" per item line on the bill, in the order printed. Include every item line, across all pages; skip subtotal, tax-summary and scheme-summary rows.
- description: the item name exactly as printed. barcode: the EAN/UPC code if printed on the line. item_code: the supplier's own item code if printed. hsn: the HSN/SAC code.
- qty: billed quantity. free_qty: free or scheme quantity if shown separately, else 0. unit: as printed (PCS, KG, BOX...).
- Write qty, rate and amount exactly as printed, even when the bill counts cases, boxes or packs: do not convert them to pieces. pack_size: the number of pieces in one case/box/pack when the line is billed that way - from a pack or case column, or from the description, e.g. "(24*500ML)" or "24X500ML" is 24 - else null.
- rate: the unit rate as printed (usually before tax). mrp: the MRP per unit if printed. discount_percent: the line discount % if printed.
- gst_percent: the total GST rate of the line. If the bill shows CGST and SGST separately, add them (2.5 + 2.5 = 5). If IGST, use it.
- batch and expiry as printed; write expiry as YYYY-MM-DD, using the last day of the month when only month and year are printed.
- amount: the line amount as printed (the taxable value or the line total, whichever the bill shows per line).
- bill_no: the invoice number as printed. bill_date: the invoice date, written as YYYY-MM-DD (Indian bills print the day first: 05/09/2026 is 2026-09-05).
- bill_total: the final amount payable. tax_total: total GST on the bill.
- For anything not printed or not readable, use null for a number and an empty string for text. Do not calculate values that are not on the bill, except adding CGST and SGST as above.
- notes: one short sentence on anything that made the bill hard to read (cut off, blurred, handwritten), or an empty string.
TXT;

    /**
     * The shape the reading comes back in.
     *
     * The API compiles this schema and refuses one with more than 16
     * "either this or null" fields (a 400: "too many parameters with union
     * types") - which the first version, with all twenty optional fields
     * nullable, was, so no bill could be read at all. Text that is not
     * printed is now an empty string and only the nine numbers that can
     * really be absent may be null.
     */
    private static function schema()
    {
        $text = ['type' => 'string'];
        $number = ['anyOf' => [['type' => 'number'], ['type' => 'null']]];
        $lineProps = [
            'description' => $text, 'barcode' => $text, 'item_code' => $text, 'hsn' => $text,
            'qty' => $number, 'free_qty' => ['type' => 'number'], 'unit' => $text, 'pack_size' => $number, 'rate' => $number, 'mrp' => $number,
            'discount_percent' => $number, 'gst_percent' => $number, 'batch' => $text, 'expiry' => $text,
            'amount' => $number,
        ];
        $props = [
            'vendor_name' => $text, 'vendor_gstin' => $text, 'bill_no' => $text, 'bill_date' => $text,
            'lines' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => $lineProps,
                'required' => array_keys($lineProps), 'additionalProperties' => false]],
            'bill_total' => $number, 'tax_total' => $number, 'notes' => $text,
        ];
        return ['type' => 'object', 'properties' => $props, 'required' => array_keys($props), 'additionalProperties' => false];
    }

    /** Checks the upload; returns [mime, bytes] or throws AiException. */
    public static function validateUpload(array $file)
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new AiException('Choose a photo or PDF of the bill to upload.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $size = filesize($file['tmp_name']);
        if ($mime === 'application/pdf') {
            if ($size > self::MAX_PDF_BYTES) {
                throw new AiException('The PDF is larger than 10 MB.');
            }
        } elseif (in_array($mime, self::IMAGE_TYPES, true)) {
            if ($size > self::MAX_IMAGE_BYTES) {
                throw new AiException('The photo is larger than 5 MB. Take it at a lower resolution, or save it as a smaller JPEG.');
            }
        } else {
            throw new AiException('Only JPEG, PNG, WebP or GIF photos and PDF files can be read.');
        }
        return [$mime, file_get_contents($file['tmp_name'])];
    }

    /**
     * Reads the bill with the configured model (Sonnet 5.5 by default). If
     * that call fails, or its reading is unusable - no lines, or lines that do
     * not add up to the bill total - the bill is read once more with
     * AiConfig::billFallbackModel() (Opus 5.5 by default), on the owner's
     * instruction of 30 Sep 2026. A call that could not be made at all (no
     * key, budget reached, key refused) is not retried: another model would
     * meet the same wall.
     *
     * @return array review() plus 'model' (which one's reading is shown) and
     *               'retry' (null, or why the second model was asked)
     */
    public static function read($mime, $bytes, $fileName, $vendorId = null)
    {
        $source = ['type' => 'base64', 'mediaType' => $mime, 'data' => base64_encode($bytes)];
        $block = $mime === 'application/pdf'
            ? ['type' => 'document', 'source' => $source]
            : ['type' => 'image', 'source' => $source];

        $primary = AiConfig::model();
        $fallback = AiConfig::billFallbackModel();
        $bill = null;
        $problem = null;
        try {
            // With a second model standing by, it is the retry; no transport retry first.
            $bill = self::extract($block, $fileName, $primary, $fallback ? 0 : 1, 'bill');
            $problem = self::readingProblem($bill);
        } catch (AiException $e) {
            if ($e->isBlocked() || !$fallback) {
                throw $e;
            }
            $problem = $e->getMessage();
        }

        $model = $primary;
        $retry = null;
        if ($problem !== null && $fallback) {
            $retry = ['first' => $primary, 'why' => $problem, 'second' => $fallback, 'failed' => null];
            try {
                $second = self::extract($block, $fileName, $fallback, 1, 'bill-retry');
                // The second reading is shown unless it came back no better
                // than a first reading that at least has lines.
                if ($bill === null || self::readingProblem($second) === null || count($second['lines']) >= count($bill['lines'])) {
                    $bill = $second;
                    $model = $fallback;
                }
            } catch (AiException $e) {
                if ($bill === null) {
                    throw $e;
                }
                $retry['failed'] = $e->getMessage();
            }
        }
        if ($problem !== null && $bill !== null && !$bill['lines']) {
            throw new AiException('No lines could be read from this bill. Try a sharper photo, taken straight on and in good light.');
        }

        $result = self::review($bill, $vendorId);
        $result['model'] = $model;
        $result['retry'] = $retry;
        return $result;
    }

    /** One reading of the bill by one model, parsed; AiException if it cannot be used at all. */
    private static function extract(array $block, $fileName, $model, $retries, $feature)
    {
        $message = AiClient::create([
            'model' => $model,
            'maxTokens' => 16000,
            'outputConfig' => ['effort' => 'medium', 'format' => ['type' => 'json_schema', 'schema' => self::schema()]],
            'messages' => [['role' => 'user', 'content' => [$block, ['type' => 'text', 'text' => self::PROMPT]]]],
        ], $feature, 'bill: ' . $fileName, 110, $retries);

        if ($message->stopReason === 'refusal') {
            throw new AiException('Claude declined to read this file.');
        }
        if ($message->stopReason === 'max_tokens') {
            throw new AiException('The bill has more lines than can be read in one go. Upload it one page at a time.');
        }
        $bill = json_decode(AiClient::text($message), true);
        if (!is_array($bill) || !isset($bill['lines']) || !is_array($bill['lines'])) {
            throw new AiException('The bill could not be read. Try a sharper photo, taken straight on and in good light.');
        }
        return $bill;
    }

    /** Why a reading cannot be trusted as it stands, or null. */
    private static function readingProblem(array $bill)
    {
        if (!$bill['lines']) {
            return 'no item lines were read';
        }
        if (self::totalsGap($bill) !== null) {
            return 'the lines read do not add up to the bill total';
        }
        return null;
    }

    /**
     * [sum of line amounts, tax, bill total] when they disagree by more than
     * ₹1 or 1%, else null. Line amounts are taxable values on some bills and
     * tax-inclusive totals on others; either is accepted.
     */
    private static function totalsGap(array $bill)
    {
        $sum = 0.0;
        foreach ($bill['lines'] as $line) {
            $sum += (float) ($line['amount'] ?? 0);
        }
        $total = (float) ($bill['bill_total'] ?? 0);
        $tax = (float) ($bill['tax_total'] ?? 0);
        if ($total > 0 && $sum > 0) {
            $gap = min(abs($sum - $total), abs($sum + $tax - $total));
            if ($gap > max(1.0, 0.01 * $total)) {
                return [$sum, $tax, $total];
            }
        }
        return null;
    }

    /** Matching and checks. Public so it can be tested without an API call. */
    public static function review(array $bill, $vendorId = null)
    {
        try {
            $vendor = self::findVendor($bill, $vendorId);
        } catch (\Throwable $e) {
            \Yii::warning('Bill vendor lookup failed: ' . $e->getMessage(), __METHOD__);
            $vendor = null;
        }
        $lines = [];
        foreach ($bill['lines'] as $i => $line) {
            // The reading is already paid for: a lookup that fails for one
            // line must not throw the whole bill away.
            try {
                $match = self::match($line, $vendor ? (int) $vendor['id'] : null);
                $flags = self::flags($line, $match['item']);
            } catch (\Throwable $e) {
                \Yii::warning('Bill line match failed: ' . $e->getMessage(), __METHOD__);
                $match = ['item' => null, 'how' => null, 'alternatives' => []];
                $flags = [['danger', 'Could not be checked against the item master - check by hand']];
            }
            $lines[] = ['n' => $i + 1, 'line' => $line, 'match' => $match['item'], 'how' => $match['how'],
                        'alternatives' => $match['alternatives'], 'flags' => $flags];
        }
        $flags = [];
        if (($gap = self::totalsGap($bill)) !== null) {
            list($sum, $tax, $total) = $gap;
            $flags[] = sprintf('The lines add up to %s (%s with tax) but the bill total is %s. A line may have been missed or misread - check against the paper bill.',
                Insights::money($sum), Insights::money($sum + $tax), Insights::money($total));
        }
        if (!$vendor) {
            $flags[] = 'The vendor on the bill was not found in DASPOS' . (!empty($bill['vendor_gstin']) ? ' by GSTIN ' . $bill['vendor_gstin'] : '') . '.';
        }
        if (!empty($bill['notes'])) {
            $flags[] = 'Reading note: ' . $bill['notes'];
        }
        return ['bill' => $bill, 'vendor' => $vendor, 'lines' => $lines, 'flags' => $flags,
                'matched' => count(array_filter($lines, function ($l) { return $l['match'] !== null; }))];
    }

    private static function findVendor(array $bill, $vendorId)
    {
        if ($vendorId) {
            $rows = AiData::rows('SELECT id, name, tax_no FROM tbl_vendor WHERE id = :id', [':id' => (int) $vendorId]);
            if ($rows) {
                return $rows[0];
            }
        }
        $gstin = strtoupper(preg_replace('/\s+/', '', (string) ($bill['vendor_gstin'] ?? '')));
        if (strlen($gstin) === 15) {
            $rows = AiData::rows("SELECT id, name, tax_no FROM tbl_vendor WHERE UPPER(REPLACE(tax_no, ' ', '')) = :g ORDER BY status, id DESC LIMIT 1", [':g' => $gstin]);
            if ($rows) {
                return $rows[0];
            }
        }
        return null;
    }

    private static function itemSelect()
    {
        return 'SELECT d.id, d.bar_code, d.item_id, COALESCE(NULLIF(d.mrp, 0), i.mrp) mrp, i.hsn_code, i.title, i.purchase_price,'
            . ' COALESCE(NULLIF(t.tax_val1 + t.tax_val2, 0), t.tax_val4, 0) gst'
            . ' FROM tbl_item_detail d JOIN tbl_item i ON i.id = d.item_id LEFT JOIN tbl_tax t ON t.id = d.tax_id';
    }

    /** Barcode, then the vendor's own item code, then the name. */
    private static function match(array $line, $vendorId)
    {
        $none = ['item' => null, 'how' => null, 'alternatives' => []];
        $barcode = preg_replace('/\s+/', '', (string) ($line['barcode'] ?? ''));
        if ($barcode !== '') {
            $rows = AiData::rows(self::itemSelect() . ' WHERE d.bar_code = :b AND d.status = 0 AND i.status = 0 ORDER BY d.id DESC LIMIT 1', [':b' => $barcode]);
            if ($rows) {
                return ['item' => self::enrich($rows[0]), 'how' => 'barcode', 'alternatives' => []];
            }
        }
        $code = trim((string) ($line['item_code'] ?? ''));
        if ($code !== '' && $vendorId) {
            $rows = AiData::rows(self::itemSelect() . ' JOIN tbl_item_vendor iv ON iv.item_detail_id = d.id'
                . ' WHERE iv.vendor_id = :v AND iv.item_code = :c AND d.status = 0 AND i.status = 0 ORDER BY d.id DESC LIMIT 1',
                [':v' => (int) $vendorId, ':c' => $code]);
            if ($rows) {
                return ['item' => self::enrich($rows[0]), 'how' => 'vendor code', 'alternatives' => []];
            }
        }
        $name = self::normalise((string) ($line['description'] ?? ''));
        $words = array_values(array_filter(explode(' ', $name), function ($w) {
            return mb_strlen($w) >= 3 && !preg_match('/^\d+(ml|l|ltr|g|gm|gms|kg|pcs|pc)?$/', $w);
        }));
        if (!$words) {
            return $none;
        }
        usort($words, function ($a, $b) { return mb_strlen($b) <=> mb_strlen($a); });
        $params = [];
        $conds = [];
        foreach (array_slice($words, 0, 2) as $k => $w) {
            $params[':w' . $k] = '%' . strtr($w, ['\\' => '\\\\', '%' => '\%', '_' => '\_']) . '%';
            $conds[] = 'i.title LIKE :w' . $k;
        }
        $rows = AiData::rows(self::itemSelect() . ' WHERE ' . implode(' AND ', $conds) . ' AND d.status = 0 AND i.status = 0 LIMIT 60', $params);
        if (!$rows && count($conds) > 1) {
            unset($params[':w1']);
            $rows = AiData::rows(self::itemSelect() . ' WHERE ' . $conds[0] . ' AND d.status = 0 AND i.status = 0 LIMIT 60', $params);
        }
        $scored = [];
        foreach ($rows as $r) {
            similar_text($name, self::normalise($r['title']), $pct);
            $scored[] = [$pct, $r];
        }
        usort($scored, function ($a, $b) { return $b[0] <=> $a[0]; });
        $alts = [];
        foreach (array_slice($scored, 0, 3) as $s) {
            $alts[] = ['title' => $s[1]['title'], 'barcode' => $s[1]['bar_code'], 'score' => (int) round($s[0]),
                       'link' => Ui::to('item/update', ['id' => $s[1]['item_id']])];
        }
        if ($scored && $scored[0][0] >= 70) {
            return ['item' => self::enrich($scored[0][1]), 'how' => 'name ' . (int) round($scored[0][0]) . '%',
                    'alternatives' => array_slice($alts, 1)];
        }
        return ['item' => null, 'how' => null, 'alternatives' => $alts];
    }

    private static function enrich(array $item)
    {
        $item['last_price'] = AiData::scalar(
            'SELECT price FROM tbl_purchase_bill_detail WHERE item_detail_id = :d AND price > 0 ORDER BY id DESC LIMIT 1',
            [':d' => (int) $item['id']]);
        $item['link'] = Ui::to('item/update', ['id' => $item['item_id']]);
        return $item;
    }

    private static function flags(array $line, $item)
    {
        $flags = [];
        $expiry = (string) ($line['expiry'] ?? '');
        if ($expiry !== '' && ($t = strtotime($expiry)) !== false) {
            $days = (int) floor(($t - strtotime(date('Y-m-d'))) / 86400);
            if ($days < 0) {
                $flags[] = ['danger', 'Expired ' . date('d M Y', $t)];
            } elseif ($days <= 30) {
                $flags[] = ['warning', 'Expires in ' . $days . ' day' . ($days === 1 ? '' : 's')];
            }
        }
        if ($item === null) {
            $flags[] = ['info', 'Not found in the item master'];
            return $flags;
        }
        $mrp = $line['mrp'] ?? null;
        if ($mrp !== null && (float) $item['mrp'] > 0 && abs((float) $mrp - (float) $item['mrp']) > 0.5) {
            $flags[] = ['warning', 'MRP ' . Insights::money($mrp) . ' on bill, ' . Insights::money($item['mrp']) . ' in master'];
        }
        $gst = $line['gst_percent'] ?? null;
        if ($gst !== null && abs((float) $gst - (float) $item['gst']) > 0.01) {
            $flags[] = ['warning', 'GST ' . (float) $gst . '% on bill, ' . (float) $item['gst'] . '% in master'];
        }
        $hsn = preg_replace('/\D/', '', (string) ($line['hsn'] ?? ''));
        $masterHsn = preg_replace('/\D/', '', (string) $item['hsn_code']);
        if ($hsn !== '' && $masterHsn !== '' && substr($hsn, 0, 4) !== substr($masterHsn, 0, 4)) {
            $flags[] = ['info', 'HSN ' . $hsn . ' on bill, ' . $masterHsn . ' in master'];
        }
        $rate = $line['rate'] ?? null;
        if ($rate !== null && (float) $item['last_price'] > 0 && (float) $rate > 1.02 * (float) $item['last_price']) {
            $flags[] = ['warning', sprintf('Rate %s is %.1f%% above the last purchase (%s)', Insights::money($rate),
                100 * ((float) $rate / (float) $item['last_price'] - 1), Insights::money($item['last_price']))];
        }
        return $flags;
    }

    /** Lower case, letters and digits only; what names are compared by. GrnBillFill compares with it too. */
    public static function normalise($s)
    {
        $s = mb_strtolower($s);
        $s = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $s);
        return trim(preg_replace('/\s+/', ' ', $s));
    }
}
