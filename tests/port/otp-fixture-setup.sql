-- OTP fixture.
--
-- Timestamps are written as Asia/Kolkata wall-clock values, not MySQL's NOW().
--
-- MySQL runs on UTC in this stack while PHP runs on Asia/Kolkata, 5h30m ahead.
-- CustomerOtp::generateOTP() builds expires_at in PHP with
-- date('Y-m-d H:i:s', strtotime('+5 minutes')) and verifyOTP() compares it with
-- PHP's time(), so the stored value is a Kolkata wall clock that MySQL never
-- converts. A fixture built from MySQL NOW() is therefore 5h30m in the past as
-- far as the application is concerned, and every code reads as expired.
--
-- CONVERT_TZ with explicit offsets needs no timezone tables loaded.
DELETE FROM tbl_customer_otp WHERE customer_id = 9990001;
UPDATE tbl_customer SET is_enable_wa = 0, contact_no = '9990001234' WHERE id = 9990001;

SET @app_now = CONVERT_TZ(NOW(), '+00:00', '+05:30');

INSERT INTO tbl_customer_otp (customer_id, phone_number, otp_code, attempts, is_verified, expires_at, created_at)
VALUES (9990001, '9990001234', '123456', 0, 0, DATE_ADD(@app_now, INTERVAL 5 MINUTE), @app_now);

INSERT INTO tbl_customer_otp (customer_id, phone_number, otp_code, attempts, is_verified, expires_at, created_at)
VALUES (9990001, '9990001234', '999999', 0, 0, DATE_SUB(@app_now, INTERVAL 5 MINUTE), DATE_SUB(@app_now, INTERVAL 10 MINUTE));

SELECT CONCAT('otp fixture ready, app_now=', @app_now, ' codes=', COUNT(*)) FROM tbl_customer_otp WHERE customer_id=9990001;
