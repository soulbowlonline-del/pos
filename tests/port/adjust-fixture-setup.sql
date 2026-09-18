-- Fixture for item/adjust.
--
-- adjust writes ItemStock, StockAdjustLog, StockLog, touches Item and
-- ItemDetail, flips a pending MrsAdjust, and then either DELETES pending
-- requisition rows or CREATES a requisition and its line. Pointed at real data
-- that would delete or invent real requisitions, so everything here is its own:
-- vendor, item, detail, stock, item-vendor link and requisition, all above id
-- 9990600.
--
-- Rows are cloned from real ones through a temporary table, because these
-- tables have long tails of NOT NULL columns with no defaults.

DELETE FROM tbl_stock_log        WHERE item_id = 9990600;
DELETE FROM tbl_stock_adjust_log WHERE item_id = 9990600;
DELETE FROM tbl_mrs_adjust       WHERE item_id = 9990600;
DELETE FROM tbl_mrs_detail       WHERE item_id = 9990600 OR mrs_id = 9990600;
DELETE FROM tbl_mrs              WHERE id = 9990600 OR vendor_id = 9990600;
DELETE FROM tbl_mrn              WHERE mrs_id = 9990600;
DELETE FROM tbl_item_vendor      WHERE item_detail_id = 9990600 OR vendor_id = 9990600;
DELETE FROM tbl_item_stock       WHERE item_id = 9990600;
DELETE FROM tbl_item_detail      WHERE item_id = 9990600;
DELETE FROM tbl_item             WHERE id = 9990600;
DELETE FROM tbl_vendor           WHERE id = 9990600;

DROP TEMPORARY TABLE IF EXISTS _v;
CREATE TEMPORARY TABLE _v AS SELECT * FROM tbl_vendor WHERE id = (SELECT MIN(id) FROM tbl_vendor);
UPDATE _v SET id = 9990600, name = 'PORT ADJUST VENDOR', updated_by = NULL;
INSERT INTO tbl_vendor SELECT * FROM _v;
DROP TEMPORARY TABLE _v;

-- min_qty 20: a stock level above it takes the cancel branch, below it the
-- create-a-requisition branch.
DROP TEMPORARY TABLE IF EXISTS _i;
CREATE TEMPORARY TABLE _i AS SELECT * FROM tbl_item WHERE id = (SELECT MIN(id) FROM tbl_item);
UPDATE _i SET id = 9990600, title = 'PORT ADJUST ITEM', item_code = 'PORTADJ600',
              min_qty = 20, max_qty = 60, reorder_qty = 30, purchase_price = 40.00,
              is_discount = 0, updated_by = NULL;
INSERT INTO tbl_item SELECT * FROM _i;
DROP TEMPORARY TABLE _i;

DROP TEMPORARY TABLE IF EXISTS _d;
CREATE TEMPORARY TABLE _d AS SELECT * FROM tbl_item_detail WHERE id = 4;
UPDATE _d SET id = 9990600, item_id = 9990600, bar_code = 'PORTADJBAR600', updated_by = NULL;
INSERT INTO tbl_item_detail SELECT * FROM _d;
DROP TEMPORARY TABLE _d;

-- 50 in stock at outlet 5, comfortably above min_qty
DROP TEMPORARY TABLE IF EXISTS _s;
CREATE TEMPORARY TABLE _s AS SELECT * FROM tbl_item_stock WHERE item_detail_id IS NOT NULL LIMIT 1;
UPDATE _s SET id = 9990600, item_id = 9990600, item_detail_id = 9990600, outlet_id = 5,
              vendor_id = 9990600, batch_number = 'PORTADJBATCH',
              purchase_qty = 50, balance_qty = 50, updated_by = NULL;
INSERT INTO tbl_item_stock SELECT * FROM _s;
DROP TEMPORARY TABLE _s;

-- the item-vendor link. Note item_detail_id holds the ITEM id: that is the
-- mismatch the relation and this action both rely on.
DROP TEMPORARY TABLE IF EXISTS _iv;
CREATE TEMPORARY TABLE _iv AS SELECT * FROM tbl_item_vendor LIMIT 1;
UPDATE _iv SET id = 9990600, item_detail_id = 9990600, vendor_id = 9990600, updated_by = NULL;
INSERT INTO tbl_item_vendor SELECT * FROM _iv;
DROP TEMPORARY TABLE _iv;

-- a pending requisition for this vendor, so $vendorMRS->id resolves, plus one
-- pending line for the item so the cancel branch has something to delete
DROP TEMPORARY TABLE IF EXISTS _m;
CREATE TEMPORARY TABLE _m AS SELECT * FROM tbl_mrs WHERE id = (SELECT MIN(id) FROM tbl_mrs);
-- the source row's create_user_id may not satisfy fk_mrs_create_user_id
SET @u = (SELECT MIN(id) FROM tbl_user);
UPDATE _m SET id = 9990600, vendor_id = 9990600, outlet_id = 5, status = 0,
              create_user_id = @u, updated_by = NULL;
INSERT INTO tbl_mrs SELECT * FROM _m;
DROP TEMPORARY TABLE _m;

DROP TEMPORARY TABLE IF EXISTS _md;
CREATE TEMPORARY TABLE _md AS SELECT * FROM tbl_mrs_detail WHERE id = (SELECT MIN(id) FROM tbl_mrs_detail);
UPDATE _md SET id = 9990600, mrs_id = 9990600, item_id = 9990600, item_detail_id = 9990600,
               outlet_id = 5, status = 0, create_user_id = @u, updated_by = NULL;
INSERT INTO tbl_mrs_detail SELECT * FROM _md;
DROP TEMPORARY TABLE _md;

INSERT INTO tbl_mrs_adjust (id, item_id, item_detail_id, mrs_detail_id, qty, type_id, status, create_time)
VALUES (9990600, 9990600, 9990600, 9990600, 5, 0, 0, '2026-09-17 00:00:00');

SELECT CONCAT('adjust fixture ready: item 9990600, stock=',
              (SELECT balance_qty FROM tbl_item_stock WHERE id = 9990600),
              ', min_qty=', (SELECT min_qty FROM tbl_item WHERE id = 9990600)) AS x;
