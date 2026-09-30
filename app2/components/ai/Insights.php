<?php
namespace app\components\ai;

use app\components\Ui;

/**
 * The store's daily checks, worked out from the database with plain rules.
 *
 * These need no API key and cost nothing: they are what a careful manager
 * would look for each morning - what is about to run out, what is stuck, what
 * looks wrong in the bills and the item master - done by the database in a
 * few seconds. Claude is only used afterwards, if someone asks for a written
 * summary of them.
 *
 * Every check only reads. None of them changes a quantity, a price or a bill;
 * where something needs fixing, the row links to the existing screen where a
 * person fixes it the usual way.
 */
class Insights
{
    public const CACHE_SECONDS = 600;

    /** key => [group, title, why] */
    public static function checks()
    {
        return [
            'reorder' => ['Stock', 'Running out soon',
                'Items that, at their average sale over the last 28 days, have less than 3 days of stock left at an outlet. The suggested quantity would cover 7 days. A suggestion for whoever raises the MRS or PO - nothing is ordered automatically.'],
            'negative_stock' => ['Stock', 'Batches below zero',
                'Batches whose balance has gone negative: sold before the GRN was approved, a wrong batch picked, or a count that needs correcting. The stock figure for these items cannot be trusted until they are checked.'],
            'dead_stock' => ['Stock', 'Stock not selling',
                'In stock for more than 60 days with no sale in the last 60, by value at cost. Candidates for a return to the vendor, a markdown or a transfer.'],
            'pending_grn' => ['Purchase', 'GRNs waiting for approval',
                'Goods received notes not approved more than 5 days after they were entered (last 180 days). Their stock is not added to inventory until someone approves them.'],
            'above_mrp' => ['Billing', 'Sold above MRP',
                'Bill lines in the last 30 days charged at a rate above the MRP printed on the line. Selling above MRP is not allowed; it usually means a wrong MRP or rate in the item master.'],
            'duplicate_bills' => ['Billing', 'Possible duplicate bills',
                'Pairs of bills in the last 14 days from the same outlet, cashier and customer, for the same amount and number of lines, within two minutes of each other - the pattern a retried Save leaves. Check with the cashier before cancelling either.'],
            'bill_no_repeats' => ['Billing', 'Bill numbers used twice',
                'The same bill number on more than one bill at the same outlet in the last 90 days.'],
            'high_discount' => ['Billing', 'Discounts by cashier',
                'Per cashier, the last 30 days: how many bills carried a discount above 20% of the bill, and the discount given in total. Worth a look if one cashier stands out.'],
            'credit_note_overuse' => ['Billing', 'Credit notes used beyond their value',
                'Credit notes whose used amount is more than their value.'],
            'missing_hsn' => ['Item master', 'Active items without an HSN code',
                'GST returns need an HSN code on every item sold.'],
            'duplicate_names' => ['Item master', 'Items with the same name',
                'Active items whose names are identical apart from capital letters and spaces at either end. Duplicates split stock and sales between two records.'],
            'duplicate_barcodes' => ['Item master', 'One barcode on several items',
                'The same barcode on more than one active item. A scan at the till can pick either.'],
        ];
    }

    /**
     * @return array ['columns' => [key => label], 'rows' => [...], 'total' => int,
     *                'note' => string|null, 'time' => string]
     */
    public static function run($key)
    {
        if (!isset(self::checks()[$key])) {
            throw new \InvalidArgumentException('Unknown check ' . $key);
        }
        $cacheKey = 'ai-insight:' . $key . ':' . date('Y-m-d');
        $hit = AiData::cache()->get($cacheKey);
        if ($hit !== false) {
            return $hit;
        }
        $method = 'check' . str_replace('_', '', ucwords($key, '_'));
        $result = self::$method();
        $result += ['note' => null];
        $result['time'] = date('H:i');
        if (!isset($result['total'])) {
            $result['total'] = count($result['rows']);
        }
        AiData::cache()->set($cacheKey, $result, self::CACHE_SECONDS);
        return $result;
    }

    public static function forget($key)
    {
        AiData::cache()->delete('ai-insight:' . $key . ':' . date('Y-m-d'));
    }

    // ------------------------------------------------------------------ stock

    private static function checkReorder()
    {
        $days = 28;
        $from = date('Y-m-d 00:00:00', strtotime('-' . $days . ' days'));
        $to = date('Y-m-d 00:00:00');
        // Sales per outlet and barcode. tbl_order_item is indexed on
        // create_time; the outlet comes from the bill by primary key.
        $sold = AiData::rows(
            'SELECT COALESCE(o.outlet_id, 0) outlet_id, oi.item_detail_id, SUM(oi.qty) sold'
            . ' FROM tbl_order_item oi JOIN tbl_order o ON o.id = oi.order_id'
            . ' WHERE oi.create_time >= :f AND oi.create_time < :t AND oi.qty > 0 AND oi.item_detail_id IS NOT NULL'
            . ' GROUP BY COALESCE(o.outlet_id, 0), oi.item_detail_id',
            [':f' => $from, ':t' => $to]);
        if (!$sold) {
            return ['columns' => [], 'rows' => [], 'note' => 'No sales in the last 28 days.'];
        }
        $stock = self::stockByOutlet(array_column($sold, 'item_detail_id'));

        $candidates = [];
        foreach ($sold as $s) {
            $perDay = (float) $s['sold'] / $days;
            if ($perDay <= 0) {
                continue;
            }
            $have = $stock[$s['outlet_id'] . ':' . $s['item_detail_id']] ?? 0.0;
            $cover = $have / $perDay;
            if ($cover < 3) {
                $candidates[] = [
                    'outlet_id' => (int) $s['outlet_id'],
                    'item_detail_id' => (int) $s['item_detail_id'],
                    'sold' => (float) $s['sold'],
                    'per_day' => $perDay,
                    'stock' => $have,
                    'cover' => max(0.0, $cover),
                    'suggest' => max(0, (int) ceil($perDay * 7 - max($have, 0))),
                ];
            }
        }
        $details = self::details(array_column($candidates, 'item_detail_id'));
        // An inactive item is not reordered.
        $candidates = array_values(array_filter($candidates, function ($c) use ($details) {
            $d = $details[$c['item_detail_id']] ?? null;
            return $d === null || (int) $d['item_status'] === 0;
        }));
        usort($candidates, function ($a, $b) {
            return [$a['cover'], -$a['per_day']] <=> [$b['cover'], -$b['per_day']];
        });
        $total = count($candidates);
        $candidates = array_slice($candidates, 0, 200);

        $vendors = self::lastVendors(array_column($candidates, 'item_detail_id'));
        $outlets = self::outlets();
        $rows = [];
        foreach ($candidates as $c) {
            $d = $details[$c['item_detail_id']] ?? null;
            $rows[] = [
                'outlet' => $outlets[$c['outlet_id']] ?? ('#' . $c['outlet_id']),
                'barcode' => $d['bar_code'] ?? '',
                'item' => $d['title'] ?? ('#' . $c['item_detail_id']),
                'sold' => self::num($c['sold']),
                'per_day' => self::num($c['per_day'], 1),
                'stock' => self::num($c['stock']),
                'days_left' => self::num($c['cover'], 1),
                'suggest' => $c['suggest'],
                'reorder_qty' => $d ? self::num($d['reorder_qty']) : '',
                'last_vendor' => $vendors[$c['item_detail_id']] ?? '',
                '_link' => $d ? Ui::to('item/update', ['id' => $d['item_id']]) : null,
            ];
        }
        return [
            'columns' => ['outlet' => 'Outlet', 'barcode' => 'Barcode', 'item' => 'Item', 'sold' => 'Sold (28 days)',
                'per_day' => 'Per day', 'stock' => 'In stock', 'days_left' => 'Days left', 'suggest' => 'Suggested qty (7 days)',
                'reorder_qty' => 'Reorder qty (master)', 'last_vendor' => 'Last bought from'],
            'rows' => $rows,
            'total' => $total,
        ];
    }

    private static function checkNegativeStock()
    {
        $total = (int) AiData::scalar('SELECT COUNT(*) FROM tbl_item_stock WHERE balance_qty < 0');
        $rows = AiData::rows(
            'SELECT s.id, COALESCE(NULLIF(s.outlet_id, 0), d.outlet_id) outlet_id, s.batch_number, s.balance_qty, s.create_time,'
            . ' d.bar_code, i.title, i.id item_id'
            . ' FROM tbl_item_stock s LEFT JOIN tbl_item_detail d ON d.id = s.item_detail_id LEFT JOIN tbl_item i ON i.id = d.item_id'
            . ' WHERE s.balance_qty < 0 ORDER BY s.balance_qty ASC LIMIT 200');
        $outlets = self::outlets();
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'outlet' => $outlets[(int) $r['outlet_id']] ?? '',
                'barcode' => $r['bar_code'],
                'item' => $r['title'],
                'batch' => $r['batch_number'],
                'balance' => self::num($r['balance_qty']),
                'since' => substr((string) $r['create_time'], 0, 10),
                '_link' => $r['item_id'] ? Ui::to('itemStock/admin', ['ItemStock[batch_number]' => $r['batch_number']]) : null,
            ];
        }
        return ['columns' => ['outlet' => 'Outlet', 'barcode' => 'Barcode', 'item' => 'Item', 'batch' => 'Batch',
            'balance' => 'Balance', 'since' => 'Batch created'], 'rows' => $out, 'total' => $total];
    }

    private static function checkDeadStock()
    {
        $cut = date('Y-m-d 00:00:00', strtotime('-60 days'));
        $held = AiData::rows(
            'SELECT COALESCE(NULLIF(s.outlet_id, 0), d.outlet_id, 0) outlet_id, s.item_detail_id,'
            . ' SUM(s.balance_qty) qty, SUM(s.balance_qty * COALESCE(s.base_price, 0)) value, MIN(s.create_time) first_in'
            . ' FROM tbl_item_stock s JOIN tbl_item_detail d ON d.id = s.item_detail_id'
            . ' WHERE s.balance_qty > 0'
            . ' GROUP BY COALESCE(NULLIF(s.outlet_id, 0), d.outlet_id, 0), s.item_detail_id'
            . ' HAVING MIN(s.create_time) < :c',
            [':c' => $cut]);
        if (!$held) {
            return ['columns' => [], 'rows' => [], 'note' => 'No stock older than 60 days.'];
        }
        $soldSet = [];
        foreach (AiData::rows(
            'SELECT DISTINCT COALESCE(o.outlet_id, 0) outlet_id, oi.item_detail_id'
            . ' FROM tbl_order_item oi JOIN tbl_order o ON o.id = oi.order_id WHERE oi.create_time >= :c',
            [':c' => $cut]) as $s) {
            $soldSet[$s['outlet_id'] . ':' . $s['item_detail_id']] = true;
        }
        $dead = [];
        foreach ($held as $h) {
            if (!isset($soldSet[$h['outlet_id'] . ':' . $h['item_detail_id']])) {
                $dead[] = $h;
            }
        }
        usort($dead, function ($a, $b) { return (float) $b['value'] <=> (float) $a['value']; });
        $total = count($dead);
        $totalValue = array_sum(array_map(function ($d) { return (float) $d['value']; }, $dead));
        $dead = array_slice($dead, 0, 200);
        $details = self::details(array_column($dead, 'item_detail_id'));
        $outlets = self::outlets();
        $rows = [];
        foreach ($dead as $h) {
            $d = $details[$h['item_detail_id']] ?? null;
            $rows[] = [
                'outlet' => $outlets[(int) $h['outlet_id']] ?? '',
                'barcode' => $d['bar_code'] ?? '',
                'item' => $d['title'] ?? ('#' . $h['item_detail_id']),
                'qty' => self::num($h['qty']),
                'value' => self::money($h['value']),
                'first_in' => substr((string) $h['first_in'], 0, 10),
                '_link' => $d ? Ui::to('item/update', ['id' => $d['item_id']]) : null,
            ];
        }
        return ['columns' => ['outlet' => 'Outlet', 'barcode' => 'Barcode', 'item' => 'Item', 'qty' => 'In stock',
            'value' => 'Value at cost', 'first_in' => 'Oldest batch'],
            'rows' => $rows, 'total' => $total,
            'note' => 'Value at cost of all ' . $total . ' items: ' . self::money($totalValue) . '.'];
    }

    // --------------------------------------------------------------- purchase

    private static function checkPendingGrn()
    {
        $rows = AiData::rows(
            'SELECT pb.id, pb.bill_no, pb.create_time, pb.total_amount, pb.outlet_id, v.name vendor'
            . ' FROM tbl_purchase_bill pb LEFT JOIN tbl_vendor v ON v.id = pb.vendor_id'
            . ' WHERE pb.status <> 1 AND pb.create_time < :cut AND pb.create_time >= :from'
            . ' ORDER BY pb.create_time ASC LIMIT 200',
            [':cut' => date('Y-m-d H:i:s', strtotime('-5 days')), ':from' => date('Y-m-d 00:00:00', strtotime('-180 days'))]);
        $outlets = self::outlets();
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'grn' => $r['id'],
                'vendor' => $r['vendor'],
                'bill_no' => $r['bill_no'],
                'outlet' => $outlets[(int) $r['outlet_id']] ?? '',
                'entered' => substr((string) $r['create_time'], 0, 10),
                'days' => (int) floor((time() - strtotime($r['create_time'])) / 86400),
                'amount' => self::money($r['total_amount']),
                '_link' => Ui::to('purchaseBill/view', ['id' => $r['id']]),
            ];
        }
        return ['columns' => ['grn' => 'GRN', 'vendor' => 'Vendor', 'bill_no' => 'Vendor bill', 'outlet' => 'Outlet',
            'entered' => 'Entered', 'days' => 'Days waiting', 'amount' => 'Amount'], 'rows' => $out];
    }

    // ---------------------------------------------------------------- billing

    private static function checkAboveMrp()
    {
        $rows = AiData::rows(
            'SELECT o.id order_id, o.bill_no, o.outlet_id, oi.create_time, oi.mrp, oi.sale_rate, oi.qty, d.bar_code, i.title'
            . ' FROM tbl_order_item oi JOIN tbl_order o ON o.id = oi.order_id'
            . ' LEFT JOIN tbl_item_detail d ON d.id = oi.item_detail_id LEFT JOIN tbl_item i ON i.id = oi.item_id'
            . ' WHERE oi.create_time >= :f AND oi.mrp > 0 AND oi.sale_rate > oi.mrp + 0.01'
            . ' ORDER BY oi.create_time DESC LIMIT 200',
            [':f' => date('Y-m-d 00:00:00', strtotime('-30 days'))]);
        $outlets = self::outlets();
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'bill_no' => $r['bill_no'],
                'outlet' => $outlets[(int) $r['outlet_id']] ?? '',
                'date' => substr((string) $r['create_time'], 0, 16),
                'barcode' => $r['bar_code'],
                'item' => $r['title'],
                'mrp' => self::money($r['mrp']),
                'rate' => self::money($r['sale_rate']),
                'qty' => self::num($r['qty']),
                '_link' => Ui::to('order/view', ['id' => $r['order_id']]),
            ];
        }
        return ['columns' => ['bill_no' => 'Bill', 'outlet' => 'Outlet', 'date' => 'Time', 'barcode' => 'Barcode',
            'item' => 'Item', 'mrp' => 'MRP', 'rate' => 'Rate charged', 'qty' => 'Qty'], 'rows' => $out];
    }

    private static function checkDuplicateBills()
    {
        $bills = AiData::rows(
            'SELECT id, bill_no, outlet_id, create_user_id, customer_id, total_amt, create_time'
            . ' FROM tbl_order WHERE create_time >= :f AND total_amt > 0 ORDER BY create_time ASC, id ASC',
            [':f' => date('Y-m-d 00:00:00', strtotime('-14 days'))]);
        // Pairs are found here rather than by a self-join, which without an
        // index on tbl_order.create_time would compare every bill with every other.
        $groups = [];
        foreach ($bills as $b) {
            $groups[implode('|', [$b['outlet_id'], $b['create_user_id'], $b['customer_id'], number_format((float) $b['total_amt'], 2, '.', '')])][] = $b;
        }
        $pairs = [];
        foreach ($groups as $g) {
            for ($i = 1, $n = count($g); $i < $n; $i++) {
                $gap = strtotime($g[$i]['create_time']) - strtotime($g[$i - 1]['create_time']);
                if ($gap >= 0 && $gap <= 120) {
                    $pairs[] = [$g[$i - 1], $g[$i], $gap];
                }
            }
        }
        if (!$pairs) {
            return ['columns' => [], 'rows' => []];
        }
        $ids = [];
        foreach ($pairs as $p) {
            $ids[] = $p[0]['id'];
            $ids[] = $p[1]['id'];
        }
        $params = [];
        $lines = [];
        foreach (AiData::rows('SELECT order_id, COUNT(*) n, SUM(qty) q FROM tbl_order_item WHERE order_id IN '
                 . AiData::inList($ids, 'o', $params) . ' GROUP BY order_id', $params) as $l) {
            $lines[$l['order_id']] = $l['n'] . '/' . number_format((float) $l['q'], 3, '.', '');
        }
        $users = self::users(array_merge(array_column(array_column($pairs, 0), 'create_user_id')));
        $outlets = self::outlets();
        $out = [];
        foreach (array_reverse($pairs) as $p) {
            list($a, $b, $gap) = $p;
            if (($lines[$a['id']] ?? '') !== ($lines[$b['id']] ?? '')) {
                continue;
            }
            $out[] = [
                'first' => $a['bill_no'],
                'second' => $b['bill_no'],
                'outlet' => $outlets[(int) $a['outlet_id']] ?? '',
                'cashier' => $users[(int) $a['create_user_id']] ?? ('#' . $a['create_user_id']),
                'amount' => self::money($a['total_amt']),
                'lines' => explode('/', $lines[$a['id']] ?? '0/0')[0],
                'time' => substr((string) $a['create_time'], 0, 16),
                'gap' => $gap . ' s',
                '_link' => Ui::to('order/view', ['id' => $b['id']]),
            ];
            if (count($out) >= 200) {
                break;
            }
        }
        return ['columns' => ['first' => 'First bill', 'second' => 'Second bill', 'outlet' => 'Outlet', 'cashier' => 'Cashier',
            'amount' => 'Amount', 'lines' => 'Lines', 'time' => 'Time', 'gap' => 'Apart'], 'rows' => $out];
    }

    private static function checkBillNoRepeats()
    {
        $rows = AiData::rows(
            'SELECT outlet_id, bill_no, COUNT(*) n, MIN(create_time) first_time, MAX(create_time) last_time, MAX(id) last_id'
            . ' FROM tbl_order WHERE create_time >= :f AND bill_no IS NOT NULL AND bill_no <> \'\''
            . ' GROUP BY outlet_id, bill_no HAVING COUNT(*) > 1 ORDER BY last_time DESC LIMIT 200',
            [':f' => date('Y-m-d 00:00:00', strtotime('-90 days'))]);
        $outlets = self::outlets();
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'bill_no' => $r['bill_no'],
                'outlet' => $outlets[(int) $r['outlet_id']] ?? '',
                'times' => (int) $r['n'],
                'first' => substr((string) $r['first_time'], 0, 16),
                'last' => substr((string) $r['last_time'], 0, 16),
                '_link' => Ui::to('order/view', ['id' => $r['last_id']]),
            ];
        }
        return ['columns' => ['bill_no' => 'Bill number', 'outlet' => 'Outlet', 'times' => 'Bills', 'first' => 'First',
            'last' => 'Last'], 'rows' => $out];
    }

    private static function checkHighDiscount()
    {
        $rows = AiData::rows(
            // Only the till's item/order stores gross_total_amt; elsewhere it is
            // 0, and the bill total plus its discount is the gross.
            'SELECT x.create_user_id, COUNT(*) bills,'
            . ' SUM(CASE WHEN x.gross > 0 AND x.discount > 0.2 * x.gross THEN 1 ELSE 0 END) big,'
            . ' SUM(x.discount) discount, SUM(x.gross) gross'
            . ' FROM (SELECT o.create_user_id, COALESCE(o.discount_amt, 0) discount,'
            . ' COALESCE(NULLIF(o.gross_total_amt, 0), o.total_amt + COALESCE(o.discount_amt, 0)) gross'
            . ' FROM tbl_order o WHERE o.create_time >= :f) x'
            . ' GROUP BY x.create_user_id ORDER BY big DESC, discount DESC',
            [':f' => date('Y-m-d 00:00:00', strtotime('-30 days'))]);
        $users = self::users(array_column($rows, 'create_user_id'));
        $out = [];
        $flagged = 0;
        foreach ($rows as $r) {
            if ((float) $r['discount'] <= 0) {
                continue;
            }
            $flagged += (int) $r['big'] > 0 ? 1 : 0;
            $out[] = [
                'cashier' => $users[(int) $r['create_user_id']] ?? ('#' . $r['create_user_id']),
                'bills' => (int) $r['bills'],
                'big' => (int) $r['big'],
                'discount' => self::money($r['discount']),
                'share' => (float) $r['gross'] > 0 ? self::num(100 * $r['discount'] / $r['gross'], 1) . '%' : '',
            ];
        }
        return ['columns' => ['cashier' => 'Cashier', 'bills' => 'Bills', 'big' => 'Bills over 20% off',
            'discount' => 'Discount given', 'share' => 'Of gross'], 'rows' => $out, 'total' => $flagged];
    }

    private static function checkCreditNoteOveruse()
    {
        $rows = AiData::rows(
            'SELECT id, credit_number, amt, amt_used, create_time FROM tbl_credit_note'
            . ' WHERE amt_used > amt + 0.01 ORDER BY id DESC LIMIT 200');
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'number' => $r['credit_number'],
                'value' => self::money($r['amt']),
                'used' => self::money($r['amt_used']),
                'created' => substr((string) $r['create_time'], 0, 10),
                '_link' => Ui::to('creditNote/view', ['id' => $r['id']]),
            ];
        }
        return ['columns' => ['number' => 'Credit note', 'value' => 'Value', 'used' => 'Used', 'created' => 'Created'],
            'rows' => $out];
    }

    // ------------------------------------------------------------ item master

    private static function checkMissingHsn()
    {
        $total = (int) AiData::scalar("SELECT COUNT(*) FROM tbl_item WHERE status = 0 AND (hsn_code IS NULL OR TRIM(hsn_code) = '')");
        $rows = AiData::rows(
            "SELECT id, title, create_time FROM tbl_item WHERE status = 0 AND (hsn_code IS NULL OR TRIM(hsn_code) = '')"
            . ' ORDER BY id DESC LIMIT 200');
        $out = [];
        foreach ($rows as $r) {
            $out[] = ['id' => $r['id'], 'item' => $r['title'], 'created' => substr((string) $r['create_time'], 0, 10),
                '_link' => Ui::to('item/update', ['id' => $r['id']])];
        }
        return ['columns' => ['id' => 'ID', 'item' => 'Item', 'created' => 'Created'], 'rows' => $out, 'total' => $total];
    }

    private static function checkDuplicateNames()
    {
        $rows = AiData::rows(
            'SELECT LOWER(TRIM(title)) name, COUNT(*) n, GROUP_CONCAT(id ORDER BY id) ids'
            . ' FROM tbl_item WHERE status = 0 AND title IS NOT NULL AND TRIM(title) <> \'\''
            . ' GROUP BY LOWER(TRIM(title)) HAVING COUNT(*) > 1 ORDER BY n DESC, name LIMIT 200');
        $out = [];
        foreach ($rows as $r) {
            $ids = explode(',', (string) $r['ids']);
            $out[] = ['item' => strtoupper($r['name']), 'count' => (int) $r['n'], 'ids' => implode(', ', $ids),
                '_link' => Ui::to('item/update', ['id' => $ids[0]])];
        }
        return ['columns' => ['item' => 'Name', 'count' => 'Items', 'ids' => 'Item IDs'], 'rows' => $out];
    }

    private static function checkDuplicateBarcodes()
    {
        $rows = AiData::rows(
            "SELECT d.bar_code, COUNT(DISTINCT d.item_id) items, GROUP_CONCAT(DISTINCT i.title ORDER BY i.title SEPARATOR ' | ') titles"
            . ' FROM tbl_item_detail d JOIN tbl_item i ON i.id = d.item_id'
            . " WHERE d.bar_code IS NOT NULL AND d.bar_code <> '' AND d.status = 0 AND i.status = 0"
            . ' GROUP BY d.bar_code HAVING COUNT(DISTINCT d.item_id) > 1 ORDER BY items DESC, d.bar_code LIMIT 200');
        $out = [];
        foreach ($rows as $r) {
            $out[] = ['barcode' => $r['bar_code'], 'items' => (int) $r['items'], 'titles' => $r['titles']];
        }
        return ['columns' => ['barcode' => 'Barcode', 'items' => 'Items', 'titles' => 'Names'], 'rows' => $out];
    }

    // ---------------------------------------------------------------- helpers

    /** Stock per "outlet:item_detail", batches summed. */
    public static function stockByOutlet(array $detailIds)
    {
        $out = [];
        $detailIds = array_values(array_unique(array_map('intval', $detailIds)));
        if (count($detailIds) > 500) {
            // Many items (the reorder check asks about everything sold in 28
            // days): one grouped pass over the stock table - the same shape as
            // the dead-stock check, which takes under a second on the store's
            // data - instead of a pass per 500 items.
            $want = array_flip($detailIds);
            foreach (AiData::rows(
                'SELECT COALESCE(NULLIF(s.outlet_id, 0), d.outlet_id, 0) outlet_id, s.item_detail_id, SUM(s.balance_qty) qty'
                . ' FROM tbl_item_stock s JOIN tbl_item_detail d ON d.id = s.item_detail_id'
                . ' GROUP BY COALESCE(NULLIF(s.outlet_id, 0), d.outlet_id, 0), s.item_detail_id') as $r) {
                if (isset($want[(int) $r['item_detail_id']])) {
                    $out[$r['outlet_id'] . ':' . $r['item_detail_id']] = (float) $r['qty'];
                }
            }
            return $out;
        }
        foreach (array_chunk($detailIds, 500) as $chunk) {
            $params = [];
            foreach (AiData::rows(
                'SELECT COALESCE(NULLIF(s.outlet_id, 0), d.outlet_id, 0) outlet_id, s.item_detail_id, SUM(s.balance_qty) qty'
                . ' FROM tbl_item_stock s JOIN tbl_item_detail d ON d.id = s.item_detail_id'
                . ' WHERE s.item_detail_id IN ' . AiData::inList($chunk, 'd', $params)
                . ' GROUP BY COALESCE(NULLIF(s.outlet_id, 0), d.outlet_id, 0), s.item_detail_id', $params) as $r) {
                $out[$r['outlet_id'] . ':' . $r['item_detail_id']] = (float) $r['qty'];
            }
        }
        return $out;
    }

    /** item_detail id => barcode, title, item id, reorder qty, item status. */
    public static function details(array $detailIds)
    {
        $out = [];
        foreach (array_chunk(array_values(array_unique($detailIds)), 500) as $chunk) {
            $params = [];
            foreach (AiData::rows(
                'SELECT d.id, d.bar_code, d.item_id, COALESCE(NULLIF(d.reorder_qty, 0), i.reorder_qty) reorder_qty,'
                . ' i.title, i.status item_status'
                . ' FROM tbl_item_detail d LEFT JOIN tbl_item i ON i.id = d.item_id'
                . ' WHERE d.id IN ' . AiData::inList($chunk, 'd', $params), $params) as $r) {
                $out[(int) $r['id']] = $r;
            }
        }
        return $out;
    }

    /** item_detail id => the vendor of its most recent GRN line. Best effort. */
    private static function lastVendors(array $detailIds)
    {
        if (!$detailIds) {
            return [];
        }
        try {
            // Two steps rather than `id IN (SELECT MAX(id) ... GROUP BY)`: MySQL
            // runs that form by scanning every GRN line ever entered and
            // probing the subquery's result for each. Here the newest line
            // per item is found first, then those lines are read by id.
            $params = [];
            $last = AiData::rows(
                'SELECT MAX(id) id FROM tbl_purchase_bill_detail WHERE item_detail_id IN '
                . AiData::inList($detailIds, 'd', $params) . ' GROUP BY item_detail_id', $params);
            if (!$last) {
                return [];
            }
            $params = [];
            $out = [];
            foreach (AiData::rows(
                'SELECT pbd.item_detail_id, v.name FROM tbl_purchase_bill_detail pbd'
                . ' JOIN tbl_purchase_bill pb ON pb.id = pbd.purchase_bill_id LEFT JOIN tbl_vendor v ON v.id = pb.vendor_id'
                . ' WHERE pbd.id IN ' . AiData::inList(array_column($last, 'id'), 'l', $params), $params) as $r) {
                $out[(int) $r['item_detail_id']] = (string) $r['name'];
            }
            return $out;
        } catch (\Throwable $e) {
            \Yii::warning('lastVendors skipped: ' . $e->getMessage(), __METHOD__);
            return [];
        }
    }

    public static function outlets()
    {
        $out = [];
        foreach (AiData::rows('SELECT id, title FROM tbl_outlet', [], 300) as $r) {
            $out[(int) $r['id']] = $r['title'];
        }
        return $out;
    }

    private static function users(array $ids)
    {
        $ids = array_filter(array_map('intval', $ids));
        if (!$ids) {
            return [];
        }
        $params = [];
        $out = [];
        foreach (AiData::rows('SELECT id, full_name, username FROM tbl_user WHERE id IN ' . AiData::inList($ids, 'u', $params), $params) as $r) {
            $out[(int) $r['id']] = $r['full_name'] ?: $r['username'];
        }
        return $out;
    }

    public static function num($v, $dec = 0)
    {
        $v = (float) $v;
        if ($dec === 0 && abs($v - round($v)) > 0.0005) {
            $dec = 3;
        }
        return number_format($v, $dec, '.', ',');
    }

    public static function money($v)
    {
        return '₹' . number_format((float) $v, 2, '.', ',');
    }
}
