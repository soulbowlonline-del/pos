<?php
namespace app\components\ai;

/**
 * Gross profit and stock cover, for "Ask DASPOS".
 *
 * DASPOS has no profit report of its own (the dashboard's "Total profit" is a
 * fixed 0), so this is a new figure and changes no existing one. The owner
 * defined it on 30 Sep 2026, after Ask DASPOS could not answer questions
 * about profit and what to stock: "the cost is the purchase price, you also
 * have the GST so please calculate the margins and profit".
 *
 *   sales    bill lines less refund lines, with the GST taken out
 *            (total_amt - tax_amount)
 *   cost     units sold x the item's purchase cost per unit, GST excluded
 *   profit   sales - cost. It is gross profit: rent, salaries, electricity
 *            and the like are not recorded in DASPOS.
 *
 * The purchase cost per unit of a barcode in a month of sale is the first of
 * these that exists:
 *   1. the average over its approved GRN lines of that month and the
 *      COST_MONTHS_BACK months before it: (qty x rate - discounts) / qty
 *      received, where a free line adds its quantity and no cost
 *   2. its latest approved GRN line from before those months
 *   3. the item master's purchase price
 * A barcode with none of the three has no cost: its sales are reported
 * separately and left out of profit and margin rather than counted as all
 * profit. On August 2026, 99.2% of sales by value are costed by rule 1.
 *
 * The cost belongs to the calendar month, not to the period asked about, so
 * August's profit is the same figure whether the question was about August,
 * about April to August or about the year.
 *
 * Read-only, like everything else AskTools calls.
 */
class AiProfit
{
    public const COST_MONTHS_BACK = 6;

    /** "Stock more": an item that earns and has fewer days of stock than this at the period's rate of sale. */
    public const LOW_COVER_DAYS = 14;

    /** "Stock less": an item holding more days of stock than this at the period's rate of sale. */
    public const HIGH_COVER_DAYS = 90;

    public const BASIS = 'Gross profit = sales excluding GST, less refunds, minus the purchase cost (excluding GST) of the units sold. '
        . 'Cost per unit is the average net rate of the item\'s approved GRNs in the month of sale and the six months before it (free quantities lower it), '
        . 'else its last GRN before those months, else the item master\'s purchase price. Operating expenses are not in DASPOS, so this is not net profit.';

    // ------------------------------------------------------------ the tools

    /** Sales, cost and gross profit for a period: in total, and by month or item category. */
    public static function summary($from, $to, $outletId, $groupBy)
    {
        if (!in_array($groupBy, ['none', 'month', 'category'], true)) {
            throw new \InvalidArgumentException('Bad group_by.');
        }
        $sold = self::sold($from, $to, $outletId);
        $costs = self::costs($sold);
        $category = $groupBy === 'category' ? self::categories() : [];

        $groups = [];
        $total = self::blank();
        foreach ($sold as $s) {
            $key = $groupBy === 'month' ? $s['m'] : ($groupBy === 'category' ? ($category[$s['item_id']] ?? 'No category') : 'all');
            if (!isset($groups[$key])) {
                $groups[$key] = self::blank();
            }
            $cost = $costs[$s['d'] . '|' . $s['m']] ?? null;
            self::add($groups[$key], $s, $cost);
            self::add($total, $s, $cost);
        }
        $rows = [];
        foreach ($groups as $key => $g) {
            $rows[] = ['group' => $key] + self::finish($g);
        }
        if ($groupBy === 'month') {
            usort($rows, function ($a, $b) { return strcmp($a['group'], $b['group']); });
        } else {
            usort($rows, function ($a, $b) { return $b['gross_profit'] <=> $a['gross_profit']; });
        }
        $out = ['period' => [$from, $to], 'basis' => self::BASIS, 'total' => self::finish($total)];
        if ($groupBy !== 'none') {
            $out['group_by'] = $groupBy;
            $out['rows'] = $rows;
        }
        return $out;
    }

    /**
     * Items ranked by what they earn, or by how their stock compares with
     * their rate of sale. Per product: a product's barcodes are added up.
     */
    public static function items($from, $to, $outletId, $itemQuery, $rankBy, $limit)
    {
        $ranks = ['profit_high', 'profit_low', 'margin_high', 'margin_low', 'stock_more', 'stock_less'];
        if (!in_array($rankBy, $ranks, true)) {
            throw new \InvalidArgumentException('Bad rank_by.');
        }
        $limit = max(1, min(50, (int) $limit));
        $days = max(1, (int) round((strtotime($to) - strtotime($from)) / 86400) + 1);
        $sold = self::sold($from, $to, $outletId);
        $note = [];
        if ($itemQuery !== null && trim($itemQuery) !== '') {
            $match = AskTools::matchItems((string) $itemQuery, 300);
            if (!$match) {
                return ['period' => [$from, $to], 'rows' => [], 'note' => 'No item matches "' . $itemQuery . '". Try fewer or different words, or find_items.'];
            }
            $sold = array_values(array_filter($sold, function ($s) use ($match) { return isset($match[$s['d']]); }));
        }
        $costs = self::costs($sold);

        $items = [];
        $uncosted = ['items' => [], 'sales' => 0.0];
        foreach ($sold as $s) {
            $cost = $costs[$s['d'] . '|' . $s['m']] ?? null;
            if ($cost === null) {
                $uncosted['items'][$s['item_id']] = true;
                $uncosted['sales'] += $s['sales'];
                continue;
            }
            $id = $s['item_id'];
            if (!isset($items[$id])) {
                $items[$id] = ['item_id' => $id, 'qty' => 0.0, 'sales' => 0.0, 'cost' => 0.0];
            }
            $items[$id]['qty'] += $s['qty'];
            $items[$id]['sales'] += $s['sales'];
            $items[$id]['cost'] += $s['qty'] * $cost;
        }
        $stock = self::stock($outletId);
        foreach ($items as &$it) {
            $it['profit'] = $it['sales'] - $it['cost'];
            $it['margin'] = $it['sales'] > 0 ? 100 * $it['profit'] / $it['sales'] : null;
            $it['unit_cost'] = $it['qty'] > 0 ? $it['cost'] / $it['qty'] : null;
            $it['stock'] = $stock[$it['item_id']] ?? 0.0;
            $perDay = $it['qty'] / $days;
            $it['cover'] = $perDay > 0 ? max(0.0, $it['stock']) / $perDay : null;
            $it['excess'] = ($perDay > 0 && $it['unit_cost'] !== null)
                ? max(0.0, $it['stock'] - self::HIGH_COVER_DAYS * $perDay) * $it['unit_cost'] : 0.0;
        }
        unset($it);
        $considered = count($items);

        switch ($rankBy) {
            case 'profit_high':
                usort($items, function ($a, $b) { return $b['profit'] <=> $a['profit']; });
                break;
            case 'profit_low':
                usort($items, function ($a, $b) { return $a['profit'] <=> $b['profit']; });
                break;
            case 'margin_high':
            case 'margin_low':
                // A margin on a handful of rupees says nothing; rank the items that sell.
                $floor = max(1000.0, 1000.0 * $days / 30);
                $items = array_filter($items, function ($i) use ($floor) { return $i['sales'] >= $floor; });
                $note[] = 'Only items with sales of at least ₹' . number_format($floor) . ' (excluding GST) in the period are ranked by margin.';
                $sign = $rankBy === 'margin_high' ? -1 : 1;
                usort($items, function ($a, $b) use ($sign) { return $sign * ($a['margin'] <=> $b['margin']); });
                break;
            case 'stock_more':
                $items = array_filter($items, function ($i) { return $i['profit'] > 0 && $i['cover'] !== null && $i['cover'] < self::LOW_COVER_DAYS; });
                usort($items, function ($a, $b) { return $b['profit'] <=> $a['profit']; });
                $note[] = 'Items that earned a profit in the period and hold under ' . self::LOW_COVER_DAYS
                    . ' days of stock at the period\'s rate of sale, highest profit first. Stock is what DASPOS shows now.';
                break;
            case 'stock_less':
                $items = array_filter($items, function ($i) { return $i['cover'] !== null && $i['cover'] > self::HIGH_COVER_DAYS; });
                usort($items, function ($a, $b) { return $b['excess'] <=> $a['excess']; });
                $note[] = 'Items holding more than ' . self::HIGH_COVER_DAYS . ' days of stock at the period\'s rate of sale, largest excess value (at cost) first. '
                    . 'Items with stock and no sale at all in the period are in the dead_stock check instead.';
                break;
        }
        $matching = count($items);
        $items = array_slice(array_values($items), 0, $limit);

        $names = self::names(array_column($items, 'item_id'));
        $rows = [];
        foreach ($items as $it) {
            $n = $names[$it['item_id']] ?? null;
            $rows[] = [
                'item' => $n['title'] ?? ('#' . $it['item_id']),
                'barcode' => $n['bar_code'] ?? '',
                'qty_sold' => round($it['qty'], 3),
                'sales_ex_gst' => round($it['sales'], 2),
                'cost_per_unit' => $it['unit_cost'] === null ? null : round($it['unit_cost'], 2),
                'gross_profit' => round($it['profit'], 2),
                'margin_percent' => $it['margin'] === null ? null : round($it['margin'], 1),
                'stock_now' => round($it['stock'], 3),
                'stock_value_at_cost' => $it['unit_cost'] === null ? null : round(max(0.0, $it['stock']) * $it['unit_cost'], 2),
                'days_of_stock' => $it['cover'] === null ? null : round($it['cover'], 1),
            ] + ($rankBy === 'stock_less' ? ['excess_stock_value' => round($it['excess'], 2)] : []);
        }
        if ($uncosted['items']) {
            $note[] = count($uncosted['items']) . ' item(s) with sales of ₹' . number_format($uncosted['sales'], 2)
                . ' have no purchase cost on record and are left out.';
        }
        return ['period' => [$from, $to], 'days' => $days, 'rank_by' => $rankBy, 'basis' => self::BASIS,
                'items_sold_with_a_cost' => $considered, 'items_matching_this_ranking' => $matching,
                'rows' => $rows, 'note' => implode(' ', $note)];
    }

    // --------------------------------------------------------------- pieces

    /**
     * What was sold, less what was refunded, per barcode row and month.
     *
     * @return array list of ['d' => item_detail id, 'item_id', 'm' => 'YYYY-MM', 'qty', 'sales' => GST excluded, 'gst']
     */
    public static function sold($from, $to, $outletId = null)
    {
        $params = [':f' => $from . ' 00:00:00', ':t' => date('Y-m-d', strtotime($to . ' +1 day')) . ' 00:00:00'];
        $outlet = '';
        if ($outletId !== null) {
            $params[':outlet'] = (int) $outletId;
            $outlet = ' AND o.outlet_id = :outlet';
        }
        $out = [];
        // The date index, named: over a year the optimiser may otherwise walk
        // an item index through all five million lines. A server without an
        // index of this name gets a warning, not an error.
        $sales = AiData::rows(
            'SELECT /*+ INDEX(oi create_time) */ oi.item_detail_id d, MAX(oi.item_id) item_id, DATE_FORMAT(oi.create_time, \'%Y-%m\') m,'
            . ' SUM(oi.qty) qty, SUM(oi.total_amt) amt, SUM(COALESCE(oi.tax_amount, 0)) tax'
            . ' FROM tbl_order_item oi' . ($outlet !== '' ? ' JOIN tbl_order o ON o.id = oi.order_id' : '')
            . ' WHERE oi.create_time >= :f AND oi.create_time < :t' . $outlet
            . ' GROUP BY oi.item_detail_id, m', $params, 300);
        foreach ($sales as $r) {
            $out[$r['d'] . '|' . $r['m']] = ['d' => (int) $r['d'], 'item_id' => (int) $r['item_id'], 'm' => $r['m'],
                'qty' => (float) $r['qty'], 'sales' => (float) $r['amt'] - (float) $r['tax'], 'gst' => (float) $r['tax']];
        }
        $refunds = AiData::rows(
            'SELECT ri.item_detail_id d, MAX(ri.item_id) item_id, DATE_FORMAT(ri.create_time, \'%Y-%m\') m,'
            . ' SUM(ri.qty) qty, SUM(ri.total_amt) amt, SUM(COALESCE(ri.tax_amt, 0)) tax'
            . ' FROM tbl_order_refund_item ri'
            . ($outlet !== '' ? ' JOIN tbl_order_refund r ON r.id = ri.order_refund_id JOIN tbl_order o ON o.id = r.order_id' : '')
            . ' WHERE ri.create_time >= :f AND ri.create_time < :t' . $outlet
            . ' GROUP BY ri.item_detail_id, m', $params, 300);
        foreach ($refunds as $r) {
            $key = $r['d'] . '|' . $r['m'];
            if (!isset($out[$key])) {
                $out[$key] = ['d' => (int) $r['d'], 'item_id' => (int) $r['item_id'], 'm' => $r['m'], 'qty' => 0.0, 'sales' => 0.0, 'gst' => 0.0];
            }
            $out[$key]['qty'] -= (float) $r['qty'];
            $out[$key]['sales'] -= (float) $r['amt'] - (float) $r['tax'];
            $out[$key]['gst'] -= (float) $r['tax'];
        }
        return array_values($out);
    }

    /**
     * Purchase cost per unit for each barcode row and month in $sold.
     *
     * @return array "item_detail id|YYYY-MM" => cost per unit; absent when there is none
     */
    public static function costs(array $sold)
    {
        if (!$sold) {
            return [];
        }
        $index = function ($ym) { return (int) substr($ym, 0, 4) * 12 + (int) substr($ym, 5, 2) - 1; };
        $months = array_map($index, array_unique(array_column($sold, 'm')));
        $firstIndex = min($months) - self::COST_MONTHS_BACK;
        $windowStart = sprintf('%04d-%02d-01 00:00:00', intdiv($firstIndex, 12), $firstIndex % 12 + 1);
        $lastIndex = max($months) + 1;
        $windowEnd = sprintf('%04d-%02d-01 00:00:00', intdiv($lastIndex, 12), $lastIndex % 12 + 1);

        // One pass over the GRNs of the months that matter, whatever is asked
        // about: a few thousand lines a month, and the answer is cached.
        $grn = [];
        foreach (AiData::rows(
            'SELECT d.item_detail_id, DATE_FORMAT(pb.create_time, \'%Y-%m\') m, SUM(d.approved_qty) units,'
            . ' SUM(CASE WHEN d.is_free = 1 THEN 0 ELSE d.approved_qty * d.price - COALESCE(d.discount_amt, 0) - COALESCE(d.discount_amt1, 0) END) cost'
            . ' FROM tbl_purchase_bill pb JOIN tbl_purchase_bill_detail d ON d.purchase_bill_id = pb.id'
            . ' WHERE pb.status = 1 AND pb.create_time >= :w AND pb.create_time < :t AND d.approved_qty > 0'
            . ' GROUP BY d.item_detail_id, m', [':w' => $windowStart, ':t' => $windowEnd], 300) as $r) {
            $grn[(int) $r['item_detail_id']][$index($r['m'])] = [(float) $r['units'], (float) $r['cost']];
        }

        $out = [];
        $missing = [];
        foreach ($sold as $s) {
            $m = $index($s['m']);
            $units = 0.0;
            $cost = 0.0;
            foreach ($grn[$s['d']] ?? [] as $gm => $g) {
                if ($gm <= $m && $gm >= $m - self::COST_MONTHS_BACK) {
                    $units += $g[0];
                    $cost += $g[1];
                }
            }
            if ($units > 0 && $cost > 0) {
                $out[$s['d'] . '|' . $s['m']] = $cost / $units;
            } else {
                $missing[$m][$s['d']] = $s;
            }
        }

        // The rest, a month at a time so the rule stays the month's own: the
        // latest GRN line from before that month's window, else the master.
        foreach ($missing as $m => $rows) {
            $start = $m - self::COST_MONTHS_BACK;
            $before = sprintf('%04d-%02d-01 00:00:00', intdiv($start, 12), $start % 12 + 1);
            $older = [];
            foreach (array_chunk(array_keys($rows), 500) as $chunk) {
                $params = [':w' => $before];
                foreach (AiData::rows(
                    'SELECT d.item_detail_id, d.approved_qty, d.price, d.discount_amt, d.discount_amt1 FROM tbl_purchase_bill_detail d'
                    . ' JOIN (SELECT MAX(d2.id) id FROM tbl_purchase_bill_detail d2 JOIN tbl_purchase_bill pb ON pb.id = d2.purchase_bill_id'
                    . ' WHERE d2.item_detail_id IN ' . AiData::inList($chunk, 'd', $params)
                    . ' AND pb.status = 1 AND pb.create_time < :w AND d2.is_free = 0 AND d2.approved_qty > 0 AND d2.price > 0'
                    . ' GROUP BY d2.item_detail_id) x ON x.id = d.id', $params, 300) as $r) {
                    $cost = ((float) $r['approved_qty'] * (float) $r['price'] - (float) $r['discount_amt'] - (float) $r['discount_amt1']) / (float) $r['approved_qty'];
                    if ($cost > 0) {
                        $older[(int) $r['item_detail_id']] = $cost;
                    }
                }
            }
            $master = [];
            $items = [];
            foreach ($rows as $d => $s) {
                if (!isset($older[$d])) {
                    $items[$s['item_id']] = true;
                }
            }
            foreach (array_chunk(array_keys($items), 500) as $chunk) {
                $params = [];
                foreach (AiData::rows('SELECT id, purchase_price FROM tbl_item WHERE purchase_price > 0 AND id IN '
                    . AiData::inList($chunk, 'i', $params), $params, 300) as $r) {
                    $master[(int) $r['id']] = (float) $r['purchase_price'];
                }
            }
            foreach ($rows as $d => $s) {
                if (isset($older[$d])) {
                    $out[$d . '|' . $s['m']] = $older[$d];
                } elseif (isset($master[$s['item_id']])) {
                    $out[$d . '|' . $s['m']] = $master[$s['item_id']];
                }
            }
        }
        return $out;
    }

    /** item id => stock on hand now, its barcodes and batches added up. */
    private static function stock($outletId)
    {
        $params = [];
        $where = '';
        if ($outletId !== null) {
            $params[':outlet'] = (int) $outletId;
            $where = ' WHERE COALESCE(NULLIF(s.outlet_id, 0), d.outlet_id) = :outlet';
        }
        $out = [];
        foreach (AiData::rows('SELECT d.item_id, SUM(s.balance_qty) qty FROM tbl_item_stock s JOIN tbl_item_detail d ON d.id = s.item_detail_id'
            . $where . ' GROUP BY d.item_id', $params, 120) as $r) {
            $out[(int) $r['item_id']] = (float) $r['qty'];
        }
        return $out;
    }

    /** item id => category title. */
    private static function categories()
    {
        $out = [];
        foreach (AiData::rows('SELECT i.id, c.title FROM tbl_item i JOIN tbl_item_category c ON c.id = i.category_id', [], 600) as $r) {
            $out[(int) $r['id']] = (string) $r['title'];
        }
        return $out;
    }

    /** item id => title and one barcode. */
    private static function names(array $itemIds)
    {
        $out = [];
        if (!$itemIds) {
            return $out;
        }
        $params = [];
        foreach (AiData::rows('SELECT i.id, i.title, MIN(d.bar_code) bar_code FROM tbl_item i LEFT JOIN tbl_item_detail d ON d.item_id = i.id'
            . ' WHERE i.id IN ' . AiData::inList($itemIds, 'p', $params) . ' GROUP BY i.id, i.title', $params) as $r) {
            $out[(int) $r['id']] = $r;
        }
        return $out;
    }

    private static function blank()
    {
        return ['sales' => 0.0, 'gst' => 0.0, 'costed_sales' => 0.0, 'cost' => 0.0, 'uncosted_sales' => 0.0];
    }

    private static function add(array &$g, array $s, $cost)
    {
        $g['sales'] += $s['sales'];
        $g['gst'] += $s['gst'];
        if ($cost === null) {
            $g['uncosted_sales'] += $s['sales'];
        } else {
            $g['costed_sales'] += $s['sales'];
            $g['cost'] += $s['qty'] * $cost;
        }
    }

    private static function finish(array $g)
    {
        $profit = $g['costed_sales'] - $g['cost'];
        return [
            'sales_incl_gst' => round($g['sales'] + $g['gst'], 2),
            'gst' => round($g['gst'], 2),
            'sales_ex_gst' => round($g['sales'], 2),
            'cost_of_goods_sold' => round($g['cost'], 2),
            'gross_profit' => round($profit, 2),
            'margin_percent' => $g['costed_sales'] > 0 ? round(100 * $profit / $g['costed_sales'], 1) : null,
            'sales_without_a_cost' => round($g['uncosted_sales'], 2),
        ];
    }
}
