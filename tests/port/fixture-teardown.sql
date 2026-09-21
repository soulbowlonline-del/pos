DELETE FROM tbl_outbound_stub_log;
-- `number = '' OR number IS NULL` is not scoped to a fixture either. It
-- matches no row in the 5.6 baseline, so unlike the credit notes below it has
-- never deleted anything real; a per-table row-count comparison against that
-- baseline confirms tbl_credit_note was the only table that lost rows.
DELETE FROM tbl_whatsapp_logs              WHERE number IN ('9990001234', '9995550001') OR number = '' OR number IS NULL;
DELETE FROM tbl_customer_otp_verification  WHERE customer_id = 9990001;
DELETE FROM tbl_customer_otp               WHERE customer_id = 9990001;
DELETE FROM tbl_loyalty_transactions       WHERE customer_id = 9990001;
DELETE FROM tbl_customer_loyalty           WHERE customer_id = 9990001;
DELETE FROM tbl_order_hold_item            WHERE order_hold_id = 9990002;
DELETE FROM tbl_order_hold                 WHERE id = 9990002;
DELETE FROM tbl_order                      WHERE id = 9990001;
-- customer/add creates rows with the test phone number
DELETE FROM tbl_customer_loyalty WHERE customer_id IN (SELECT id FROM tbl_customer WHERE contact_no = '9995550001');
DELETE FROM tbl_customer                   WHERE contact_no = '9995550001';
DELETE FROM tbl_customer                   WHERE id = 9990001;
DELETE FROM tbl_online_order_item          WHERE order_id = 9990100;
DELETE FROM tbl_online_order               WHERE id IN (9990100, 9990101);
-- order/refund fixture: its own item, detail, stock, order and lines, plus
-- everything a refund of them writes.
DELETE FROM tbl_stock_log                  WHERE item_id = 9990500;
DELETE FROM tbl_order_refund_item          WHERE item_id = 9990500;
DELETE FROM tbl_order_refund               WHERE order_id = 9990500;
DELETE FROM tbl_order_item                 WHERE order_id = 9990500;
DELETE FROM tbl_order                      WHERE id = 9990500;
DELETE FROM tbl_mrs_detail                 WHERE item_id = 9990500;
DELETE FROM tbl_mrs                        WHERE id = 9990500;
DELETE FROM tbl_item_stock                 WHERE item_id = 9990500;
DELETE FROM tbl_item_detail                WHERE item_id = 9990500;
DELETE FROM tbl_item                       WHERE id = 9990500;
-- Credit notes raised by the refund suite. Scoped by id, not by amount:
-- this line read `WHERE amt IN (100, 200, 300, 1000)`, and the application's
-- own credit notes are round numbers too, so every run deleted the real ones
-- as well. 601 of them had gone - 322 at 100, 113 at 200, 103 at 300, and the
-- rest at 0 and 1000, going back to 2018 and worth 96,700 between them -
-- before a row-count comparison against the 5.6 database found it. That
-- database is the only reason they still exist; nothing else here had a copy.
-- The highest credit note in the data set is 15972, as the highest real order
-- is 1,581,602 on line 47.
DELETE FROM tbl_credit_note                WHERE id > 15972;
-- item/adjust fixture: its own vendor, item, detail, stock, item-vendor link
-- and requisition, plus everything an adjustment of them writes.
DELETE FROM tbl_stock_log        WHERE item_id = 9990600;
DELETE FROM tbl_stock_adjust_log WHERE item_id = 9990600;
DELETE FROM tbl_mrs_adjust       WHERE item_id = 9990600;
DELETE FROM tbl_mrs_detail       WHERE item_id = 9990600 OR mrs_id = 9990600;
DELETE FROM tbl_mrn              WHERE mrs_id = 9990600;
DELETE FROM tbl_mrs              WHERE id = 9990600 OR vendor_id = 9990600;
DELETE FROM tbl_item_vendor      WHERE item_detail_id = 9990600 OR vendor_id = 9990600;
DELETE FROM tbl_item_stock       WHERE item_id = 9990600;
DELETE FROM tbl_item_detail      WHERE item_id = 9990600;
DELETE FROM tbl_item             WHERE id = 9990600;
DELETE FROM tbl_vendor           WHERE id = 9990600;
-- orders and held orders raised by the item/order, ordertest and punchorder
-- suites, and the PDF bills punchorder writes. Real orders are all below
-- 1,581,602; 9990001 and 9990500 are fixtures.
DELETE FROM tbl_order_item                 WHERE order_id > 9990000 AND order_id NOT IN (9990001, 9990500);
DELETE FROM tbl_order_hold_item            WHERE order_hold_id > 9990000 AND order_hold_id <> 9990002;
DELETE FROM tbl_order                      WHERE id > 9990000 AND id NOT IN (9990001, 9990500);
DELETE FROM tbl_order_hold                 WHERE id > 9990000 AND id <> 9990002;
DELETE FROM tbl_user                       WHERE id = 9990002;
-- GRN fixture (see item-grn-fixture-setup.sql). The user row has to go
-- before the emp row it points at.
DELETE FROM tbl_purchase_bill_detail       WHERE id IN (9990020, 9990021, 9990022);
DELETE FROM tbl_purchase_bill              WHERE id IN (9990010, 9990011, 9990012);
DELETE FROM tbl_user                       WHERE id = 9990004;
DELETE FROM tbl_emp                        WHERE id = 9990003;
DELETE FROM tbl_role_permission            WHERE id >= 9990100;
DELETE FROM tbl_permission                 WHERE id >= 9990100;
SELECT CONCAT('fixture rows remaining: ',
  (SELECT COUNT(*) FROM tbl_customer WHERE id=9990001 OR contact_no='9995550001') +
  (SELECT COUNT(*) FROM tbl_customer_loyalty WHERE customer_id=9990001) +
  (SELECT COUNT(*) FROM tbl_customer_otp WHERE customer_id=9990001) +
  (SELECT COUNT(*) FROM tbl_outbound_stub_log) +
  (SELECT COUNT(*) FROM tbl_order WHERE id=9990001) +
  (SELECT COUNT(*) FROM tbl_user WHERE id IN (9990002,9990004)) +
  (SELECT COUNT(*) FROM tbl_emp WHERE id=9990003) +
  (SELECT COUNT(*) FROM tbl_purchase_bill WHERE id IN (9990010,9990011,9990012)) +
  (SELECT COUNT(*) FROM tbl_permission WHERE id >= 9990100) +
  (SELECT COUNT(*) FROM tbl_role_permission WHERE id >= 9990100));
