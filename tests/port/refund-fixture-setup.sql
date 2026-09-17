-- Fixture for order/refund.
--
-- The refund path writes OrderRefund, OrderRefundItem, ItemStock and StockLog,
-- and - when returning stock takes an item back above its reorder level - it
-- DELETES pending MrsDetail rows and sometimes the Mrs itself. Pointing that at
-- real items could delete real requisitions, so everything here is a fixture
-- row above id 9990000: its own item, item detail, stock, order and lines.
--
-- Rows are cloned from real ones rather than built column by column, because
-- these tables have long tails of NOT NULL columns with no defaults.

DELETE FROM tbl_stock_log         WHERE item_id = 9990500;
DELETE FROM tbl_order_refund_item WHERE item_id = 9990500;
DELETE FROM tbl_order_refund      WHERE order_id = 9990500;
DELETE FROM tbl_order_item        WHERE order_id = 9990500;
DELETE FROM tbl_order             WHERE id = 9990500;
DELETE FROM tbl_mrs_detail        WHERE item_id = 9990500;
DELETE FROM tbl_mrs               WHERE id = 9990500;
DELETE FROM tbl_item_stock        WHERE item_id = 9990500;
DELETE FROM tbl_item_detail       WHERE item_id = 9990500;
DELETE FROM tbl_item              WHERE id = 9990500;

-- item: min_qty is high so the requisition-cleanup branch stays off by default
DROP TEMPORARY TABLE IF EXISTS _i;
CREATE TEMPORARY TABLE _i AS SELECT * FROM tbl_item WHERE id = (SELECT MIN(id) FROM tbl_item);
UPDATE _i SET id = 9990500, title = 'PORT REFUND ITEM', item_code = 'PORTREF500',
              min_qty = 100000, is_discount = 0, updated_by = NULL;
INSERT INTO tbl_item SELECT * FROM _i;
DROP TEMPORARY TABLE _i;

DROP TEMPORARY TABLE IF EXISTS _d;
CREATE TEMPORARY TABLE _d AS SELECT * FROM tbl_item_detail WHERE id = 4;
UPDATE _d SET id = 9990500, item_id = 9990500, bar_code = 'PORTREFBAR500', updated_by = NULL;
INSERT INTO tbl_item_detail SELECT * FROM _d;
DROP TEMPORARY TABLE _d;

DROP TEMPORARY TABLE IF EXISTS _s;
CREATE TEMPORARY TABLE _s AS SELECT * FROM tbl_item_stock WHERE item_detail_id IS NOT NULL LIMIT 1;
UPDATE _s SET id = 9990500, item_id = 9990500, item_detail_id = 9990500,
              batch_number = 'PORTREFBATCH', purchase_qty = 50, balance_qty = 50, updated_by = NULL;
INSERT INTO tbl_item_stock SELECT * FROM _s;
DROP TEMPORARY TABLE _s;

-- the order being refunded, and one line of 10 units
DROP TEMPORARY TABLE IF EXISTS _o;
CREATE TEMPORARY TABLE _o AS SELECT * FROM tbl_order WHERE id = (SELECT MIN(id) FROM tbl_order);
-- an existing customer, so this fixture does not depend on setup_test.sql
UPDATE _o SET id = 9990500, customer_id = (SELECT MIN(id) FROM tbl_customer), bill_no = 999500,
              bill_date = '2026-09-17', total_amt = 1000.00, discount_amt = 0, updated_by = NULL;
INSERT INTO tbl_order SELECT * FROM _o;
DROP TEMPORARY TABLE _o;

DROP TEMPORARY TABLE IF EXISTS _oi;
CREATE TEMPORARY TABLE _oi AS SELECT * FROM tbl_order_item WHERE id = (SELECT MIN(id) FROM tbl_order_item);
UPDATE _oi SET id = 9990500, order_id = 9990500, item_id = 9990500, item_detail_id = 9990500,
               qty = 10, price = 90.00, sale_rate = 100.00, total_amt = 1000.00,
               discount_amt = 50.00, tax_amount = 47.62, discount_id = 0, updated_by = NULL;
INSERT INTO tbl_order_item SELECT * FROM _oi;
DROP TEMPORARY TABLE _oi;

SELECT CONCAT('refund fixture ready: order 9990500 qty=',
              (SELECT qty FROM tbl_order_item WHERE id = 9990500),
              ' stock=', (SELECT balance_qty FROM tbl_item_stock WHERE id = 9990500)) AS x;
