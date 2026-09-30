<?php
namespace app\components\ai;

/**
 * Turns a vendor's bill, as read by BillReader, into values for the grid of
 * the GRN screen (purchaseBillDetail/index).
 *
 * The grid already holds the lines of the GRN that is open, usually from its
 * purchase order. This class decides which bill line belongs to which grid
 * line and what a storekeeper would type there: received qty, rate, MRP and
 * discount %. Bill lines with no grid line, and grid lines the bill does not
 * mention, are listed and left alone.
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

    /** The grid's lines, in the grid's order. */
    public static function rows($poid)
    {
        return AiData::rows(
            'SELECT d.id, d.item_detail_id, d.item_id, d.is_free, d.req_qty, d.approved_qty, d.price, d.mrp, d.discount,'
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
     * In three passes, surest first, so a loose match never takes a grid line
     * that a later bill line matches exactly: the barcode printed on the bill
     * or the item BillReader found in the item master; then the same item
     * under another of its barcodes; then the name, among the grid lines still
     * free. A grid line is used once: a second bill line for the same item (a
     * second batch) is listed as not on the GRN.
     *
     * @param array $lines BillReader::review()'s lines
     * @param array $rows  self::rows()
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

        foreach ($lines as $i => $l) {
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
            $name = BillReader::normalise((string) ($l['line']['description'] ?? ''));
            if ($name === '') {
                continue;
            }
            $best = null;
            $bestPct = 0.0;
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
            if ($best !== null && $bestPct >= self::NAME_MATCH) {
                $used[(int) $best['id']] = true;
                $taken[$i] = [$best, 'name ' . (int) round($bestPct) . '%, among this GRN\'s lines'];
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
