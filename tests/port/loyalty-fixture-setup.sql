-- Fixture well above every existing id, so nothing real is touched.
DELETE FROM tbl_loyalty_transactions WHERE customer_id = 9990001;
DELETE FROM tbl_customer_loyalty     WHERE customer_id = 9990001;
DELETE FROM tbl_order                WHERE id = 9990001;
DELETE FROM tbl_customer             WHERE id = 9990001;

INSERT INTO tbl_customer (id, name, contact_no) VALUES (9990001, 'PORT TEST', '999-PORT-TEST');
INSERT INTO tbl_customer_loyalty (customer_id, total_points, lifetime_earned, lifetime_redeemed)
  VALUES (9990001, 1000, 1000, 0);

-- bill_date and bill_no are set deliberately. This id is higher than every real
-- order, so /api/order/getLastOrder returns THIS row; with a zero bill_date
-- getOrderBillNo() calls strtotime('0000-00-00'), which returns false and takes
-- the endpoint down on PHP 8. Give the fixture realistic values so it does not
-- break unrelated endpoints while it exists.
INSERT INTO tbl_order (id, customer_id, bill_no, bill_date, create_time, outlet_id, qty, total_amt)
VALUES (9990001, 9990001, 999001, '2026-09-17', '2026-09-17 00:00:00', 0, 0, 0);

SELECT CONCAT('fixture ready: customer=9990001 order=9990001 points=',
              (SELECT total_points FROM tbl_customer_loyalty WHERE customer_id=9990001));
