-- Functional indexes behind order/groupTax, which took a hundred seconds.
--
-- The report's column callbacks - getGroupTaxCgstAmount() and the nine beside
-- it - each run a query of one shape, once per row of the report:
--
--     SELECT SUM(cgst_amt) FROM tbl_order_item
--      WHERE tax_id = ? AND DATE(create_time) = ?
--
-- tbl_order_item has an index on create_time, and DATE() around the column
-- makes it unusable: the optimiser cannot turn a function of a column back
-- into a range. There is no index on tax_id either, and only nine distinct
-- values, so one would not have helped much on its own. Every call was a full
-- scan - EXPLAIN said type ALL, key NULL, rows 4,786,788 - at about a second
-- each, and the page ran a hundred of them.
--
-- MySQL 8.0.13 added functional key parts, so the expression itself can be
-- indexed. It has to be written the way the query writes it for the optimiser
-- to match the two. After: type ref, key idx_order_item_taxdate, rows 1.
--
-- Measured on the full dataset, signed in, same request to both stacks:
--
--                        before      after (warm)
--   one day              -           0.21s both
--   a fortnight          106.6s  /  94.6s      0.64s  /  0.83s
--
-- Pure additions. No column, constraint or value changes, so neither
-- application can tell the difference except in how long it waits - which is
-- the point: the report is slow on Yii 1 too, and both get this.
--
-- The second table is small, 54,851 rows, and its scan was costing about a
-- second across fifty calls. Included because it is the same fault and the
-- index is cheap to build and to keep.
--
-- What this does NOT fix: the report is N+1 by construction, roughly ten
-- queries per row, and that is shared by both trees. A fortnight is fast now
-- because it is a handful of rows; nine months is still around twenty seconds
-- on both stacks because it is hundreds. Removing the N+1 means changing how
-- the report aggregates, in both trees, and is its own piece of work.
--
-- Run once against the imported DB. MySQL has no ADD INDEX IF NOT EXISTS, so
-- re-running errors on a duplicate name, which is harmless.

ALTER TABLE `tbl_order_item`
  ADD INDEX `idx_order_item_taxdate` ((DATE(`create_time`)), `tax_id`);

ALTER TABLE `tbl_order_refund_item`
  ADD INDEX `idx_refund_item_taxdate` ((DATE(`create_time`)), `tax_id`);
