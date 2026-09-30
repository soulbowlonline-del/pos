<?php
namespace app\components\ai;

/**
 * Turns a vendor's bill, as read by BillReader, into values for the grid of
 * the GRN screen (purchaseBillDetail/index).
 *
 * The grid already holds the lines of the GRN that is open, usually from its
 * purchase order and put in the bill's order when the goods were received on
 * the Android app. This class pairs the bill's lines with the grid's, line by
 * line in that order (see assign()), and says what a storekeeper would type
 * there: received qty, rate, MRP and discount %. Bill lines with no grid
 * line, and grid lines the bill does not mention, are listed and left alone.
 *
 * It saves nothing and works nothing out. v2/js/grn-bill.js types the values
 * into the grid's own inputs and fires their change events, so every amount,
 * tax and total comes from the screen's own formulas, and nothing reaches the
 * database until Update is pressed - exactly as when the bill is typed in by
 * hand. Owner's instruction, 30 Sep 2026: the bill is read on the GRN screen,
 * under the Merge button, and fills the grid.
 */
class GrnBillFill
{
    /** A name has to be this alike (similar_text %) to pair a bill line with a grid line; BillReader's own threshold. */
    public const NAME_MATCH = 70;

    /** The GRN a bill is being read for; AiException when it cannot take one. */
    public static function grn($poid)
    {
        $rows = AiData::rows(
            'SELECT pb.id, pb.status, pb.vendor_id, v.name vendor, v.tax_no FROM tbl_purchase_bill pb'
            . ' LEFT JOIN tbl_vendor v ON v.id = pb.vendor_id WHERE pb.id = :id', [':id' => (int) $poid]);
        if (!$rows) {
            throw new AiException('Choose a GRN first: pick the vendor and the purchase bill number, then read the bill.');
        }
        if ((int) $rows[0]['status'] === 1) {
            throw new AiException('This GRN is already approved; its lines can no longer be changed.');
        }
        return $rows[0];
    }

    /**
     * The grid's lines, in the grid's order. entry_position is the place the
     * Android app gave a line when it was received (api item/updateStock writes
     * its entry_position to `order`): 1, 2, 3... in the order the goods were
     * scanned against the bill, and 0 for a line not received that way.
     */
    public static function rows($poid)
    {
        return AiData::rows(
            'SELECT d.id, d.item_detail_id, d.item_id, d.is_free, d.req_qty, d.approved_qty, d.price, d.mrp, d.discount, d.`order` entry_position,'
            . ' i.title, idt.bar_code, COALESCE(NULLIF(t.tax_val1 + t.tax_val2, 0), t.tax_val4, 0) gst'
            . ' FROM tbl_purchase_bill_detail d LEFT JOIN tbl_item i ON i.id = d.item_id'
            . ' LEFT JOIN tbl_item_detail idt ON idt.id = d.item_detail_id LEFT JOIN tbl_tax t ON t.id = d.tax_id'
            . ' WHERE d.purchase_bill_id = :p ORDER BY d.`order`, d.id', [':p' => (int) $poid]);
    }

    /**
     * @param array $result BillReader::read()'s result
     * @param array $grn    self::grn()
     * @param array $rows   self::rows()
     * @return array what grn-bill.js fills in and shows
     */
    public static function plan(array $result, array $grn, array $rows)
    {
        $bill = $result['bill'];
        $flags = $result['flags'];
        $gstin = strtoupper(preg_replace('/\s+/', '', (string) ($bill['vendor_gstin'] ?? '')));
        $ours = strtoupper(preg_replace('/\s+/', '', (string) ($grn['tax_no'] ?? '')));
        if (strlen($gstin) === 15 && strlen($ours) === 15 && $gstin !== $ours) {
            array_unshift($flags, 'The GSTIN on the bill (' . $gstin . ') is not that of this GRN\'s vendor, '
                . $grn['vendor'] . ' (' . $ours . '). Check that the bill belongs to this GRN.');
        }
        $retry = null;
        if (!empty($result['retry'])) {
            $r = $result['retry'];
            $retry = AiConfig::label($r['first']) . ' could not read this bill cleanly (' . rtrim($r['why'], '.') . '), so it was read again by '
                . AiConfig::label($r['second']) . '.' . ($r['failed'] ? ' That second reading failed too; the first reading is used.' : '');
        }
        return [
            'file' => (string) ($result['file'] ?? ''),
            'model' => AiConfig::label($result['model'] ?? ''),
            'retry' => $retry,
            'vendor' => (string) $grn['vendor'],
            'bill' => [
                'vendor_name' => (string) ($bill['vendor_name'] ?? ''),
                'bill_no' => trim((string) ($bill['bill_no'] ?? '')),
                'bill_date' => self::billDate((string) ($bill['bill_date'] ?? '')),
                'bill_date_printed' => (string) ($bill['bill_date'] ?? ''),
                'bill_total' => self::num($bill['bill_total'] ?? null),
                'tax_total' => self::num($bill['tax_total'] ?? null),
            ],
            'flags' => $flags,
            'line_count' => count($result['lines']),
        ] + self::assign($result['lines'], $rows);
    }

    /**
     * Pairs bill lines with grid lines. No database, so it can be tested on
     * its own.
     *
     * Line by line, in order (owner, 30 Sep 2026): the goods are received on
     * the Android app against the bill, which numbers the GRN's lines in the
     * bill's order, while the names on a vendor's bill often differ from the
     * item master's. So the bill and the received lines are aligned as
     * sequences - a bill line pairs with the grid line at the same place in
     * the order of receiving - and what each pair has in
     * common (barcode, the item BillReader found, name, MRP, rate) decides
     * where the two lists fall out of step: a bill line the GRN does not
     * have, or a GRN line the bill does not have, is stepped over instead of
     * shifting every line after it. A pair that rests on its place alone is
     * filled and flagged for checking.
     *
     * Bill lines left over are then looked for out of order, surest first:
     * the barcode or the item-master match, the same item under another
     * barcode, the name among the grid lines still free. A grid line is used
     * once: a second bill line for the same item (a second batch) is listed
     * as not on the GRN.
     *
     * @param array $lines BillReader::review()'s lines
     * @param array $rows  self::rows(), in the grid's order
     * @return array fills, missing, unmatched, untouched
     */
    public static function assign(array $lines, array $rows)
    {
        $paid = [];
        $free = [];
        foreach ($rows as $r) {
            if ((int) $r['is_free'] === 1) {
                $free[(int) $r['id']] = $r;
            } else {
                $paid[(int) $r['id']] = $r;
            }
        }
        $used = [];
        $taken = [];
        $first = function (array $pool, callable $test) use (&$used) {
            foreach ($pool as $id => $r) {
                if (!isset($used[$id]) && $test($r)) {
                    $used[$id] = true;
                    return $r;
                }
            }
            return null;
        };

        // The lines received on the app, in the order they were received.
        // Lines with no place (not received, or added on the web) are not in
        // the bill's order and are only looked for by what they are, below.
        $sequence = array_values(array_filter($paid, function ($r) { return (int) ($r['entry_position'] ?? 0) > 0; }));
        usort($sequence, function ($a, $b) {
            return [(int) $a['entry_position'], (int) $a['id']] <=> [(int) $b['entry_position'], (int) $b['id']];
        });
        $pairFlag = [];
        foreach (self::align(array_values($lines), $sequence) as $i => $pair) {
            list($row, $how, $flag) = $pair;
            $index = array_keys($lines)[$i];
            $taken[$index] = [$row, $how];
            $used[(int) $row['id']] = true;
            if ($flag !== null) {
                $pairFlag[$index] = $flag;
            }
        }

        // A pair resting on its place alone gives way to a line of the GRN,
        // still free, that carries the bill line's name: two lines received
        // in the other order, or a line the app never numbered.
        foreach ($pairFlag as $i => $flag) {
            if ($flag[0] !== 'warning') {
                continue;
            }
            list($row, $pct) = self::byName($lines[$i], $paid, $used);
            if ($row !== null) {
                unset($used[(int) $taken[$i][0]['id']], $pairFlag[$i]);
                $used[(int) $row['id']] = true;
                $taken[$i] = [$row, 'name ' . (int) round($pct) . '%, among this GRN\'s lines'];
            }
        }

        foreach ($lines as $i => $l) {
            if (isset($taken[$i])) {
                continue;
            }
            $barcode = preg_replace('/\s+/', '', (string) ($l['line']['barcode'] ?? ''));
            if ($barcode !== '' && ($r = $first($paid, function ($r) use ($barcode) { return (string) $r['bar_code'] === $barcode; }))) {
                $taken[$i] = [$r, 'barcode'];
                continue;
            }
            $m = $l['match'];
            if ($m && ($r = $first($paid, function ($r) use ($m) { return (int) $r['item_detail_id'] === (int) $m['id']; }))) {
                $taken[$i] = [$r, (string) $l['how']];
            }
        }
        foreach ($lines as $i => $l) {
            $m = $l['match'];
            if (!isset($taken[$i]) && $m && ($r = $first($paid, function ($r) use ($m) { return (int) $r['item_id'] === (int) $m['item_id']; }))) {
                $taken[$i] = [$r, $l['how'] . ', another barcode of the item'];
            }
        }
        foreach ($lines as $i => $l) {
            // An item found by its barcode or the vendor's code is that item;
            // if the grid does not have it, it is not on this GRN.
            if (isset($taken[$i]) || ($l['match'] && strpos((string) $l['how'], 'name') !== 0)) {
                continue;
            }
            list($row, $pct) = self::byName($l, $paid, $used);
            if ($row !== null) {
                $used[(int) $row['id']] = true;
                $taken[$i] = [$row, 'name ' . (int) round($pct) . '%, among this GRN\'s lines'];
            }
        }

        $out = ['fills' => [], 'missing' => [], 'unmatched' => [], 'untouched' => []];
        foreach ($lines as $i => $l) {
            $x = $l['line'];
            $m = $l['match'];
            $entry = [
                'n' => (int) $l['n'],
                'description' => (string) ($x['description'] ?? ''),
                'qty' => self::num($x['qty'] ?? null),
                'free_qty' => self::num($x['free_qty'] ?? null),
                'unit' => (string) ($x['unit'] ?? ''),
                'rate' => self::num($x['rate'] ?? null),
                'mrp' => self::num($x['mrp'] ?? null),
                'discount' => self::num($x['discount_percent'] ?? null),
                'gst' => self::num($x['gst_percent'] ?? null),
                'amount' => self::num($x['amount'] ?? null),
            ];
            if (isset($taken[$i])) {
                list($row, $how) = $taken[$i];
                $sameItem = $m && (int) $m['item_id'] === (int) $row['item_id'];
                $flags = self::rowFlags($entry, $row);
                if (isset($pairFlag[$i])) {
                    array_unshift($flags, $pairFlag[$i]);
                }
                foreach ($l['flags'] as $f) {
                    // The MRP and GST checks are made against the grid line
                    // above; the rest of BillReader's compare with the item
                    // master and hold only if this is that item. Expiry is
                    // the bill's own.
                    if (strpos($f[1], 'Expire') === 0 || ($sameItem && strpos($f[1], 'MRP ') !== 0 && strpos($f[1], 'GST ') !== 0)) {
                        $flags[] = $f;
                    }
                }
                $entry += ['detail_id' => (int) $row['id'], 'item' => (string) $row['title'], 'barcode' => (string) $row['bar_code'],
                           'how' => $how, 'free_detail_id' => null];
                if ((float) $entry['free_qty'] > 0) {
                    $freeRow = $first($free, function ($r) use ($row) { return (int) $r['item_detail_id'] === (int) $row['item_detail_id']; })
                        ?: $first($free, function ($r) use ($row) { return (int) $r['item_id'] === (int) $row['item_id']; });
                    if ($freeRow) {
                        $entry['free_detail_id'] = (int) $freeRow['id'];
                    } else {
                        $flags[] = ['warning', self::trim($entry['free_qty']) . ' free on the bill: add a line for it with Is free = Yes'];
                    }
                }
                $entry['flags'] = $flags;
                $out['fills'][] = $entry;
            } elseif ($m) {
                $entry += ['item' => (string) $m['title'], 'barcode' => (string) $m['bar_code'], 'how' => (string) $l['how'], 'flags' => $l['flags']];
                $out['missing'][] = $entry;
            } else {
                $entry['alternatives'] = array_map(function ($a) {
                    return ['title' => (string) $a['title'], 'barcode' => (string) $a['barcode'], 'score' => (int) $a['score']];
                }, $l['alternatives']);
                $out['unmatched'][] = $entry;
            }
        }
        foreach ($paid as $id => $r) {
            if (!isset($used[$id])) {
                $out['untouched'][] = ['detail_id' => $id, 'item' => (string) $r['title'], 'barcode' => (string) $r['bar_code'],
                                       'req_qty' => self::num($r['req_qty']), 'approved_qty' => self::num($r['approved_qty'])];
            }
        }
        return $out;
    }

    /** The free grid line whose name is most like the bill line's, if alike enough: [row, %] or [null, 0]. */
    private static function byName(array $l, array $paid, array $used)
    {
        $name = BillReader::normalise((string) ($l['line']['description'] ?? ''));
        $best = null;
        $bestPct = 0.0;
        if ($name !== '') {
            foreach ($paid as $id => $r) {
                if (isset($used[$id])) {
                    continue;
                }
                similar_text($name, BillReader::normalise((string) $r['title']), $pct);
                if ($pct > $bestPct) {
                    $bestPct = $pct;
                    $best = $r;
                }
            }
        }
        return $bestPct >= self::NAME_MATCH ? [$best, $bestPct] : [null, 0.0];
    }

    /**
     * The two lists aligned in order: the set of pairs, none crossing
     * another, with the most in common in total.
     *
     * @param array $lines bill lines, in the bill's order, keyed 0..n-1
     * @param array $rows  the grid's paid lines, in the grid's order, keyed 0..m-1
     * @return array line index => [row, how it was paired, flag or null]
     */
    private static function align(array $lines, array $rows)
    {
        $n = count($lines);
        $m = count($rows);
        if ($n === 0 || $m === 0) {
            return [];
        }
        $pair = [];
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $m; $j++) {
                $pair[$i][$j] = self::pair($lines[$i], $rows[$j]);
            }
        }
        // best[i][j]: the most that lines i.. and rows j.. can have in common.
        // Worked from the end so that, read from the start, ties pair the
        // first lines with the first rows and leave the unpaired at the end.
        $best = array_fill(0, $n + 1, array_fill(0, $m + 1, 0.0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $v = max($best[$i + 1][$j], $best[$i][$j + 1]);
                if ($pair[$i][$j][0] > 0) {
                    $v = max($v, $best[$i + 1][$j + 1] + $pair[$i][$j][0]);
                }
                $best[$i][$j] = $v;
            }
        }
        $out = [];
        $i = 0;
        $j = 0;
        while ($i < $n && $j < $m) {
            if ($pair[$i][$j][0] > 0 && abs($best[$i][$j] - ($best[$i + 1][$j + 1] + $pair[$i][$j][0])) < 1e-9) {
                $out[$i] = [$rows[$j], $pair[$i][$j][1], $pair[$i][$j][2]];
                $i++;
                $j++;
            } elseif (abs($best[$i][$j] - $best[$i + 1][$j]) < 1e-9) {
                $i++;
            } else {
                $j++;
            }
        }
        return $out;
    }

    /**
     * What a bill line and a grid line have in common: [score, how, flag].
     * A score of zero or less means they are not to be paired.
     *
     * The barcode, or the item BillReader found by barcode or vendor code,
     * settles it either way. Otherwise every pair starts with a little for
     * standing at the same place, and earns more for a like name, the same
     * MRP and a rate close to the GRN line's.
     */
    private static function pair(array $l, array $row)
    {
        $x = $l['line'];
        $m = $l['match'];
        $barcode = preg_replace('/\s+/', '', (string) ($x['barcode'] ?? ''));
        if ($barcode !== '' && (string) $row['bar_code'] === $barcode) {
            return [4.0, 'barcode', null];
        }
        $certain = $m && strpos((string) $l['how'], 'name') !== 0;
        if ($m && (int) $m['id'] === (int) $row['item_detail_id']) {
            return [$certain ? 4.0 : 3.0, (string) $l['how'], null];
        }
        if ($m && (int) $m['item_id'] === (int) $row['item_id']) {
            return [$certain ? 3.5 : 2.5, $l['how'] . ', another barcode of the item', null];
        }
        if ($certain) {
            return [-1.0, null, null];
        }
        similar_text(BillReader::normalise((string) ($x['description'] ?? '')), BillReader::normalise((string) $row['title']), $alike);
        $mrp = isset($x['mrp']) && (float) $x['mrp'] > 0 && (float) $row['mrp'] > 0 && abs((float) $x['mrp'] - (float) $row['mrp']) <= 0.5;
        $rate = isset($x['rate']) && (float) $x['rate'] > 0 && (float) $row['price'] > 0
            && abs((float) $x['rate'] - (float) $row['price']) <= 0.1 * (float) $row['price'];
        $score = 0.3 + 1.5 * max(0.0, ($alike - 40) / 60) + ($mrp ? 0.8 : 0.0) + ($rate ? 0.6 : 0.0);
        $how = 'line order' . ($alike >= self::NAME_MATCH ? ', name ' . (int) round($alike) . '%' : '')
            . ($mrp ? ', same MRP' : '') . ($rate ? ', rate close' : '');
        $flag = null;
        if ($alike < self::NAME_MATCH) {
            $flag = ($mrp || $rate)
                ? ['info', 'The name on the bill differs from this item\'s; paired by its place on the bill' . ($mrp ? ', its MRP' : '') . ($rate ? ', its rate' : '')]
                : ['warning', 'Paired only by its place on the bill - check it is this item'];
        }
        return [$score, $how, $flag];
    }

    /** What the bill says that the grid line does not. */
    private static function rowFlags(array $entry, array $row)
    {
        $flags = [];
        if ($entry['qty'] !== null && (float) $row['req_qty'] > 0 && $entry['qty'] > (float) $row['req_qty'] + 0.0005) {
            $flags[] = ['info', 'Bill has ' . self::trim($entry['qty']) . ', ordered ' . self::trim($row['req_qty'])];
        }
        if ($entry['mrp'] !== null && (float) $row['mrp'] > 0 && abs($entry['mrp'] - (float) $row['mrp']) > 0.5) {
            $flags[] = ['warning', 'MRP changes from ' . Insights::money($row['mrp']) . ' to ' . Insights::money($entry['mrp']) . ' - the sale rate follows the MRP'];
        }
        if ($entry['gst'] !== null && abs($entry['gst'] - (float) $row['gst']) > 0.01) {
            $flags[] = ['warning', 'GST ' . self::trim($entry['gst']) . '% on bill, ' . self::trim($row['gst']) . '% on this line - the tax is left as it is'];
        }
        return $flags;
    }

    /**
     * The bill's date as YYYY-MM-DD, or null. Indian bills print the day
     * first; strtotime() would read 05/09/2026 as the 9th of May.
     */
    public static function billDate($s)
    {
        $s = trim($s);
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $s, $m)) {
            list(, $y, $mo, $d) = $m;
        } elseif (preg_match('/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{2}|\d{4})$/', $s, $m)) {
            list(, $d, $mo, $y) = $m;
            $y = strlen($y) === 2 ? '20' . $y : $y;
        } elseif ($s !== '' && preg_match('/[a-z]{3}/i', $s) && ($t = strtotime($s)) !== false) {
            return date('Y-m-d', $t);
        } else {
            return null;
        }
        return checkdate((int) $mo, (int) $d, (int) $y) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : null;
    }

    private static function num($v)
    {
        return ($v === null || $v === '' || !is_numeric($v)) ? null : (float) $v;
    }

    private static function trim($v)
    {
        return rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.');
    }
}
