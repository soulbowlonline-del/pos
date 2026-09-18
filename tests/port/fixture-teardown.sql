DELETE FROM tbl_outbound_stub_log;
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
DELETE FROM tbl_credit_note                WHERE amt IN (100, 200, 300, 1000);
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
DELETE FROM tbl_user                       WHERE id = 9990002;
-- GRN fixture (see item-grn-fixture-setup.sql). The user row has to go
-- before the emp row it points at.
DELETE FROM tbl_purchase_bill_detail       WHERE id IN (9990020, 9990021, 9990022);
DELETE FROM tbl_purchase_bill              WHERE id IN (9990010, 9990011, 9990012);
DELETE FROM tbl_user                       WHERE id = 9990004;
DELETE FROM tbl_emp                        WHERE id = 9990003;
SELECT CONCAT('fixture rows remaining: ',
  (SELECT COUNT(*) FROM tbl_customer WHERE id=9990001 OR contact_no='9995550001') +
  (SELECT COUNT(*) FROM tbl_customer_loyalty WHERE customer_id=9990001) +
  (SELECT COUNT(*) FROM tbl_customer_otp WHERE customer_id=9990001) +
  (SELECT COUNT(*) FROM tbl_outbound_stub_log) +
  (SELECT COUNT(*) FROM tbl_order WHERE id=9990001) +
  (SELECT COUNT(*) FROM tbl_user WHERE id IN (9990002,9990004)) +
  (SELECT COUNT(*) FROM tbl_emp WHERE id=9990003) +
  (SELECT COUNT(*) FROM tbl_purchase_bill WHERE id IN (9990010,9990011,9990012)));
