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
