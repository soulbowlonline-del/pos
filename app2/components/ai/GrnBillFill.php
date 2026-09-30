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
        $inclusive = self::amountsIncludeTax($bill);
        $names = self::vendorNames($grn['vendor_id'] ?? 0);
        $lines = self::withVendorNames($result['lines'], $names, self::itemsByBarcode(array_values($names['items'])));
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
                'amounts_include_gst' => $inclusive,
            ],
            'flags' => $flags,
            'line_count' => count($result['lines']),
        ] + self::assign($lines, $rows, $inclusive);
    }

    /** What this vendor calls things: app2/config/ai-bill-names.php. */
    public static function vendorNames($vendorId)
    {
        static $all = null;
        if ($all === null) {
            $file = dirname(__DIR__, 2) . '/config/ai-bill-names.php';
            $all = is_file($file) ? (array) require $file : [];
        }
        return ($all[(int) $vendorId] ?? []) + ['units' => [], 'items' => [], 'reading' => '', 'pack_size_is_certain' => false];
    }

    /** barcode => the item as BillReader describes one. */
    public static function itemsByBarcode(array $barcodes)
    {
        $out = [];
        $barcodes = array_values(array_unique(array_map('strval', $barcodes)));
        if (!$barcodes) {
            return $out;
        }
        $params = [];
        foreach ($barcodes as $k => $b) {
            $params[':b' . $k] = $b;
        }
        foreach (AiData::rows(
            'SELECT d.id, d.bar_code, d.item_id, COALESCE(NULLIF(d.mrp, 0), i.mrp) mrp, i.hsn_code, i.title, i.purchase_price, i.purchase_price last_price,'
            . ' COALESCE(NULLIF(t.tax_val1 + t.tax_val2, 0), t.tax_val4, 0) gst'
            . ' FROM tbl_item_detail d JOIN tbl_item i ON i.id = d.item_id LEFT JOIN tbl_tax t ON t.id = d.tax_id'
            . ' WHERE d.bar_code IN (' . implode(',', array_keys($params)) . ') ORDER BY d.status DESC, d.id', $params) as $r) {
            $out[(string) $r['bar_code']] = $r;   // an active row, the newest, is the one kept
        }
        return $out;
    }

    /**
     * The bill's lines with the vendor's own words put into DASPOS's: a unit
     * the vendor counts in becomes the line's pack size, and the vendor's
     * name for an item becomes that item, found as surely as by a barcode.
     * No database, so it can be tested on its own.
     *
     * @param array $lines BillReader::review()'s lines
     * @param array $names self::vendorNames()
     * @param array $items self::itemsByBarcode() of the names' barcodes
     */
    public static function withVendorNames(array $lines, array $names, array $items)
    {
        $units = [];
        foreach ($names['units'] as $unit => $pieces) {
            $units[preg_replace('/[^a-z]/', '', strtolower($unit))] = (float) $pieces;
        }
        $called = [];
        foreach ($names['items'] as $name => $barcode) {
            if (isset($items[(string) $barcode])) {
                $called[BillReader::normalise($name)] = $items[(string) $barcode];
            }
        }
        // The longest name first: "Full Cream Milk 500ml" before a plain "Milk 500ml".
        uksort($called, function ($a, $b) { return strlen($b) <=> strlen($a); });
        foreach ($lines as &$l) {
            $unit = preg_replace('/[^a-z]/', '', strtolower((string) ($l['line']['unit'] ?? '')));
            if ($unit !== '' && isset($units[$unit]) && $units[$unit] > 1) {
                $l['line']['pack_size'] = $units[$unit];
                $l['line']['pack_fixed'] = true;
            } elseif (!empty($names['pack_size_is_certain']) && (float) ($l['line']['pack_size'] ?? 0) > 1) {
                // Stated on the line, as the owner says this vendor's bills
                // do - unless the item's own name prints another size, which
                // is as likely a misread figure as a different box: then it
                // is weighed like any other and the line says so.
                $printed = self::printedPack((string) ($l['line']['description'] ?? ''));
                if ($printed === null || abs($printed - (float) $l['line']['pack_size']) < 0.0005) {
                    $l['line']['pack_fixed'] = true;
                } else {
                    $l['flags'][] = ['warning', 'The box size written on the bill was read as ' . self::trim($l['line']['pack_size'])
                        . ' and the item\'s name prints ' . self::trim($printed) . ' - check the quantity'];
                }
            }
            $said = ' ' . BillReader::normalise((string) ($l['line']['description'] ?? '')) . ' ';
            foreach ($called as $name => $item) {
                if ($name !== '' && strpos($said, ' ' . $name . ' ') !== false) {
                    $l['match'] = $item;
                    $l['how'] = "this vendor's name for it";
                    $l['alternatives'] = [];
                    $l['flags'] = array_values(array_filter($l['flags'], function ($f) { return strpos($f[1], 'Expire') === 0; }));
                    break;
                }
            }
        }
        unset($l);
        return $lines;
    }

    /**
     * Do the bill's line amounts include GST? True when they add up to the
     * bill total, false when they add up to it only with the tax added, null
     * when the bill does not say (no tax, no total, or neither adds up).
     */
    public static function amountsIncludeTax(array $bill)
    {
        $sum = 0.0;
        foreach ($bill['lines'] ?? [] as $line) {
            $sum += (float) ($line['amount'] ?? 0);
        }
        $total = (float) ($bill['bill_total'] ?? 0);
        $tax = (float) ($bill['tax_total'] ?? 0);
        $slack = max(1.0, 0.01 * $total);
        if ($total <= 0 || $sum <= 0 || $tax <= $slack) {
            return null;
        }
        if (abs($sum - $total) <= $slack) {
            return true;
        }
        return abs($sum + $tax - $total) <= $slack ? false : null;
    }

    /**
     * Pairs bill lines with grid lines and works out what each says in the
     * GRN's units. No database, so it can be tested on its own.
     *
     * Line by line, in order (owner, 30 Sep 2026): the goods are received on
     * the Android app against the bill, which numbers the GRN's lines in the
     * bill's order, while the names on a vendor's bill often differ from the
     * item master's. So the bill and the numbered lines are aligned as
     * sequences - a bill line pairs with the grid line at the same place -
     * and what each pair has in common (see common()) decides where the two
     * lists fall out of step: a bill line the GRN does not have, or a GRN
     * line the bill does not have, is stepped over instead of shifting every
     * line after it.
     *
     * The rest are paired by what they are, out of order: the barcode or the
     * item BillReader found, the same item under another barcode, then every
     * remaining bill line against every free grid line, the pairs with the
     * most in common first. That is all there is for lines the app did not
     * number and for a GRN not received on the app. A bill line with only
     * its place to go by keeps that place, flagged for checking, if nothing
     * better claims it. A grid line is used once: a second bill line for the
     * same item (a second batch) is listed as not on the GRN.
     *
     * @param array     $lines     BillReader::review()'s lines
     * @param array     $rows      self::rows(), in the grid's order
     * @param bool|null $inclusive self::amountsIncludeTax()
     * @return array fills, missing, unmatched, untouched
     */
    public static function assign(array $lines, array $rows, $inclusive = null)
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
        $ctx = self::context($paid, $inclusive);
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
        $byPlace = [];
        $keys = array_keys($lines);
        foreach (self::align(array_values($lines), $sequence, $ctx) as $i => $c) {
            if ($c['weak']) {
                // Only its place to go by: held back until the lines that
                // have something in common with a grid line have chosen.
                $byPlace[$keys[$i]] = $c;
                continue;
            }
            $taken[$keys[$i]] = [$c['row'], $c['how']];
            $used[(int) $c['row']['id']] = true;
            if ($c['flag'] !== null) {
                $pairFlag[$keys[$i]] = $c['flag'];
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
            if (self::certain($l) && ($r = $first($paid, function ($r) use ($m) { return (int) $r['item_detail_id'] === (int) $m['id']; }))) {
                $taken[$i] = [$r, (string) $l['how']];
            }
        }
        foreach ($lines as $i => $l) {
            $m = $l['match'];
            if (!isset($taken[$i]) && self::certain($l) && ($r = $first($paid, function ($r) use ($m) { return (int) $r['item_id'] === (int) $m['item_id']; }))) {
                $taken[$i] = [$r, $l['how'] . ', another barcode of the item'];
            }
        }

        // Every bill line still unpaired against every grid line still free;
        // the pairs with the most in common choose first.
        $candidates = [];
        foreach ($lines as $i => $l) {
            // An item found by its barcode or the vendor's code is that item;
            // if the grid does not have it, it is not on this GRN.
            if (isset($taken[$i]) || self::certain($l)) {
                continue;
            }
            foreach ($paid as $id => $r) {
                if (!isset($used[$id])) {
                    $c = self::common($l, $r, $ctx);
                    if ($c['enough']) {
                        $candidates[] = [$c['score'], $i, $id, $c];
                    }
                }
            }
        }
        usort($candidates, function ($a, $b) { return $b[0] <=> $a[0] ?: $a[1] <=> $b[1]; });
        foreach ($candidates as list(, $i, $id, $c)) {
            if (!isset($taken[$i]) && !isset($used[$id])) {
                $taken[$i] = [$paid[$id], $c['how']];
                $used[$id] = true;
                if ($c['flag'] !== null) {
                    $pairFlag[$i] = $c['flag'];
                }
            }
        }
        foreach ($byPlace as $i => $c) {
            $id = (int) $c['row']['id'];
            if (!isset($taken[$i]) && !isset($used[$id])) {
                $taken[$i] = [$c['row'], $c['how']];
                $used[$id] = true;
                $pairFlag[$i] = $c['flag'];
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
                $rd = self::reading($x, $row, $inclusive);
                $entry += self::fillValues($rd);
                $flags = self::rowFlags($entry, $rd, $row);
                if (isset($pairFlag[$i])) {
                    array_unshift($flags, $pairFlag[$i]);
                }
                foreach ($l['flags'] as $f) {
                    // MRP, GST and rate are checked against the grid line
                    // above, in the GRN's units. The HSN check compares with
                    // the item master and holds only if this is that item.
                    // Expiry is the bill's own.
                    if (strpos($f[1], 'Expire') === 0 || strpos($f[1], 'The box size written') === 0 || ($sameItem && strpos($f[1], 'HSN ') === 0)) {
                        $flags[] = $f;
                    }
                }
                $entry += ['detail_id' => (int) $row['id'], 'item' => (string) $row['title'], 'barcode' => (string) $row['bar_code'],
                           'how' => $how, 'free_detail_id' => null];
                if ((float) $entry['fill_free_qty'] > 0) {
                    $freeRow = $first($free, function ($r) use ($row) { return (int) $r['item_detail_id'] === (int) $row['item_detail_id']; })
                        ?: $first($free, function ($r) use ($row) { return (int) $r['item_id'] === (int) $row['item_id']; });
                    if ($freeRow) {
                        $entry['free_detail_id'] = (int) $freeRow['id'];
                    } else {
                        $flags[] = ['warning', self::trim($entry['fill_free_qty']) . ' free on the bill: add a line for it with Is free = Yes'];
                    }
                }
                $entry['flags'] = $flags;
                $out['fills'][] = $entry;
            } elseif ($m) {
                // For the Add Item form: the item master stands in for the grid line.
                $rd = self::reading($x, ['price' => $m['last_price'] ?? 0, 'mrp' => $m['mrp'] ?? 0, 'gst' => $m['gst'] ?? 0, 'approved_qty' => 0], $inclusive);
                $entry += self::fillValues($rd);
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

    // ------------------------------------------------ what a line says, in the GRN's units

    /**
     * A bill line in the GRN's units: pieces, and the rate of one piece
     * before GST.
     *
     * The grid counts pieces at a rate before tax. A vendor's bill often
     * counts cases ("CLUB SODA (24*500ML)  4  370.00") and often prints its
     * rates and amounts with the GST in them. Typed in as printed, 4 at 370
     * would replace 96 at 14.68 (the first real bill, 30 Sep 2026). So the
     * line is read each way it could be meant - per piece or per case of the
     * pack size the bill gives (or that the quantity received on the app, or
     * a price a whole number of times the grid's, implies), with or without
     * GST when the bill's totals do not settle it -
     * and the reading kept is the one whose piece rate is nearest the grid
     * line's, among those that do not put the cost above the MRP. The rate
     * comes from the line's amount, which carries any scheme or discount in
     * rupees; a discount % the bill prints is taken back out of it, because
     * the screen takes it off again.
     *
     * @param array     $x         the bill line, as read
     * @param array     $row       the grid line (price, mrp, gst, approved_qty)
     * @param bool|null $inclusive self::amountsIncludeTax()
     */
    public static function reading(array $x, array $row, $inclusive)
    {
        $qty = self::num($x['qty'] ?? null);
        $rate = self::num($x['rate'] ?? null);
        $amount = self::num($x['amount'] ?? null);
        $disc = (float) ($x['discount_percent'] ?? 0);
        $gst = self::num($x['gst_percent'] ?? null);
        $gst = $gst === null ? (float) ($row['gst'] ?? 0) : $gst;
        $rowPrice = (float) ($row['price'] ?? 0);
        $rowMrp = (float) ($row['mrp'] ?? 0);
        $received = (float) ($row['approved_qty'] ?? 0);

        // The pack sizes the line could be counted in: pieces => whether the
        // only reason to think so is the quantity received on the app.
        $packs = ['1' => false];
        if ((float) ($x['pack_size'] ?? 0) > 1) {
            $packs[(string) (float) $x['pack_size']] = false;
        }
        if (($printed = self::printedPack((string) ($x['description'] ?? ''))) !== null) {
            $packs[(string) $printed] = false;
        }
        // A price that is a whole number of times the grid line's is a case
        // of that many: 762.00 a crate against 31.75 a piece is 24.
        $each = ($amount !== null && $qty > 0) ? $amount / $qty : $rate;
        if ($each !== null && $rowPrice > 0) {
            foreach ([$each, $each / (1 + $gst / 100)] as $price) {
                $times = $price / $rowPrice;
                // Whole to within what rounding both prices to the paisa can
                // do, and no more: any large enough number is near a whole one.
                if ($times >= 1.5 && abs($times - round($times)) <= 0.005 + (0.01 + 0.001 * $times) / $rowPrice) {
                    $packs[(string) (float) round($times)] = false;
                }
            }
        }
        if ($qty > 0 && $received > 0 && $received / $qty >= 2 && abs($received / $qty - round($received / $qty)) < 1e-6) {
            $packs += [(string) (float) round($received / $qty) => true];
        }
        if (!empty($x['pack_fixed'])) {
            // The vendor's own unit, as the owner gave it: not up for choosing.
            $packs = [(string) (float) $x['pack_size'] => false];
        }
        $best = null;
        foreach ($packs as $pack => $fromReceived) {
            $pack = (float) $pack;
            foreach ($inclusive === null ? [false, true] : [(bool) $inclusive] as $withTax) {
                $pieces = $qty === null ? null : $qty * $pack;
                $unit = null;
                if ($amount !== null && $pieces > 0) {
                    $unit = ($withTax ? $amount / (1 + $gst / 100) : $amount) / $pieces;
                    if ($disc > 0 && $disc < 100) {
                        $unit /= 1 - $disc / 100;
                    }
                } elseif ($rate !== null) {
                    $unit = ($withTax ? $rate / (1 + $gst / 100) : $rate) / $pack;
                }
                $off = ($unit !== null && $rowPrice > 0) ? abs($unit - $rowPrice) / $rowPrice : null;
                if ($fromReceived && ($off === null || $off > 0.10)) {
                    // Dividing what was received by what was billed always
                    // gives some number; it is a pack size only if the rate
                    // then agrees.
                    continue;
                }
                $aboveMrp = $unit !== null && $rowMrp > 0 && $unit * (1 + $gst / 100) > 1.1 * $rowMrp;
                $sameQty = $pieces !== null && $received > 0 && abs($pieces - $received) < 0.0005;
                // Least is best: not above the MRP; nearest the grid's rate;
                // the quantity received; and, all else equal, as printed.
                $rank = ($aboveMrp ? 10 : 0) + ($off === null ? 1.0 : min($off, 1.0)) - ($sameQty ? 0.05 : 0) + ($pack > 1 ? 0.002 : 0) + ($withTax ? 0.001 : 0);
                if ($best === null || $rank < $best['rank']) {
                    $best = ['rank' => $rank, 'pack' => $pack, 'with_tax' => $withTax, 'pieces' => $pieces, 'unit' => $unit, 'off' => $off,
                             'above_mrp' => $aboveMrp, 'same_qty' => $sameQty, 'qty_agrees' => $sameQty && !$fromReceived];
                }
            }
        }
        $best['gst'] = $gst;
        $best['discount'] = self::num($x['discount_percent'] ?? null);
        $best['free'] = self::num($x['free_qty'] ?? null) === null ? null : (float) $x['free_qty'] * $best['pack'];
        $mrp = self::num($x['mrp'] ?? null);
        // An MRP below the cost with tax, or several times the grid's, is a
        // case price or a misreading; not one to type in.
        $best['mrp'] = ($mrp > 0 && ($best['unit'] === null || $mrp >= 0.95 * $best['unit'] * (1 + $gst / 100)) && ($rowMrp <= 0 || $mrp <= 3 * $rowMrp)) ? $mrp : null;
        $best['mrp_skipped'] = $mrp > 0 && $best['mrp'] === null;

        $basis = [];
        if ($best['pack'] > 1 && $qty !== null) {
            $basis[] = self::trim($qty) . ' x ' . self::trim($best['pack']) . ' = ' . self::trim($best['pieces']) . ' pieces';
        }
        if ($best['unit'] !== null && ($best['pack'] > 1 || $best['with_tax'] || ($rate !== null && abs($best['unit'] - $rate) > 0.005))) {
            $basis[] = ($amount !== null && $best['pieces'] > 0 ? Insights::money($amount) : Insights::money($rate) . ' a pack')
                . ($best['with_tax'] ? ' with ' . self::trim($gst) . '% GST in it' : '')
                . ($disc > 0 && $disc < 100 && $amount !== null ? ', after ' . self::trim($disc) . '% discount' : '')
                . ' = ' . Insights::money($best['unit']) . ' a piece before GST' . ($disc > 0 && $disc < 100 && $amount !== null ? ' and discount' : '');
        }
        $best['basis'] = implode('; ', $basis);
        return $best;
    }

    /** The pack size printed in an item's name: "(24*500ML)", "24 X 500ML", and "(18*" where the print ran out of room. */
    private static function printedPack($description)
    {
        return preg_match('/(\d{1,3})\s*(?:[*×]|[xX]\s*\d)/u', $description, $m) && (int) $m[1] > 1 ? (float) $m[1] : null;
    }

    /** What grn-bill.js types in. */
    private static function fillValues(array $rd)
    {
        if ($rd['above_mrp']) {
            // A cost above the MRP means the line's unit was not understood
            // (a case taken for a piece). Nothing of it is typed in.
            return ['fill_qty' => null, 'fill_rate' => null, 'fill_mrp' => null, 'fill_discount' => null, 'fill_free_qty' => null, 'basis' => ''];
        }
        return [
            'fill_qty' => $rd['pieces'] === null ? null : round($rd['pieces'], 3),
            'fill_rate' => $rd['unit'] === null ? null : round($rd['unit'], 2),
            'fill_mrp' => $rd['mrp'],
            'fill_discount' => $rd['discount'],
            'fill_free_qty' => $rd['free'] === null ? null : round($rd['free'], 3),
            'basis' => $rd['basis'],
        ];
    }

    /** What the bill says that the grid line does not. */
    private static function rowFlags(array $entry, array $rd, array $row)
    {
        $flags = [];
        if ($rd['above_mrp']) {
            $flags[] = ['danger', 'Not filled in: as printed it is ' . Insights::money($rd['unit'] * (1 + $rd['gst'] / 100)) . ' a piece with GST, above the MRP of '
                . Insights::money($row['mrp']) . '. The bill probably counts cases; enter this line by hand'];
        } elseif ($rd['unit'] !== null && (float) $row['price'] > 0 && $rd['unit'] > 1.02 * (float) $row['price']) {
            $flags[] = ['warning', sprintf('Rate %s is %.1f%% above this line\'s rate (%s)', Insights::money($rd['unit']),
                100 * ($rd['unit'] / (float) $row['price'] - 1), Insights::money($row['price']))];
        }
        if ($rd['above_mrp']) {
            return $flags;
        }
        if ($rd['unit'] !== null && (float) $row['price'] > 0 && $rd['unit'] < 0.9 * (float) $row['price']) {
            $flags[] = ['warning', sprintf('Rate %s is %.1f%% below this line\'s rate (%s) - check the quantity unit', Insights::money($rd['unit']),
                100 * (1 - $rd['unit'] / (float) $row['price']), Insights::money($row['price']))];
        }
        if ($rd['pieces'] !== null && (float) $row['approved_qty'] > 0 && !$rd['same_qty']) {
            $flags[] = ['warning', 'The bill has ' . self::trim($rd['pieces']) . '; ' . self::trim($row['approved_qty']) . ' was received on this line'];
        }
        if ($rd['pieces'] !== null && (float) $row['req_qty'] > 0 && $rd['pieces'] > (float) $row['req_qty'] + 0.0005) {
            $flags[] = ['info', 'Bill has ' . self::trim($rd['pieces']) . ', ordered ' . self::trim($row['req_qty'])];
        }
        if ($rd['mrp'] !== null && (float) $row['mrp'] > 0 && abs($rd['mrp'] - (float) $row['mrp']) > 0.5) {
            $flags[] = ['warning', 'MRP changes from ' . Insights::money($row['mrp']) . ' to ' . Insights::money($rd['mrp']) . ' - the sale rate follows the MRP'];
        }
        if ($rd['mrp_skipped']) {
            $flags[] = ['info', 'The MRP on the bill (' . Insights::money($entry['mrp']) . ') does not fit a piece of this item and was not filled in'];
        }
        if ($entry['gst'] !== null && abs($entry['gst'] - (float) $row['gst']) > 0.01) {
            $flags[] = ['warning', 'GST ' . self::trim($entry['gst']) . '% on bill, ' . self::trim($row['gst']) . '% on this line - the tax is left as it is'];
        }
        return $flags;
    }

    // ---------------------------------------------------- what a pair has in common

    /** What is needed to compare names: each grid line's words, and how many lines carry each word. */
    private static function context(array $paid, $inclusive)
    {
        $words = [];
        $lines = [];
        foreach ($paid as $id => $r) {
            $words[$id] = self::words((string) $r['title']);
            foreach (array_keys($words[$id]) as $w) {
                $lines[$w] = ($lines[$w] ?? 0) + 1;
            }
        }
        return ['words' => $words, 'lines' => $lines, 'count' => max(1, count($paid)), 'inclusive' => $inclusive];
    }

    /**
     * The words of a name that tell items apart: word => true for a size
     * ("500ml"), false otherwise. Sizes are put in one unit (1LTR, 1000ML
     * and 1L are the same word); bare numbers and filler are dropped.
     */
    private static function words($name)
    {
        $out = [];
        foreach (explode(' ', BillReader::normalise($name)) as $w) {
            if (preg_match('/^(\d+)(ml|m|l|lt|ltr|litre|g|gm|gms|gram|grams|kg|kgs)$/', $w, $m)) {
                $n = (int) $m[1];
                $litres = in_array($m[2], ['l', 'lt', 'ltr', 'litre'], true);
                $kilos = in_array($m[2], ['kg', 'kgs'], true);
                $out[($litres || $kilos ? $n * 1000 : $n) . ($litres || in_array($m[2], ['ml', 'm'], true) ? 'ml' : 'g')] = true;
            } elseif (mb_strlen($w) >= 2 && !ctype_digit($w) && !in_array($w, ['pcs', 'pkt', 'nos', 'mrp', 'the', 'and', 'pc', 'rs', 'no', 'of', 'ml', 'gm', 'kg', 'lt'], true)) {
                $out[$w] = false;
            }
        }
        return $out;
    }

    /** The same word, allowing for a printing or spelling slip: CRENBERRY / CRANBERRY, FLAVOUR / FLAVOURED. */
    private static function sameWord($a, $b)
    {
        if ($a === $b) {
            return true;
        }
        $short = min(strlen($a), strlen($b));
        // One the start of the other, and most of it: FLAVOUR / FLAVOURED, not WATER / WATERMELON.
        if ($short >= 4 && $short >= 0.7 * max(strlen($a), strlen($b)) && (strpos($a, $b) === 0 || strpos($b, $a) === 0)) {
            return true;
        }
        return $short >= 5 && levenshtein($a, $b) <= ($short >= 8 ? 2 : 1);
    }

    /**
     * How alike a bill's name for an item and a grid line's are, 0 to 100.
     *
     * Two measures, the higher counts. Letter by letter (similar_text), which
     * is what BillReader uses. And word by word, in any order, where a word
     * that few lines of this GRN carry counts for more than one they all
     * carry: on a GRN of CATCH FLAVOURED WATER in four flavours, the flavour
     * is what tells the lines apart, and letter by letter "CATCH LEMON
     * FLAVOURED WATER(18*60)" is as close to PEACH as to LEMON LIME.
     */
    private static function alike($billName, array $row, array $ctx)
    {
        similar_text(BillReader::normalise($billName), BillReader::normalise((string) $row['title']), $letters);
        $bill = self::words($billName);
        $grid = $ctx['words'][(int) $row['id']] ?? self::words((string) $row['title']);
        if (!$bill || !$grid) {
            return $letters;
        }
        $weight = function ($word, $isSize) use ($ctx) {
            $carried = 0;
            foreach ($ctx['lines'] as $w => $n) {
                if ($isSize ? $w === $word : self::sameWord($w, $word)) {
                    $carried = max($carried, $n);
                }
            }
            return log(1 + $ctx['count'] / max(1, $carried)) * ($isSize ? 0.5 : 1.0);
        };
        $found = function (array $from, array $in) use ($weight) {
            $all = 0.0;
            $hit = 0.0;
            foreach ($from as $word => $isSize) {
                $w = $weight($word, $isSize);
                $all += $w;
                foreach ($in as $other => $otherIsSize) {
                    if ($isSize === $otherIsSize && ($isSize ? $word === $other : self::sameWord($word, $other))) {
                        $hit += $w;
                        break;
                    }
                }
            }
            return $all > 0 ? $hit / $all : 0.0;
        };
        return max($letters, 100 * (0.7 * $found($bill, $grid) + 0.3 * $found($grid, $bill)));
    }

    /** Did BillReader find the line's item by its barcode or the vendor's code, rather than by name? */
    private static function certain(array $l)
    {
        return $l['match'] && strpos((string) $l['how'], 'name') !== 0;
    }

    /**
     * What a bill line and a grid line have in common.
     *
     * The barcode, or the item BillReader found by barcode or vendor code,
     * settles it either way. Otherwise: a like name, the same MRP, the same
     * rate once the line is read in the GRN's units, and the quantity the
     * app received.
     *
     * @return array score (zero or less: never pair), how, flag,
     *               enough (to pair them out of order), certain
     */
    private static function common(array $l, array $row, array $ctx)
    {
        $x = $l['line'];
        $m = $l['match'];
        $hit = function ($score, $how) { return ['score' => $score, 'how' => $how, 'flag' => null, 'enough' => true, 'evidence' => true, 'settled' => true]; };
        $barcode = preg_replace('/\s+/', '', (string) ($x['barcode'] ?? ''));
        if ($barcode !== '' && (string) $row['bar_code'] === $barcode) {
            return $hit(6.0, 'barcode');
        }
        // Only a barcode or vendor-code match in the item master says which
        // item it is. A name match there is a guess among every item the
        // store has (the first real bill's LEMON was matched to PEACH, 73%);
        // the name is compared with this GRN's own lines below instead.
        if (self::certain($l)) {
            if ((int) $m['id'] === (int) $row['item_detail_id']) {
                return $hit(6.0, (string) $l['how']);
            }
            if ((int) $m['item_id'] === (int) $row['item_id']) {
                return $hit(5.0, $l['how'] . ', another barcode of the item');
            }
            return ['score' => -1.0, 'how' => null, 'flag' => null, 'enough' => false, 'evidence' => false];
        }
        $alike = self::alike((string) ($x['description'] ?? ''), $row, $ctx);
        $rd = self::reading($x, $row, $ctx['inclusive']);
        $sameRate = $rd['off'] !== null && $rd['off'] <= 0.02 && !$rd['above_mrp'];
        $nearRate = !$sameRate && $rd['off'] !== null && $rd['off'] <= 0.10 && !$rd['above_mrp'];
        $sameQty = $rd['qty_agrees'];   // not when the pack size was itself worked out from that quantity
        $mrp = isset($x['mrp']) && (float) $x['mrp'] > 0 && (float) $row['mrp'] > 0 && abs((float) $x['mrp'] - (float) $row['mrp']) <= 0.5;
        $parts = [];
        if ($alike >= 50) {
            $parts[] = 'name ' . (int) round($alike) . '%';
        }
        if ($mrp) {
            $parts[] = 'same MRP';
        }
        if ($sameRate || $nearRate) {
            $parts[] = $sameRate ? 'same rate' : 'rate close';
        }
        if ($sameQty) {
            $parts[] = 'the quantity received';
        }
        $numbers = $mrp || $sameRate || $nearRate || $sameQty;
        $flag = null;
        if ($alike < self::NAME_MATCH && $numbers) {
            $flag = ['info', 'The name on the bill differs from this item\'s; paired by ' . implode(', ', array_filter($parts, function ($p) { return strpos($p, 'name') !== 0; }))];
        }
        return [
            'score' => 1.5 * max(0.0, ($alike - 40) / 60) + ($mrp ? 0.8 : 0.0) + ($sameRate ? 1.0 : ($nearRate ? 0.5 : 0.0)) + ($sameQty ? 0.5 : 0.0),
            'how' => implode(', ', $parts),
            'flag' => $flag,
            // Numbers alone are not enough out of order: among twenty free
            // lines some rate or quantity will agree by chance.
            'enough' => $alike >= self::NAME_MATCH || ($alike >= 50 && $numbers),
            'evidence' => $alike >= self::NAME_MATCH || $numbers,
        ];
    }

    /**
     * The bill's lines and the numbered grid lines aligned in order: the set
     * of pairs, none crossing another, with the most in common in total.
     * Standing at the same place counts for a little, so lines with nothing
     * else in common still pair in order.
     *
     * @param array $lines bill lines, in the bill's order, keyed 0..n-1
     * @param array $rows  the numbered grid lines, in the order of receiving, keyed 0..m-1
     * @return array line index => [row, how, flag, weak: only its place to go by]
     */
    private static function align(array $lines, array $rows, array $ctx)
    {
        $n = count($lines);
        $m = count($rows);
        if ($n === 0 || $m === 0) {
            return [];
        }
        $pair = [];
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $m; $j++) {
                $c = self::common($lines[$i], $rows[$j], $ctx);
                $pair[$i][$j] = $c + ['total' => $c['score'] < 0 ? -1.0 : $c['score'] + 0.3];
            }
        }
        // best[i][j]: the most that lines i.. and rows j.. can have in common.
        // Worked from the end so that, read from the start, ties pair the
        // first lines with the first rows and leave the unpaired at the end.
        $best = array_fill(0, $n + 1, array_fill(0, $m + 1, 0.0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $v = max($best[$i + 1][$j], $best[$i][$j + 1]);
                if ($pair[$i][$j]['total'] > 0) {
                    $v = max($v, $best[$i + 1][$j + 1] + $pair[$i][$j]['total']);
                }
                $best[$i][$j] = $v;
            }
        }
        $out = [];
        $i = 0;
        $j = 0;
        while ($i < $n && $j < $m) {
            $c = $pair[$i][$j];
            if ($c['total'] > 0 && abs($best[$i][$j] - ($best[$i + 1][$j + 1] + $c['total'])) < 1e-9) {
                $weak = !$c['evidence'];
                $out[$i] = ['row' => $rows[$j], 'weak' => $weak,
                    'how' => !empty($c['settled']) ? $c['how'] : 'line order' . ($c['how'] !== '' && $c['how'] !== null ? ', ' . $c['how'] : ''),
                    'flag' => $weak ? ['warning', 'Paired only by its place on the bill - check it is this item'] : $c['flag']];
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
