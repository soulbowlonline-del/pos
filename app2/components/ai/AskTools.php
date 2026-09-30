<?php
namespace app\components\ai;

/**
 * What "Ask DASPOS" can look up, as tools Claude may call.
 *
 * Each tool is a fixed, parameterised, read-only query; the model chooses the
 * tool and fills in dates, an outlet or an item name, and never writes SQL.
 * Results are totals and item-level figures. There is deliberately no tool
 * that returns a customer's name or number: customer questions are answered
 * in counts.
 *
 * The definitions are returned in a fixed order and never change between
 * requests, so they and the system prompt are served from the prompt cache.
 */
class AskTools
{
    public const MAX_DAYS = 400;
    public const MAX_ROWS = 100;

    private static function nullable($type, $description = null)
    {
        $s = ['anyOf' => [['type' => $type], ['type' => 'null']]];
        if ($description !== null) {
            $s['description'] = $description;
        }
        return $s;
    }

    private static function tool($name, $description, array $properties)
    {
        return [
            'name' => $name,
            'description' => $description,
            'strict' => true,
            'inputSchema' => [
                'type' => 'object',
                'properties' => $properties ?: new \stdClass(),
                'required' => array_keys($properties),
                'additionalProperties' => false,
            ],
        ];
    }

    public static function definitions()
    {
        $from = ['type' => 'string', 'format' => 'date', 'description' => 'First day, YYYY-MM-DD, inclusive.'];
        $to = ['type' => 'string', 'format' => 'date', 'description' => 'Last day, YYYY-MM-DD, inclusive. At most 400 days after from_date.'];
        $outlet = self::nullable('integer', 'Outlet id from list_outlets, or null for all outlets.');
        return [
            self::tool('list_outlets', 'The store\'s outlets (branches) with their ids.', []),
            self::tool('sales_summary',
                'Bill totals for a period from the bills table: number of bills, net sales (bill totals, tax included), discount and gross. '
                . 'Grouped by nothing, day, month, outlet, payment mode or cashier. Dates are bill dates.',
                ['from_date' => $from, 'to_date' => $to, 'outlet_id' => $outlet,
                 'group_by' => ['type' => 'string', 'enum' => ['none', 'day', 'month', 'outlet', 'payment_mode', 'cashier']]]),
            self::tool('item_sales',
                'Quantity sold, amount and number of bills for the items matching item_query (a barcode, or words from the item name - every word must match), '
                . 'from bill lines. Returns which items matched. Grouped by item, day, month or outlet.',
                ['item_query' => ['type' => 'string'], 'from_date' => $from, 'to_date' => $to, 'outlet_id' => $outlet,
                 'group_by' => ['type' => 'string', 'enum' => ['item', 'day', 'month', 'outlet']]]),
            self::tool('top_items', 'Best-selling items in a period, by quantity or by amount, from bill lines.',
                ['from_date' => $from, 'to_date' => $to, 'outlet_id' => $outlet,
                 'rank_by' => ['type' => 'string', 'enum' => ['qty', 'amount']],
                 'limit' => ['type' => 'integer', 'description' => '1 to 50.']]),
            self::tool('find_items',
                'Look up items in the item master by barcode or name words: barcode, name, MRP, sale price, HSN, GST %, active or not, and stock on hand.',
                ['query' => ['type' => 'string']]),
            self::tool('stock_position',
                'Current stock of the items matching item_query, batch by batch (batch number, outlet, balance, MRP, cost), with totals.',
                ['item_query' => ['type' => 'string'], 'outlet_id' => $outlet]),
            self::tool('vendor_purchases',
                'Goods received from vendors in a period (GRNs by the date they were entered), per vendor and in total: number of GRNs, approved and pending, '
                . 'net_amount_approved (the net bill amount of approved GRNs - after discounts, tax included - the figure the DASPOS vendor reports use), '
                . 'tax_amount_approved, and pending_lines_amount (pending GRNs have no net amount until approved, so their line amounts are added up instead). '
                . 'vendor_query narrows to vendors whose name contains it; null for all.',
                ['vendor_query' => self::nullable('string'), 'from_date' => $from, 'to_date' => $to]),
            self::tool('refunds_summary', 'Refunds in a period: count and amount, grouped by nothing, day or outlet.',
                ['from_date' => $from, 'to_date' => $to,
                 'group_by' => ['type' => 'string', 'enum' => ['none', 'day', 'outlet']]]),
            self::tool('customer_counts',
                'Customer activity in a period, as counts only: new customers registered, bills with and without a customer, and customers who came back (two or more bills).',
                ['from_date' => $from, 'to_date' => $to]),
            self::tool('run_check',
                'One of the store\'s daily checks, the same ones shown on the Insights page: reorder (running out soon), negative_stock, dead_stock, '
                . 'pending_grn, above_mrp, duplicate_bills, bill_no_repeats, high_discount, credit_note_overuse, missing_hsn, duplicate_names, duplicate_barcodes. '
                . 'Returns the count and the first 25 rows.',
                ['check' => ['type' => 'string', 'enum' => array_keys(Insights::checks())]]),
            // Added after the ones above, which keeps their place in the prompt cache.
            self::tool('profit_summary',
                'Gross profit for a period: sales excluding GST (refunds taken off), the purchase cost of the units sold, gross profit and margin % '
                . '(profit as a share of sales excluding GST), in total and grouped by month or item category. Cost is the purchase price excluding GST, '
                . 'from the item\'s GRNs; the result\'s "basis" says exactly how. sales_without_a_cost is sales of items with no purchase cost on record, '
                . 'left out of profit and margin. This is gross profit, before rent, salaries and other expenses, which DASPOS does not hold.',
                ['from_date' => $from, 'to_date' => $to, 'outlet_id' => $outlet,
                 'group_by' => ['type' => 'string', 'enum' => ['none', 'month', 'category']]]),
            self::tool('item_profit',
                'Items ranked by what they earn or by how their stock compares with their sales in a period. Each row: quantity sold, sales excluding GST, '
                . 'purchase cost per unit, gross profit, margin %, stock on hand now, its value at cost and days_of_stock (stock divided by the period\'s daily sales). '
                . 'rank_by: profit_high (most profit), profit_low (least profit - losses first), margin_high, margin_low, '
                . 'stock_more (profitable items with under 14 days of stock: candidates to stock more of), '
                . 'stock_less (items with over 90 days of stock, largest excess value first: candidates to buy less of). '
                . 'item_query narrows to items whose name contains every word, or a barcode; null for all items.',
                ['from_date' => $from, 'to_date' => $to, 'outlet_id' => $outlet, 'item_query' => self::nullable('string'),
                 'rank_by' => ['type' => 'string', 'enum' => ['profit_high', 'profit_low', 'margin_high', 'margin_low', 'stock_more', 'stock_less']],
                 'limit' => ['type' => 'integer', 'description' => '1 to 50.']]),
        ];
    }

    /** Runs a tool; returns data for the model. Throws \InvalidArgumentException on bad input. */
    public static function run($name, array $input)
    {
        switch ($name) {
            case 'list_outlets':
                $out = [];
                foreach (Insights::outlets() as $id => $title) {
                    $out[] = ['id' => $id, 'title' => $title];
                }
                return ['outlets' => $out];
            case 'sales_summary':
                return self::salesSummary($input);
            case 'item_sales':
                return self::itemSales($input);
            case 'top_items':
                return self::topItems($input);
            case 'find_items':
                return self::findItems($input);
            case 'stock_position':
                return self::stockPosition($input);
            case 'vendor_purchases':
                return self::vendorPurchases($input);
            case 'refunds_summary':
                return self::refundsSummary($input);
            case 'customer_counts':
                return self::customerCounts($input);
            case 'profit_summary':
                list($f, $t) = self::range($input);
                return self::round(AiProfit::summary($f, $t, isset($input['outlet_id']) ? (int) $input['outlet_id'] : null,
                    (string) ($input['group_by'] ?? 'none')));
            case 'item_profit':
                list($f, $t) = self::range($input);
                return AiProfit::items($f, $t, isset($input['outlet_id']) ? (int) $input['outlet_id'] : null,
                    $input['item_query'] ?? null, (string) ($input['rank_by'] ?? 'profit_high'), (int) ($input['limit'] ?? 10));
            case 'run_check':
                $key = (string) ($input['check'] ?? '');
                if (!isset(Insights::checks()[$key])) {
                    throw new \InvalidArgumentException('Unknown check.');
                }
                $r = Insights::run($key);
                $rows = array_map(function ($row) { unset($row['_link']); return $row; }, array_slice($r['rows'], 0, 25));
                return ['check' => Insights::checks()[$key][1], 'total' => $r['total'], 'note' => $r['note'],
                        'columns' => $r['columns'], 'rows' => $rows];
        }
        throw new \InvalidArgumentException('Unknown tool ' . $name);
    }

    // ------------------------------------------------------------------ tools

    private static function range(array $in)
    {
        $today = date('Y-m-d');
        $from = AiData::date($in['from_date'] ?? '', null);
        $to = AiData::date($in['to_date'] ?? '', null);
        if ($from === null || $to === null) {
            throw new \InvalidArgumentException('from_date and to_date must be YYYY-MM-DD.');
        }
        if ($to < $from) {
            throw new \InvalidArgumentException('to_date is before from_date.');
        }
        if ((strtotime($to) - strtotime($from)) / 86400 > self::MAX_DAYS) {
            throw new \InvalidArgumentException('The period is longer than ' . self::MAX_DAYS . ' days; ask for a shorter one.');
        }
        return [$from, min($to, $today)];
    }

    private static function outletFilter(array $in, $column, array &$params)
    {
        if (isset($in['outlet_id'])) {
            $params[':outlet'] = (int) $in['outlet_id'];
            return ' AND ' . $column . ' = :outlet';
        }
        return '';
    }

    private static function salesSummary(array $in)
    {
        list($from, $to) = self::range($in);
        $keys = [
            'none' => "'all'",
            'day' => 'o.bill_date',
            'month' => "DATE_FORMAT(o.bill_date, '%Y-%m')",
            'outlet' => 'o.outlet_id',
            'payment_mode' => 'COALESCE(pm.title, o.mode_of_payment)',
            'cashier' => 'o.create_user_id',
        ];
        $by = $in['group_by'] ?? 'none';
        if (!isset($keys[$by])) {
            throw new \InvalidArgumentException('Bad group_by.');
        }
        $params = [':f' => $from, ':t' => $to];
        $sql = 'SELECT ' . $keys[$by] . ' k, COUNT(*) bills, SUM(o.total_amt) net_sales, SUM(COALESCE(o.discount_amt, 0)) discount,'
            // Only the till's item/order stores gross_total_amt; elsewhere it is
            // 0, and the bill total plus its discount is the gross.
            . ' SUM(COALESCE(NULLIF(o.gross_total_amt, 0), o.total_amt + COALESCE(o.discount_amt, 0))) gross'
            . ' FROM tbl_order o' . ($by === 'payment_mode' ? ' LEFT JOIN tbl_payment_mode pm ON pm.id = o.mode_of_payment' : '')
            . ' WHERE o.bill_date BETWEEN :f AND :t' . self::outletFilter($in, 'o.outlet_id', $params)
            . ' GROUP BY k ORDER BY k LIMIT 400';
        $rows = AiData::rows($sql, $params, 120);
        $rows = self::label($rows, $by);
        return ['period' => [$from, $to], 'group_by' => $by, 'rows' => self::cap(self::round($rows))];
    }

    /** How many matching barcodes a name lookup follows; beyond it the model is told to narrow down. */
    private const MATCH_LIMIT = 300;

    private static function itemSales(array $in)
    {
        list($from, $to) = self::range($in);
        $details = self::matchItems((string) ($in['item_query'] ?? ''), self::MATCH_LIMIT);
        if (!$details) {
            return ['matched_items' => [], 'note' => 'No item matches "' . ($in['item_query'] ?? '') . '". Try fewer or different words, or find_items.'];
        }
        $by = $in['group_by'] ?? 'item';
        $keys = ['item' => 'oi.item_detail_id', 'day' => 'DATE(oi.create_time)',
                 'month' => "DATE_FORMAT(oi.create_time, '%Y-%m')", 'outlet' => 'o.outlet_id'];
        if (!isset($keys[$by])) {
            throw new \InvalidArgumentException('Bad group_by.');
        }
        $params = [':f' => $from . ' 00:00:00', ':t' => date('Y-m-d', strtotime($to . ' +1 day')) . ' 00:00:00'];
        $outlet = self::outletFilter($in, 'o.outlet_id', $params);
        // The bill is joined only when an outlet is asked about: over a long
        // period that join is most of the query's cost.
        $join = ($outlet !== '' || $by === 'outlet') ? ' JOIN tbl_order o ON o.id = oi.order_id' : '';
        $rows = AiData::rows(
            'SELECT ' . $keys[$by] . ' k, SUM(oi.qty) qty, SUM(oi.total_amt) amount, COUNT(DISTINCT oi.order_id) bills'
            . ' FROM tbl_order_item oi' . $join
            . ' WHERE oi.item_detail_id IN ' . AiData::inList(array_keys($details), 'i', $params)
            . ' AND oi.create_time >= :f AND oi.create_time < :t' . $outlet
            . ' GROUP BY k ORDER BY k LIMIT 2000', $params, 120);
        if ($by === 'item') {
            // One product has a barcode row per outlet; add them up per product.
            $byItem = [];
            foreach ($rows as $r) {
                $d = $details[(int) $r['k']] ?? null;
                $key = $d ? 'i' . $d['item_id'] : 'd' . $r['k'];
                if (!isset($byItem[$key])) {
                    $byItem[$key] = ['item' => $d ? $d['title'] : '#' . $r['k'], 'barcode' => $d ? $d['bar_code'] : '',
                                     'qty' => 0.0, 'amount' => 0.0, 'bills' => 0];
                }
                $byItem[$key]['qty'] += (float) $r['qty'];
                $byItem[$key]['amount'] += (float) $r['amount'];
                // A bill can hold the same product under two barcodes only rarely; the sum is an upper bound.
                $byItem[$key]['bills'] += (int) $r['bills'];
            }
            $rows = array_values($byItem);
        } else {
            $rows = self::label($rows, $by);
        }
        $products = [];
        foreach ($details as $d) {
            $products[$d['item_id']] = ['title' => $d['title'], 'barcode' => $d['bar_code']];
        }
        $out = [
            'period' => [$from, $to],
            'matched_products' => array_slice(array_values($products), 0, 40),
            'matched_product_count' => count($products),
            'group_by' => $by,
            'rows' => self::cap(self::round($rows)),
        ];
        if (count($details) >= self::MATCH_LIMIT) {
            $out['note'] = 'More than ' . self::MATCH_LIMIT . ' barcodes match; only the first ' . self::MATCH_LIMIT
                . ' are counted, so these totals may be too low. Ask with more specific words.';
        }
        return $out;
    }

    private static function topItems(array $in)
    {
        list($from, $to) = self::range($in);
        $limit = max(1, min(50, (int) ($in['limit'] ?? 10)));
        $order = ($in['rank_by'] ?? 'qty') === 'amount' ? 'amount' : 'qty';
        $params = [':f' => $from . ' 00:00:00', ':t' => date('Y-m-d', strtotime($to . ' +1 day')) . ' 00:00:00'];
        $outlet = self::outletFilter($in, 'o.outlet_id', $params);
        // Per product, so an item sold at several outlets (one barcode row
        // each) is ranked on its whole sale.
        $rows = AiData::rows(
            'SELECT oi.item_id, SUM(oi.qty) qty, SUM(oi.total_amt) amount, COUNT(DISTINCT oi.order_id) bills'
            . ' FROM tbl_order_item oi' . ($outlet !== '' ? ' JOIN tbl_order o ON o.id = oi.order_id' : '')
            . ' WHERE oi.create_time >= :f AND oi.create_time < :t AND oi.item_id IS NOT NULL' . $outlet
            . ' GROUP BY oi.item_id ORDER BY ' . $order . ' DESC LIMIT ' . $limit, $params, 300);
        $names = [];
        if ($rows) {
            $p = [];
            foreach (AiData::rows('SELECT i.id, i.title, MIN(d.bar_code) bar_code FROM tbl_item i LEFT JOIN tbl_item_detail d ON d.item_id = i.id'
                     . ' WHERE i.id IN ' . AiData::inList(array_column($rows, 'item_id'), 'p', $p) . ' GROUP BY i.id, i.title', $p) as $n) {
                $names[(int) $n['id']] = $n;
            }
        }
        foreach ($rows as &$r) {
            $n = $names[(int) $r['item_id']] ?? null;
            $r = ['item' => $n['title'] ?? ('#' . $r['item_id']), 'barcode' => $n['bar_code'] ?? '',
                  'qty' => $r['qty'], 'amount' => $r['amount'], 'bills' => $r['bills']];
        }
        unset($r);
        return ['period' => [$from, $to], 'rank_by' => $order, 'rows' => self::round($rows)];
    }

    private static function findItems(array $in)
    {
        $items = self::matchItems((string) ($in['query'] ?? ''), 20);
        if (!$items) {
            return ['items' => [], 'note' => 'No item matches.'];
        }
        $stock = [];
        $params = [];
        foreach (AiData::rows('SELECT item_detail_id, SUM(balance_qty) q FROM tbl_item_stock WHERE item_detail_id IN '
                 . AiData::inList(array_keys($items), 'd', $params) . ' GROUP BY item_detail_id', $params) as $r) {
            $stock[(int) $r['item_detail_id']] = $r['q'];
        }
        $out = [];
        foreach ($items as $id => $d) {
            $out[] = [
                'barcode' => $d['bar_code'], 'title' => $d['title'], 'mrp' => $d['mrp'], 'sale_price' => $d['sale_price'],
                'hsn' => $d['hsn_code'], 'gst_percent' => $d['gst'], 'active' => (int) $d['status'] === 0 && (int) $d['item_status'] === 0,
                'outlet' => Insights::outlets()[(int) $d['outlet_id']] ?? null, 'stock' => $stock[$id] ?? 0,
            ];
        }
        return ['items' => self::round($out)];
    }

    private static function stockPosition(array $in)
    {
        $limit = 40;
        $items = self::matchItems((string) ($in['item_query'] ?? ''), $limit);
        if (!$items) {
            return ['batches' => [], 'note' => 'No item matches.'];
        }
        $params = [];
        $sql = 'SELECT s.item_detail_id, COALESCE(NULLIF(s.outlet_id, 0), d.outlet_id) outlet_id, s.batch_number, s.balance_qty,'
            . ' s.mrp, s.base_price, s.create_time'
            . ' FROM tbl_item_stock s JOIN tbl_item_detail d ON d.id = s.item_detail_id'
            . ' WHERE s.item_detail_id IN ' . AiData::inList(array_keys($items), 'd', $params) . ' AND s.balance_qty <> 0'
            . self::outletFilter($in, 'COALESCE(NULLIF(s.outlet_id, 0), d.outlet_id)', $params)
            . ' ORDER BY s.item_detail_id, s.id DESC LIMIT 300';
        $outlets = Insights::outlets();
        $batches = [];
        $totals = [];
        foreach (AiData::rows($sql, $params) as $r) {
            $d = $items[(int) $r['item_detail_id']];
            $key = (int) $d['item_id'];
            if (!isset($totals[$key])) {
                $totals[$key] = ['item' => $d['title'], 'barcode' => $d['bar_code'], 'total' => 0.0];
            }
            $totals[$key]['total'] += (float) $r['balance_qty'];
            $batches[] = ['item' => $d['title'], 'barcode' => $d['bar_code'], 'outlet' => $outlets[(int) $r['outlet_id']] ?? null,
                          'batch' => $r['batch_number'], 'balance' => $r['balance_qty'], 'mrp' => $r['mrp'], 'cost' => $r['base_price'],
                          'received' => substr((string) $r['create_time'], 0, 10)];
        }
        $out = ['totals' => self::round(array_values($totals)), 'batches' => self::cap(self::round($batches))];
        if (count($items) >= $limit) {
            $out['note'] = 'More than ' . $limit . ' barcodes match; only the first ' . $limit . ' are shown. Ask with more specific words.';
        }
        return $out;
    }

    private static function vendorPurchases(array $in)
    {
        list($from, $to) = self::range($in);
        $params = [':f' => $from . ' 00:00:00', ':t' => date('Y-m-d', strtotime($to . ' +1 day')) . ' 00:00:00'];
        $where = '';
        if (!empty($in['vendor_query'])) {
            $params[':v'] = '%' . self::likeEscape(trim($in['vendor_query'])) . '%';
            $where = ' AND v.name LIKE :v';
        }
        // The GRN approval screen writes bill_amount, tax_amount and
        // net_bill_amount; total_amount is an older field it never fills, so
        // summing it gave 0. Vendor::getVendorPurchaseTotalAmount() sums
        // net_bill_amount, and so does this.
        $cols = 'COUNT(*) grns, SUM(CASE WHEN pb.status = 1 THEN 1 ELSE 0 END) approved,'
            . ' SUM(CASE WHEN pb.status <> 1 THEN 1 ELSE 0 END) pending,'
            . ' SUM(CASE WHEN pb.status = 1 THEN COALESCE(pb.net_bill_amount, 0) ELSE 0 END) net_amount_approved,'
            . ' SUM(CASE WHEN pb.status = 1 THEN COALESCE(pb.tax_amount, 0) ELSE 0 END) tax_amount_approved,'
            . ' SUM(CASE WHEN pb.status <> 1 THEN (SELECT COALESCE(SUM(d.amount), 0) FROM tbl_purchase_bill_detail d'
            . ' WHERE d.purchase_bill_id = pb.id) ELSE 0 END) pending_lines_amount';
        $from_ = ' FROM tbl_purchase_bill pb LEFT JOIN tbl_vendor v ON v.id = pb.vendor_id'
            . ' WHERE pb.create_time >= :f AND pb.create_time < :t' . $where;
        $rows = AiData::rows('SELECT v.name vendor, ' . $cols . $from_
            . ' GROUP BY v.name ORDER BY net_amount_approved DESC LIMIT 50', $params, 120);
        $total = AiData::rows('SELECT COUNT(DISTINCT pb.vendor_id) vendors, ' . $cols . $from_, $params, 120);
        $out = ['period' => [$from, $to], 'total' => self::round($total[0] ?? []), 'rows' => self::round($rows)];
        if ((int) ($total[0]['vendors'] ?? 0) > 50) {
            $out['note'] = 'rows lists the 50 largest vendors; total covers all ' . (int) $total[0]['vendors'] . '.';
        }
        return $out;
    }

    private static function refundsSummary(array $in)
    {
        list($from, $to) = self::range($in);
        $by = $in['group_by'] ?? 'none';
        $keys = ['none' => "'all'", 'day' => 'DATE(r.create_time)', 'outlet' => 'o.outlet_id'];
        if (!isset($keys[$by])) {
            throw new \InvalidArgumentException('Bad group_by.');
        }
        $rows = AiData::rows(
            'SELECT ' . $keys[$by] . ' k, COUNT(*) refunds, SUM(COALESCE(r.total_amt, 0)) amount'
            . ' FROM tbl_order_refund r LEFT JOIN tbl_order o ON o.id = r.order_id'
            . ' WHERE r.create_time >= :f AND r.create_time < :t GROUP BY k ORDER BY k LIMIT 400',
            [':f' => $from . ' 00:00:00', ':t' => date('Y-m-d', strtotime($to . ' +1 day')) . ' 00:00:00'], 120);
        return ['period' => [$from, $to], 'group_by' => $by, 'rows' => self::cap(self::round(self::label($rows, $by)))];
    }

    private static function customerCounts(array $in)
    {
        list($from, $to) = self::range($in);
        $p = [':f' => $from, ':t' => $to];
        $new = AiData::scalar('SELECT COUNT(*) FROM tbl_customer WHERE create_time >= :f AND create_time < DATE_ADD(:t, INTERVAL 1 DAY)', $p, 120);
        $bills = AiData::rows(
            'SELECT SUM(CASE WHEN customer_id IS NOT NULL AND customer_id > 0 THEN 1 ELSE 0 END) with_customer,'
            . ' SUM(CASE WHEN customer_id IS NULL OR customer_id = 0 THEN 1 ELSE 0 END) walk_in,'
            . ' COUNT(DISTINCT CASE WHEN customer_id > 0 THEN customer_id END) customers'
            . ' FROM tbl_order WHERE bill_date BETWEEN :f AND :t', $p, 120);
        $returning = AiData::scalar(
            'SELECT COUNT(*) FROM (SELECT customer_id FROM tbl_order WHERE bill_date BETWEEN :f AND :t AND customer_id > 0'
            . ' GROUP BY customer_id HAVING COUNT(*) >= 2) x', $p, 120);
        return ['period' => [$from, $to], 'new_customers' => (int) $new,
                'bills_with_customer' => (int) ($bills[0]['with_customer'] ?? 0), 'walk_in_bills' => (int) ($bills[0]['walk_in'] ?? 0),
                'distinct_customers' => (int) ($bills[0]['customers'] ?? 0), 'customers_with_2_or_more_bills' => (int) $returning];
    }

    // ---------------------------------------------------------------- helpers

    /**
     * item_detail id => row, for a barcode (exact) or name words (all must
     * appear). Active items first.
     */
    public static function matchItems($query, $limit)
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }
        $select = 'SELECT d.id, d.item_id, d.bar_code, d.outlet_id, d.status, COALESCE(NULLIF(d.mrp, 0), i.mrp) mrp, i.sale_price, i.hsn_code,'
            . ' i.title, i.status item_status,'
            . ' COALESCE(NULLIF(t.tax_val1 + t.tax_val2, 0), t.tax_val4, 0) gst'
            . ' FROM tbl_item_detail d JOIN tbl_item i ON i.id = d.item_id LEFT JOIN tbl_tax t ON t.id = d.tax_id';
        $rows = AiData::rows($select . ' WHERE d.bar_code = :b ORDER BY d.status, d.id DESC LIMIT ' . (int) $limit, [':b' => $query]);
        if (!$rows) {
            $params = [];
            $conds = [];
            foreach (array_slice(preg_split('/\s+/', $query), 0, 6) as $n => $word) {
                $params[':w' . $n] = '%' . self::likeEscape($word) . '%';
                $conds[] = 'i.title LIKE :w' . $n;
            }
            $rows = AiData::rows($select . ' WHERE ' . implode(' AND ', $conds)
                . ' ORDER BY i.status, d.status, i.title, d.id DESC LIMIT ' . (int) $limit, $params);
        }
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['id']] = $r;
        }
        return $out;
    }

    private static function likeEscape($s)
    {
        return strtr($s, ['\\' => '\\\\', '%' => '\%', '_' => '\_']);
    }

    /** Replace outlet / cashier ids in the group key with names. */
    private static function label(array $rows, $by)
    {
        if ($by === 'outlet') {
            $o = Insights::outlets();
            foreach ($rows as &$r) {
                $r['k'] = $o[(int) $r['k']] ?? ('outlet #' . $r['k']);
            }
            unset($r);
        } elseif ($by === 'cashier') {
            $ids = array_filter(array_map('intval', array_column($rows, 'k')));
            $names = [];
            if ($ids) {
                $params = [];
                foreach (AiData::rows('SELECT id, full_name, username FROM tbl_user WHERE id IN ' . AiData::inList($ids, 'u', $params), $params) as $u) {
                    $names[(int) $u['id']] = $u['full_name'] ?: $u['username'];
                }
            }
            foreach ($rows as &$r) {
                $r['k'] = $names[(int) $r['k']] ?? ('user #' . $r['k']);
            }
            unset($r);
        }
        foreach ($rows as &$r) {
            $r = ['group' => $r['k']] + array_diff_key($r, ['k' => 1]);
        }
        unset($r);
        return $rows;
    }

    private static function round($value)
    {
        if (is_array($value)) {
            return array_map([self::class, 'round'], $value);
        }
        if (is_string($value) && preg_match('/^-?\d+\.\d{3,}$/', $value)) {
            return round((float) $value, 2);
        }
        if (is_float($value)) {
            return round($value, 2);
        }
        return $value;
    }

    private static function cap(array $rows)
    {
        if (count($rows) > self::MAX_ROWS) {
            return ['first_rows' => array_slice($rows, 0, self::MAX_ROWS), 'truncated_from' => count($rows)];
        }
        return $rows;
    }
}
