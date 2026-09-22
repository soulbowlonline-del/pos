DELETE FROM tbl_outbound_stub_log;
DELETE FROM tbl_whatsapp_logs              WHERE number = '9990001234';
DELETE FROM tbl_customer_otp_verification  WHERE customer_id = 9990001;
DELETE FROM tbl_customer_otp               WHERE customer_id = 9990001;
DELETE FROM tbl_loyalty_transactions       WHERE customer_id = 9990001;
DELETE FROM tbl_customer_loyalty           WHERE customer_id = 9990001;
DELETE FROM tbl_order_hold_item            WHERE order_hold_id = 9990002;
DELETE FROM tbl_order_hold                 WHERE id = 9990002;
DELETE FROM tbl_order                      WHERE id = 9990001;
DELETE FROM tbl_customer                   WHERE id = 9990001;
DELETE FROM tbl_user                       WHERE id = 9990002;
SELECT CONCAT('fixture rows remaining: ',
  (SELECT COUNT(*) FROM tbl_customer WHERE id=9990001) +
  (SELECT COUNT(*) FROM tbl_customer_loyalty WHERE customer_id=9990001) +
  (SELECT COUNT(*) FROM tbl_customer_otp WHERE customer_id=9990001) +
  (SELECT COUNT(*) FROM tbl_outbound_stub_log) +
  (SELECT COUNT(*) FROM tbl_order WHERE id=9990001) +
  (SELECT COUNT(*) FROM tbl_user WHERE id=9990002));
