-- Phase 0 performance indexes (added 2026-07-11)
-- These fix slow queries exposed by the full prod dataset under Docker:
--   * dashboard monthly chart (tbl_order date scans)
--   * per-item SUM(...) WHERE item_id N+1 on the item page (full scans of large
--     detail tables — up to ~10s each on tbl_order_item's 4.24M rows)
--   * countOrders XHR (tbl_online_order.type_id)
--
-- Run once against the imported DB (indexes are not in the original dump):
--   docker compose -f docker-compose.legacy.yml exec -T db \
--     mysql -uroot -p"$DB_ROOT_PASSWORD" pos_live < db_changes/2026-07-11-phase0-perf-indexes.sql
--
-- MySQL 5.7 has no "ADD INDEX IF NOT EXISTS"; re-running errors on duplicates
-- (harmless — it means the index already exists).

-- Dashboard chart / date-range queries
ALTER TABLE `tbl_order`               ADD INDEX `idx_create_time` (`create_time`);

-- Date-filtered reports (e.g. daily bill report) scanned all ~1.3M rows
ALTER TABLE `tbl_order`               ADD INDEX `idx_bill_date` (`bill_date`);

-- countOrders XHR: COUNT(*) WHERE type_id = ?
ALTER TABLE `tbl_online_order`        ADD INDEX `idx_type_id` (`type_id`);

-- Item page per-item aggregates (covering indexes = index-only SUMs)
ALTER TABLE `tbl_order_item`          ADD INDEX `idx_item_id_amt_qty` (`item_id`, `total_amt`, `qty`);
ALTER TABLE `tbl_purchase_bill_detail` ADD INDEX `idx_item_id_amount` (`item_id`, `amount`);

-- Remaining detail tables summed per item_id (plain item_id lookup index)
ALTER TABLE `tbl_mrn_detail`            ADD INDEX `idx_item_id` (`item_id`);
ALTER TABLE `tbl_purchase_order_detail` ADD INDEX `idx_item_id` (`item_id`);
ALTER TABLE `tbl_order_refund_item`     ADD INDEX `idx_item_id` (`item_id`);
ALTER TABLE `tbl_b2bpurchase_bill_detail` ADD INDEX `idx_item_id` (`item_id`);
ALTER TABLE `tbl_scanned_items`         ADD INDEX `idx_item_id` (`item_id`);
ALTER TABLE `tbl_order_hold_item`       ADD INDEX `idx_item_id` (`item_id`);
