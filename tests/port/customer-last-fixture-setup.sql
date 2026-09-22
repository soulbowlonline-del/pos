-- Fixtures for verifywhatappotp and getOrderHold.
-- Timestamps use Asia/Kolkata wall clock; see the note in otp-fixture-setup.sql.
SET @app_now = CONVERT_TZ(NOW(), '+00:00', '+05:30');

DELETE FROM tbl_customer_otp_verification WHERE customer_id = 9990001;
UPDATE tbl_customer SET is_enable_wa = 0 WHERE id = 9990001;

INSERT INTO tbl_customer_otp_verification (customer_id, otp_code, expires_at, is_verified, created_at)
VALUES (9990001, '654321', DATE_ADD(@app_now, INTERVAL 5 MINUTE), 0, @app_now);
INSERT INTO tbl_customer_otp_verification (customer_id, otp_code, expires_at, is_verified, created_at)
VALUES (9990001, '111111', DATE_SUB(@app_now, INTERVAL 5 MINUTE), 0, DATE_SUB(@app_now, INTERVAL 10 MINUTE));

-- A held order to consume. getOrderHold deletes it, so it is recreated before
-- every call.
DELETE FROM tbl_order_hold_item WHERE order_hold_id = 9990002;
DELETE FROM tbl_order_hold      WHERE id = 9990002;
INSERT INTO tbl_order_hold (id, bill_no, bill_date, qty, discount_amt, total_amt, paid_amt, status,
                            type_id, city_id, state_id, country_id, outlet_id, address, note,
                            create_time, customer_id)
VALUES (9990002, 999002, '2026-09-17', 0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 'addr', 'note',
        '2026-09-17 00:00:00', 9990001);

SELECT CONCAT('fixture ready: otp codes=',
  (SELECT COUNT(*) FROM tbl_customer_otp_verification WHERE customer_id=9990001),
  ' hold=', (SELECT COUNT(*) FROM tbl_order_hold WHERE id=9990002));
