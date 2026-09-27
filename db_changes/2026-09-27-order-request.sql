-- Idempotency keys for the POS checkout API: item/order, item/ordertest and
-- item/punchorder, on both stacks.
--
-- The .NET application and the Android APK resend a checkout when the first
-- answer is not a clean OK, and nothing in the request says it is a resend:
-- the bill_no and bill_date they post are ignored and the server numbers the
-- bill after the commit. Without a key, OrderDedupe (protected/components and
-- app2/components) recognises a resend by its contents within ten seconds.
--
-- A client that sends a key - POST request_id, or an X-Request-Id header, at
-- most 64 characters - gets an exact answer instead: the first request records
-- its key against the order inside the order's own transaction, and any later
-- request with the same key is answered with that order and creates nothing.
-- The unique key is what makes it hold under concurrency: a second request that
-- gets as far as the insert fails on it, rolls back, and answers with the order
-- that won.
--
-- The code works before this runs: a missing table is noticed, and the key is
-- ignored in favour of the content check. Pure addition - no existing table,
-- column or value changes. Safe to run more than once.
--
-- request_id is compared byte for byte (utf8mb4_bin): a key is an opaque token,
-- and 'abc' and 'ABC' are two different requests.

CREATE TABLE IF NOT EXISTS `tbl_order_request` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `request_id` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `order_id` INT NOT NULL,
  `create_time` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_request_id` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
