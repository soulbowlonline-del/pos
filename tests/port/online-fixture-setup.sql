-- Fixture for the online-order write paths: cancelOrder, assignOrder,
-- orderUpdate, shipOrder and completeOrder all mutate a row, so they need one
-- of their own rather than a real order.
--
-- Ids are well above everything real. first_name is set and last_name is left
-- empty on purpose: BaseOnlineOrder's `default` rule rewrites empty attributes
-- to NULL on every save, and that only shows up if a blank one is there to
-- start with.

DELETE FROM tbl_online_order_item WHERE order_id = 9990100;
DELETE FROM tbl_online_order      WHERE id IN (9990100, 9990101);

INSERT INTO tbl_online_order
  (id, order_id, order_date, item_count, grand_total, first_name, last_name,
   street, city, telephone, mobile, zip_code, country, delivery_slot,
   delivery_boy, delivery_telephone, payment_method, delivery_method, ship_name,
   order_from, comment, is_shipped, type_id, status, order_status,
   create_time, create_user_id, delivery_boy_id, picker_id)
VALUES
  (9990100, 999900001, '2026-09-17 10:00:00', 2, 250.00, 'PORT', '',
   '1 Test Street', 'Testville', '0112345678', '9990001234', '110001', 'India', '10-12',
   '', '', 'Cash on Delivery', 'Home Delivery', 'PORT TEST',
   'app', 'fixture order', 0, 0, 'Pending', 0,
   '2026-09-17 10:00:00', 1, 9990002, NULL),
  -- a second one already assigned and shipped, for completeOrder
  (9990101, 999900002, '2026-09-17 11:00:00', 1, 99.00, 'PORT2', NULL,
   NULL, NULL, NULL, '9990001234', NULL, NULL, NULL,
   NULL, NULL, NULL, NULL, NULL,
   'app', NULL, 1, 0, 'Pending', 2,
   '2026-09-17 11:00:00', 1, 9990002, NULL);

-- two lines whose product codes resolve to real items, so the item payload is
-- exercised rather than skipped
INSERT INTO tbl_online_order_item
  (id, name, barcode, qty, price, total, product_code, type_id, status, create_time, create_user_id, order_id)
SELECT 9990100, i.title, '', 2.0000, 10.00, 20.00, i.item_code, 0, 0, '2026-09-17 10:00:00', 1, 9990100
  FROM tbl_item i WHERE i.item_code = '14941' LIMIT 1;
INSERT INTO tbl_online_order_item
  (id, name, barcode, qty, price, total, product_code, type_id, status, create_time, create_user_id, order_id)
SELECT 9990101, i.title, '8906005060013', 1.0000, 60.00, 60.00, i.item_code, 0, 0, '2026-09-17 10:00:00', 1, 9990100
  FROM tbl_item i WHERE i.item_code = '979' LIMIT 1;

-- The rider these are assigned to is user 9990002, created by emp_fixture.sql
-- (role_id 7, which is what completeOrder notifies).

SELECT CONCAT('online fixture ready: orders 9990100/9990101, rider 9990002, lines=',
              (SELECT COUNT(*) FROM tbl_online_order_item WHERE order_id = 9990100));
